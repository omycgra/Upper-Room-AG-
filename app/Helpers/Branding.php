<?php

class Branding
{
    public static function mediaUrl(string $storedPath): string
    {
        $p = trim(str_replace('\\', '/', $storedPath));
        if ($p === '') return '';

        if (preg_match('#^https?://#i', $p)) {
            return $p;
        }

        $p = ltrim($p, '/');
        $posPublic = strpos($p, 'public/uploads/');
        if ($posPublic !== false) {
            $p = substr($p, $posPublic);
        } else {
            $posUploads = strpos($p, 'uploads/');
            if ($posUploads !== false) {
                $p = 'public/' . substr($p, $posUploads);
            }
        }

        $localCandidate = $p;
        if (defined('ROOT_PATH') && $localCandidate !== '') {
            $full = ROOT_PATH . '/' . $localCandidate;
            if (file_exists($full)) {
                return rtrim((string)BASE_URL, '/') . '/' . $localCandidate;
            }
        }

        $supabaseUrl = trim((string)Env::get('SUPABASE_URL', ''));
        $bucket = trim((string)Env::get('SUPABASE_STORAGE_BUCKET', ''));
        if ($bucket === '') $bucket = 'uploads';
        if ($supabaseUrl !== '') {
            if (strpos($p, 'public/uploads/') === 0) {
                $objectPath = substr($p, strlen('public/uploads/'));
                if ($objectPath !== '') {
                    $encodedPath = implode('/', array_map('rawurlencode', array_filter(explode('/', $objectPath), 'strlen')));
                    return rtrim($supabaseUrl, '/') . '/storage/v1/object/public/' . rawurlencode($bucket) . '/' . $encodedPath;
                }
            }
            if (strpos($p, 'uploads/') === 0) {
                $objectPath = substr($p, strlen('uploads/'));
                if ($objectPath !== '') {
                    $encodedPath = implode('/', array_map('rawurlencode', array_filter(explode('/', $objectPath), 'strlen')));
                    return rtrim($supabaseUrl, '/') . '/storage/v1/object/public/' . rawurlencode($bucket) . '/' . $encodedPath;
                }
            }
        }

        return rtrim((string)BASE_URL, '/') . '/' . $p;
    }

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
