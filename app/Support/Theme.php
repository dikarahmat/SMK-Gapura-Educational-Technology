<?php

namespace App\Support;

class Theme
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    public function primary(): string
    {
        return ltrim((string) $this->get('primary', '#2563eb'), '#');
    }

    public function secondary(): string
    {
        return ltrim((string) $this->get('secondary', '#7e22ce'), '#');
    }

    public function secondaryArgb(): string
    {
        return 'FF' . $this->secondary();
    }

    public function accent(): string
    {
        return ltrim((string) $this->get('accent', '#06b6d4'), '#');
    }

    public function status(string $key): string
    {
        return ltrim((string) $this->get("status.{$key}", '#6b7280'), '#');
    }

    public function statuses(): array
    {
        $statuses = $this->get('status', []);
        $out = [];
        foreach ($statuses as $key => $value) {
            $out[$key] = ltrim((string) $value, '#');
        }
        return $out;
    }

    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function rgbToHex(array $rgb): string
    {
        return sprintf('%02x%02x%02x', max(0, min(255, (int) $rgb[0])), max(0, min(255, (int) $rgb[1])), max(0, min(255, (int) $rgb[2])));
    }

    public static function mix(string $hex, float $amount): string
    {
        $rgb = self::hexToRgb($hex);
        $white = [255, 255, 255];
        $black = [0, 0, 0];

        if ($amount >= 0) {
            $result = [
                $rgb[0] + ($white[0] - $rgb[0]) * $amount,
                $rgb[1] + ($white[1] - $rgb[1]) * $amount,
                $rgb[2] + ($white[2] - $rgb[2]) * $amount,
            ];
        } else {
            $amount = abs($amount);
            $result = [
                $rgb[0] - $rgb[0] * $amount,
                $rgb[1] - $rgb[1] * $amount,
                $rgb[2] - $rgb[2] * $amount,
            ];
        }

        return self::rgbToHex($result);
    }

    public function scale(): array
    {
        $primary = $this->primary();
        $scale = [
            '50'  => self::mix($primary, 0.92),
            '100' => self::mix($primary, 0.80),
            '200' => self::mix($primary, 0.60),
            '300' => self::mix($primary, 0.40),
            '400' => self::mix($primary, 0.25),
            '500' => $primary,
            '600' => self::mix($primary, -0.15),
            '700' => self::mix($primary, -0.30),
            '800' => self::mix($primary, -0.45),
            '900' => self::mix($primary, -0.60),
        ];
        $scale['hover'] = self::mix($primary, -0.12);
        return $scale;
    }

    public static function contrastText(string $hex): string
    {
        $rgb = self::hexToRgb($hex);
        $lum = (0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2]) / 255;
        return $lum > 0.5 ? '000000' : 'ffffff';
    }

    public function onPrimary(): string
    {
        $rgb = self::hexToRgb($this->primary());
        $lum = (0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2]) / 255;
        return $lum > 0.5 ? '#0f172a' : '#ffffff';
    }

    public function onPrimaryCssVariable(): string
    {
        return 'var(--color-on-primary)';
    }

    /**
     * Resolver token: 'primary' | 'primary-dark' | 'primary-light' | '#hex'.
     * Mengembalikan hex akhir dengan leading #.
     */
    public function resolve(string $token): string
    {
        $token = trim($token);
        if ($token === 'primary') {
            return '#' . $this->primary();
        }
        if ($token === 'primary-dark') {
            return '#' . $this->scale()['700'];
        }
        if ($token === 'primary-light') {
            return '#' . $this->scale()['100'];
        }
        if (preg_match('/^primary-(\d+)$/', $token, $m)) {
            $scale = $this->scale();
            if (isset($scale[$m[1]])) {
                return '#' . $scale[$m[1]];
            }
        }
        if ($token === 'secondary') {
            return '#' . $this->secondary();
        }
        if (str_starts_with($token, 'action.')) {
            return '#' . $this->action(substr($token, 7));
        }
        if (str_starts_with($token, 'status.')) {
            return '#' . $this->status(substr($token, 7));
        }
        if (str_starts_with($token, 'kiosk.')) {
            return '#' . ltrim((string) $this->get($token, ''), '#');
        }
        if (str_contains($token, '.')) {
            $val = $this->get($token);
            if ($val !== null) {
                return (str_starts_with((string) $val, '#') ? (string) $val : '#' . ltrim((string) $val, '#'));
            }
        }
        if (str_starts_with($token, '#')) {
            // normalise to 6 digit
            $hex = ltrim($token, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            return '#' . strtolower($hex);
        }
        // fallback: treat as config key under buttons? or return as-is
        return $token;
    }

    public function primaryArgb(): string
    {
        return 'FF' . $this->primary();
    }

    public function primary700Argb(): string
    {
        return 'FF' . $this->scale()['700'];
    }

    public function action(string $key): string
    {
        return ltrim((string) $this->get("action.{$key}", '#059669'), '#');
    }

    public function actionArgb(string $key): string
    {
        return 'FF' . $this->action($key);
    }

    /**
     * Hasilkan daftar custom property CSS untuk :root.
     */
    public function cssVariables(): array
    {
        $vars = [];
        $scale = $this->scale();

        // Primary skala
        foreach ($scale as $key => $value) {
            $vars["--color-primary-{$key}"] = '#' . $value;
        }

        // Primary flat (alias ke 500)
        $vars['--color-primary'] = '#' . $scale['500'];
        $vars['--color-primary-hover'] = '#' . $scale['hover'];
        $vars['--color-primary-dark'] = '#' . $scale['700'];
        $vars['--color-primary-light'] = '#' . $scale['100'];
        $vars['--color-on-primary'] = $this->onPrimary();

        // rgb format "r, g, b"
        $rgb = self::hexToRgb($scale['500']);
        $vars['--color-primary-rgb'] = implode(', ', $rgb);
        $vars['--color-primary-rgb-hover'] = implode(', ', self::hexToRgb($scale['hover']));

        // Secondary & accent
        $vars['--color-secondary'] = '#' . $this->secondary();
        $vars['--color-secondary-hover'] = '#' . self::mix($this->secondary(), -0.12);
        $vars['--color-accent'] = '#' . $this->accent();
        $vars['--color-accent-hover'] = '#' . self::mix($this->accent(), -0.12);

        // Bootstrap mapping
        $vars['--bs-primary'] = '#' . $scale['500'];
        $vars['--bs-primary-rgb'] = implode(', ', $rgb);
        $vars['--bs-secondary'] = '#' . $this->secondary();
        $vars['--bs-accent'] = '#' . $this->accent();

        // Status
        foreach ($this->statuses() as $key => $value) {
            $vars["--status-{$key}"] = '#' . $value;
            $vars["--status-{$key}-bg"] = '#' . self::mix($value, 0.88);
            $vars["--status-{$key}-border"] = '#' . self::mix($value, 0.60);
            $vars["--status-{$key}-text"] = '#' . self::contrastText($value);
        }

        // Action
        foreach (array_keys($this->get('action', ['excel' => '#059669', 'pdf' => '#e11d48'])) as $ak) {
            $hex = $this->action($ak);
            $vars["--action-{$ak}"] = '#' . $hex;
            $vars["--action-{$ak}-hover"] = '#' . self::mix($hex, -0.12);
            $vars["--action-{$ak}-bg"] = '#' . self::mix($hex, 0.88);
            $vars["--action-{$ak}-border"] = '#' . self::mix($hex, 0.60);
            $rgbA = self::hexToRgb($hex);
            $vars["--action-{$ak}-rgb"] = implode(', ', $rgbA);
        }

        // Buttons with anti-kembar
        $buttons = $this->get('buttons', []);
        $resolved = [];
        foreach ($buttons as $key => $token) {
            $resolved[$key] = $this->resolve((string) $token);
        }
        // anti-kembar: add vs print_card
        if (isset($resolved['add'], $resolved['print_card']) && strtolower($resolved['add']) === strtolower($resolved['print_card'])) {
            $resolved['add'] = '#' . $this->scale()['700'];
        }
        // scanner vs gate
        if (isset($resolved['scanner'], $resolved['gate']) && strtolower($resolved['scanner']) === strtolower($resolved['gate'])) {
            $resolved['scanner'] = '#' . $this->scale()['700'];
        }

        foreach ($resolved as $key => $hex) {
            $h = ltrim($hex, '#');
            $vars["--btn-{$key}"] = '#' . $h;
            $vars["--btn-{$key}-hover"] = '#' . self::mix($h, -0.12);
            $rgbB = self::hexToRgb($h);
            $vars["--btn-{$key}-rgb"] = implode(', ', $rgbB);
        }

        // Shortcuts
        $shortcuts = $this->get('shortcuts', []);
        foreach ($shortcuts as $key => $token) {
            $hex = ltrim($this->resolve((string) $token), '#');
            $vars["--shortcut-{$key}"] = '#' . $hex;
            $rgbS = self::hexToRgb($hex);
            $vars["--shortcut-{$key}-bg"] = 'rgba(' . implode(', ', $rgbS) . ', 0.12)';
        }

        // On-primary text color for contrast
        $vars['--color-on-primary'] = $this->onPrimary();

        // Kiosk
        $vars['--kiosk-stripe'] = '#' . ltrim((string) $this->get('kiosk.stripe', '#f59e0b'), '#');

        return $vars;
    }
}
