<?php

class AppConfig
{
    private static $settings = null;

    public static function getSetting(string $key, $default = null)
    {
        if (self::$settings === null) {
            self::loadSettings();
        }

        return array_key_exists($key, self::$settings) ? self::$settings[$key] : $default;
    }

    public static function reset(): void
    {
        self::$settings = null;
    }

    private static function loadSettings(): void
    {
        self::$settings = [];

        try {
            $db = Database::getInstance();
            $rows = $db->fetchAll("SELECT key_name, value FROM settings");
            foreach ($rows as $row) {
                $name = (string)($row['key_name'] ?? '');
                if ($name === '') {
                    continue;
                }
                self::$settings[$name] = $row['value'] ?? null;
            }
        } catch (Throwable $e) {
            self::$settings = [];
        }
    }
}
