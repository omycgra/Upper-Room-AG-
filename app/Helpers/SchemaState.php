<?php

class SchemaState
{
    private static $requestCache = [];

    public static function once(string $key, callable $callback, int $ttlSeconds = 86400): void
    {
        if (isset(self::$requestCache[$key])) {
            return;
        }

        $sessionKey = self::sessionKey($key);
        $state = Session::get($sessionKey);
        $checkedAt = is_array($state) ? (int)($state['checked_at'] ?? 0) : 0;

        if ($checkedAt > 0 && (time() - $checkedAt) < $ttlSeconds) {
            self::$requestCache[$key] = true;
            return;
        }

        $callback();

        Session::set($sessionKey, ['checked_at' => time()]);
        self::$requestCache[$key] = true;
    }

    public static function invalidate(string $key): void
    {
        unset(self::$requestCache[$key]);
        Session::remove(self::sessionKey($key));
    }

    private static function sessionKey(string $key): string
    {
        return 'schema_state_' . preg_replace('/[^a-z0-9_]+/i', '_', strtolower($key));
    }
}
