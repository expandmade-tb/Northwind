<?php

namespace helper;

class CryptStrSingleton {
    private static array $instances = [];

    /**
     * Get the singleton CryptStr instance for a given secret
     */
    public static function getInstance(string $secret): CryptStr {
        if (!isset(self::$instances[$secret])) {
            self::$instances[$secret] = new CryptStr($secret);
        }
        return self::$instances[$secret];
    }

    /**
     * Optional: Clear all cached instances (for testing or reloading)
     */
    public static function reset(): void {
        self::$instances = [];
    }
}