(function () {
    'use strict';

    var MUTE_STORAGE_KEY = 'kioskSpeechMuted';
    var BEEP_END_TIMEOUT_MS = 800;
    var PENDING_MAX_AGE_MS = 6000;

    var synth = window.speechSynthesis;
    var supported = !!(synth && window.SpeechSynthesisUtterance);

    // Default: suara AKTIF. Hanya mute kalau user sengaja mematikan (tersimpan '1').
    var muted = false;
    var idVoices = [];
    var voice = null;
    var unlocked = false;
    var speakToken = 0;
    var beepToken = 0;
    var pending = null; // { text, at } kalau speak() sempat ditolak browser

    try {
        muted = window.localStorage.getItem(MUTE_STORAGE_KEY) === '1';
    } catch (e) {}

    /* ================= VOICE (otomatis: Google / perempuan) ================= */
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

    /* ================= KALIMAT SUARA (TANPA NAMA SISWA) ================= */
    function asSentence(text) {
        var value = String(text || '').trim().replace(/\s+/g, ' ');
        if (!value) return '';
        value = value.charAt(0).toUpperCase() + value.slice(1);
        return /[.!?]$/.test(value) ? value : value + '.';
    }

    function escapeRegExp(str) {
        return String(str).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Buang nama siswa/guru dari pesan overlay supaya tidak ikut dibacakan.
    function removeName(message, rawName) {
        var text = String(message || '');

        if (rawName) {
            var re = new RegExp('(siswa|guru)?\\s*(atas nama)?\\s*' + escapeRegExp(rawName), 'ig');
            text = text.replace(re, ' ');
        }

        // Kalau masih ada "atas nama" (nama tidak diketahui), ambil mulai dari kata kerja statusnya.
        if (/atas nama/i.test(text)) {
            var m = text.match(/\b(sudah|belum|tidak|gagal|baru)\b[\s\S]*$/i);
            text = m ? m[0] : '';
        }

        return text.replace(/\s+/g, ' ').replace(/^[\s,.:;\-]+/, '').trim();
    }

    function isUnrecognizedQrMessage(message) {
        var value = String(message || '').toLowerCase();
        return value.indexOf('tidak dikenali') !== -1 ||
               value.indexOf('tidak ditemukan') !== -1 ||
               value.indexOf('tidak valid') !== -1;
    }

    // status = kode HTTP (200/404/dst atau null), body = JSON dari server.
    function buildAnnouncement(status, body) {
        body = body || {};

        var person = body.student || body.teacher || {};
        var rawName = String(person.name || '').trim();
        var message = String(body.message || '').trim();
        var lower = message.toLowerCase();
        var type = String(body.type || '').toLowerCase();

        // Berhasil
        if (status === 200 && body.success) {
            if (type === 'completed') return 'Sudah presensi masuk dan pulang.';
            if (body.is_late) return 'Terlambat.';
            if (type === 'check_out') return 'Presensi pulang berhasil.';
            return 'Hadir.';
        }

        // Libur (libur nasional / libur sekolah)
        if (body.is_holiday || type.indexOf('libur') !== -1 || type.indexOf('holiday') !== -1 ||
            /\blibur\b/.test(lower)) {
            return 'Hari ini libur, tidak ada presensi.';
        }

        // Sabtu / Minggu / tidak ada jadwal
        if (body.is_weekend || type === 'weekend' || type === 'sabtu' || type === 'minggu' ||
            lower.indexOf('tidak ada jadwal presensi') !== -1) {
            return 'Hari ini tidak ada jadwal presensi.';
        }

        // Belum presensi masuk
        if (lower.indexOf('belum presensi masuk') !== -1) {
            return 'Belum presensi masuk hari ini.';
        }

        // Sudah presensi / duplikat
        if (lower.indexOf('sudah melakukan presensi') !== -1 ||
            lower.indexOf('baru saja melakukan presensi') !== -1 ||
            lower.indexOf('sudah presensi') !== -1) {
            return 'Sudah presensi.';
        }

        // QR / kartu tidak dikenali
        if (status === 404 || isUnrecognizedQrMessage(message)) {
            return 'QR code tidak dikenali.';
        }

        // Fallback: bacakan pesan overlay, nama dibuang.
        var cleaned = removeName(message, rawName);
        if (cleaned) return asSentence(cleaned);

        if (!status || status >= 500) return 'Terjadi kendala koneksi ke server, coba lagi.';
        return 'Presensi gagal.';
    }

    /* ================= BEEP ================= */
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

    /* ================= TTS ================= */
    function speak(text, delayMs) {
        if (!supported || muted) return;

        var message = String(text || '').trim();
        if (!message) return;

        if (!voice) refreshVoices();

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

                    // Browser menolak karena belum ada interaksi: simpan, ucapkan saat unlock.
                    utterance.onerror = function (ev) {
                        if (ev && ev.error === 'not-allowed') {
                            pending = { text: message, at: Date.now() };
                        }
                    };

                    synth.speak(utterance);
                } catch (e) {}
            }, delay);
        } catch (e) {}
    }

    function flushPending() {
        if (!pending) return;
        var item = pending;
        pending = null;
        if (muted) return;
        if (Date.now() - item.at > PENDING_MAX_AGE_MS) return;
        speak(item.text, 0);
    }

    /* ================= UNLOCK OTOMATIS ================= */
    var UNLOCK_EVENTS = ['pointerdown', 'touchstart', 'keydown', 'click'];

    function removeUnlockListeners() {
        for (var i = 0; i < UNLOCK_EVENTS.length; i++) {
            document.removeEventListener(UNLOCK_EVENTS[i], unlockSpeech, true);
        }
    }

    function unlockSpeech() {
        if (!supported) return;
        if (unlocked) { flushPending(); return; }

        unlocked = true;
        removeUnlockListeners();
        refreshVoices();

        try {
            var utterance = new window.SpeechSynthesisUtterance(' ');
            utterance.lang = 'id-ID';
            utterance.volume = 0;
            synth.speak(utterance);
        } catch (e) {}

        flushPending();
    }

    // Interaksi pertama di mana saja (klik, sentuh, keyboard, tombol Layar Penuh) membuka kunci.
    for (var i = 0; i < UNLOCK_EVENTS.length; i++) {
        document.addEventListener(UNLOCK_EVENTS[i], unlockSpeech, { capture: true, passive: true });
    }

    // Kalau halaman sudah pernah diberi interaksi (reload, flag Chrome), langsung unlock.
    function tryAutoUnlock() {
        try {
            if (navigator.userActivation && navigator.userActivation.hasBeenActive) {
                unlockSpeech();
            }
        } catch (e) {}
    }

    tryAutoUnlock();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryAutoUnlock);
    }

    /* ================= TOMBOL SPEAKER (mute / unmute) ================= */
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
            pending = null;
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

    bindControls();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindControls);
    }

    /* ================= TITIK MASUK DARI scanner.js ================= */
    // Beep SELALU bunyi. Speaker aktif = beep lalu TTS. Speaker mute = beep saja.
    function announceScan(status, body) {
        // Kode kosong / tanpa hasil: beep saja
        if (status === null || status === undefined) {
            playBeepOnly();
            return true;
        }

        var text = buildAnnouncement(status, body);

        if (muted || !supported || !text) {
            playBeepOnly();
            return true;
        }

        playBeepThenSpeak(text);
        return true;
    }

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
        isBeepBeforeSpeech: function () { return true; },
        refreshVoices: refreshVoices
    };

    window.speak = speak;
})();