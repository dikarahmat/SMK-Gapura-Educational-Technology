(function () {
    'use strict';

    var MUTE_STORAGE_KEY = 'kioskSpeechMuted';
    var BEEP_END_TIMEOUT_MS = 800;

    var synth = window.speechSynthesis;
    var supported = !!(synth && window.SpeechSynthesisUtterance);
    var muted = false;
    var idVoices = [];
    var voice = null;
    var unlocked = false;
    var speakToken = 0;
    var beepToken = 0;

    try {
        muted = window.localStorage.getItem(MUTE_STORAGE_KEY) === '1';
    } catch (e) {}

    function normalizeLang(lang) {
        return String(lang || '').toLowerCase().replace(/_/g, '-');
    }

    function isIndonesianVoice(candidate) {
        var lang = normalizeLang(candidate.lang);
        return lang === 'id' || lang.indexOf('id-') === 0;
    }

    function voiceName(candidate) {
        return String(candidate.name || '');
    }

    function isFemaleVoice(candidate) {
        return /gadis|damayanti|female/i.test(voiceName(candidate));
    }

    function isMaleVoice(candidate) {
        return /andika|rizki|\bmale\b/i.test(voiceName(candidate));
    }

    function voicePriority(candidate) {
        if (/google/i.test(voiceName(candidate))) return 0;
        if (isFemaleVoice(candidate)) return 1;
        if (!isMaleVoice(candidate)) return 2;
        return 3;
    }

    function sortIdVoices(list) {
        return list.slice().sort(function (a, b) {
            var priority = voicePriority(a) - voicePriority(b);
            if (priority !== 0) return priority;
            return voiceName(a).localeCompare(voiceName(b));
        });
    }

    function refreshVoices() {
        if (!supported) return;

        var allVoices = [];
        try { allVoices = synth.getVoices() || []; } catch (e) { allVoices = []; }
        if (!Array.isArray(allVoices)) allVoices = [];

        idVoices = sortIdVoices(allVoices.filter(isIndonesianVoice));

        voice = null;
        for (var i = 0; i < idVoices.length; i++) {
            if (voicePriority(idVoices[i]) < 3) {
                voice = idVoices[i];
                break;
            }
        }
    }

    if (supported) {
        refreshVoices();
        if (typeof synth.addEventListener === 'function') {
            synth.addEventListener('voiceschanged', refreshVoices);
        } else {
            synth.onvoiceschanged = refreshVoices;
        }
    }

    function titleCase(value) {
        return String(value || '').trim().replace(/\S+/g, function (word) {
            return word.toLowerCase().replace(/(^|[-'.])([a-z\u00C0-\u024F])/g, function (match, separator, character) {
                return separator + character.toUpperCase();
            });
        });
    }

    function withName(name, detail) {
        detail = String(detail || '').trim();
        if (!name) return detail;
        if (!detail) return name;
        return name + ', ' + detail;
    }

    function asSentence(text) {
        var value = String(text || '').trim().replace(/\s+/g, ' ');
        if (!value) return '';
        return /[.!?]$/.test(value) ? value : value + '.';
    }

    function stripNamePrefix(message, rawName) {
        var text = String(message || '').trim();
        if (!text || !rawName) return text;

        var prefixes = ['Siswa atas nama ' + rawName, 'Guru atas nama ' + rawName];
        for (var i = 0; i < prefixes.length; i++) {
            if (text.toLowerCase().indexOf(prefixes[i].toLowerCase()) === 0) {
                return text.slice(prefixes[i].length).trim();
            }
        }

        return text;
    }

    function isUnrecognizedQrMessage(message) {
        var value = String(message || '').toLowerCase();
        return value.indexOf('tidak dikenali') !== -1 ||
               value.indexOf('tidak ditemukan') !== -1 ||
               value.indexOf('tidak valid') !== -1;
    }

    function buildAnnouncement(status, body) {
        body = body || {};

        var student = body.student || body.teacher || {};
        var rawName = student.name || '';
        var name = rawName ? titleCase(rawName) : '';
        var message = String(body.message || '').trim();
        var lower = message.toLowerCase();

        if (status === 200 && body.success) {
            if (body.type === 'completed') return asSentence(withName(name, 'sudah presensi masuk dan pulang'));
            if (body.is_late) return asSentence(withName(name, 'terlambat'));
            if (body.type === 'check_out') return asSentence(withName(name, 'presensi pulang berhasil'));
            return asSentence(withName(name, 'hadir'));
        }

        if (name) {
            if (lower.indexOf('belum presensi masuk') !== -1) {
                return asSentence(withName(name, 'belum presensi masuk hari ini'));
            }

            if (lower.indexOf('sudah melakukan presensi') !== -1 ||
                lower.indexOf('baru saja melakukan presensi masuk') !== -1) {
                return asSentence(withName(name, 'sudah presensi'));
            }

            if (lower.indexOf('tidak ada jadwal presensi') !== -1) {
                return asSentence(withName(name, 'hari ini tidak ada jadwal presensi'));
            }

            return asSentence(withName(name, stripNamePrefix(message, rawName) || 'presensi gagal'));
        }

        if (lower.indexOf('tidak ada jadwal presensi') !== -1) {
            return 'Hari ini tidak ada jadwal presensi.';
        }

        if (status === 404 || isUnrecognizedQrMessage(message)) {
            return 'QR code tidak dikenali.';
        }

        if (message) return asSentence(message);
        return 'Terjadi kendala koneksi ke server, coba lagi.';
    }

    function getBeep() {
        return document.getElementById('beepSound');
    }

    function playBeepOnly() {
        beepToken++;

        var beep = getBeep();
        if (!beep) return;

        try {
            beep.muted = false;
            beep.currentTime = 0;
            var promise = beep.play();
            if (promise && typeof promise.catch === 'function') {
                promise.catch(function () {});
            }
        } catch (e) {}
    }

    function playBeepThenSpeak(text) {
        var token = ++beepToken;
        var beep = getBeep();

        if (!beep) {
            speak(text);
            return;
        }

        var finished = false;
        var timer = null;

        function finish() {
            if (finished) return;
            finished = true;

            if (timer) {
                clearTimeout(timer);
                timer = null;
            }

            try { beep.removeEventListener('ended', finish, false); } catch (e) {}

            if (token === beepToken) speak(text);
        }

        try {
            beep.muted = false;
            beep.currentTime = 0;
            beep.addEventListener('ended', finish, false);
            timer = window.setTimeout(finish, BEEP_END_TIMEOUT_MS);

            var promise = beep.play();
            if (promise && typeof promise.catch === 'function') {
                promise.catch(finish);
            }
        } catch (e) {
            finish();
        }
    }

    function speak(text, delayMs) {
        if (!supported || muted) return;

        var message = String(text || '').trim();
        if (!message) return;

        var token = ++speakToken;
        var delay = Math.max(0, Number(delayMs) || 40);

        try {
            synth.cancel();
            window.setTimeout(function () {
                if (token !== speakToken || muted) return;

                try {
                    var utterance = new window.SpeechSynthesisUtterance(message);
                    utterance.lang = 'id-ID';
                    utterance.rate = 0.95;
                    utterance.pitch = 1;
                    utterance.volume = 1;

                    if (voice) utterance.voice = voice;

                    synth.speak(utterance);
                } catch (e) {}
            }, delay);
        } catch (e) {}
    }

    function unlockSpeech() {
        if (!supported || unlocked) return;
        unlocked = true;
        refreshVoices();

        try {
            var utterance = new window.SpeechSynthesisUtterance(' ');
            utterance.lang = 'id-ID';
            utterance.volume = 0;
            synth.speak(utterance);
        } catch (e) {}
    }

    window.addEventListener('pointerdown', unlockSpeech, { once: true, passive: true });
    window.addEventListener('keydown', unlockSpeech, { once: true });

    function updateToggleButton() {
        var button = document.getElementById('btnSoundToggle');
        if (!button) return;

        var icon = button.querySelector('i');
        var label = muted ? 'Aktifkan Suara' : 'Matikan Suara';

        if (icon) icon.className = muted ? 'bx bx-volume-mute' : 'bx bx-volume-full';
        button.title = label;
        button.setAttribute('aria-label', label);
        button.setAttribute('aria-pressed', muted ? 'true' : 'false');
        button.classList.toggle('is-muted', muted);
    }

    function setMuted(next) {
        muted = !!next;
        beepToken++;

        try {
            window.localStorage.setItem(MUTE_STORAGE_KEY, muted ? '1' : '0');
        } catch (e) {}

        if (muted) {
            speakToken++;
            try { if (supported) synth.cancel(); } catch (e) {}
        } else {
            unlockSpeech();
            speak('Suara diaktifkan.');
        }

        updateToggleButton();
    }

    function toggleMuted() {
        setMuted(!muted);
    }

    function bindControls() {
        var muteButton = document.getElementById('btnSoundToggle');
        if (muteButton && muteButton.getAttribute('data-speech-bound') !== '1') {
            muteButton.setAttribute('data-speech-bound', '1');
            muteButton.addEventListener('click', toggleMuted);
        }

        updateToggleButton();
    }

    function announceScan(status, body) {
        if (muted) return true;
        if (!supported) return false;
        if (status === null || status === undefined) return false;

        var text = buildAnnouncement(status, body);
        if (!text) return false;

        if (muted) {
            playBeepOnly();
        } else {
            playBeepThenSpeak(text);
        }

        return true;
    }

    bindControls();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindControls);
    }

    document.addEventListener('click', function (event) {
        // Panel Settings Sudah Dihapus - abaikan klik di luar
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') return;
    });

    window.KioskSpeech = {
        supported: supported,
        speak: speak,
        announceScan: announceScan,
        buildAnnouncement: buildAnnouncement,
        unlock: unlockSpeech,
        isMuted: function () { return muted; },
        setMuted: setMuted,
        toggleMuted: toggleMuted,
        getVoices: function () { return idVoices.slice(); },
        getResolvedVoice: function () { return voice; },
        getRate: function () { return 0.95; },
        isBeepBeforeSpeech: function () { return false; },
        refreshVoices: refreshVoices
    };

    window.speak = speak;
})();