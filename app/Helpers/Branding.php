<?php

class Branding
{
    public static function getLogoPath(): ?string
    {
        $configured = trim((string)AppConfig::getSetting('church_logo', ''));
        if ($configured !== '' && defined('ROOT_PATH') && file_exists(ROOT_PATH . '/' . ltrim($configured, '/'))) {
            return ltrim($configured, '/');
        }

        $fallbacks = [
            'public/assets/img/logo.png',
            'public/images/logo.png'
        ];

        foreach ($fallbacks as $candidate) {
            if (defined('ROOT_PATH') && file_exists(ROOT_PATH . '/' . $candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
