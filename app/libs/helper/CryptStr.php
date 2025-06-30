<?php

/**
 * Version 2.0.0
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace helper;

class CryptStr {
    private const VERSION = 'v2';
    private const ENCRYPTION_ALGORITHM_V2 = 'aes-256-gcm';
    private const HASHING_ALGORITHM = 'sha256';
    private const PBKDF2_ITERATIONS = 100_000;
    private const PBKDF2_SALT = '034f55af5c79935e17b2'; // Secure, static
    private const KEY_LENGTH = 32;
    private const IV_LENGTH_V2 = 12;
    private const TAG_LENGTH = 16;

    private string $secret;

    // Cache for derived keys
    private ?string $derivedKeyV2 = null;

    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    private function deriveKeyV2(): string {
        if ($this->derivedKeyV2 === null) {
            $this->derivedKeyV2 = hash_pbkdf2(
                self::HASHING_ALGORITHM,
                $this->secret,
                self::PBKDF2_SALT,
                self::PBKDF2_ITERATIONS,
                self::KEY_LENGTH,
                true
            );
        }
        return $this->derivedKeyV2;
    }

    public function encrypt(string $input): string|false {
        $key = $this->deriveKeyV2();
        $iv = random_bytes(self::IV_LENGTH_V2);
        $tag = '';

        $cipherText = openssl_encrypt(
            $input,
            self::ENCRYPTION_ALGORITHM_V2,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            self::VERSION,
            self::TAG_LENGTH
        );

        if ($cipherText === false) {
            return false;
        }

        $data = bin2hex($iv . $tag . $cipherText);
        return self::VERSION . ':' . $data;
    }

    public function decrypt(string $input): string|false {
        // Detect version by prefix
        if (str_starts_with($input, self::VERSION . ':')) {
            return $this->decryptV2($input);
        }

        // Fallback to legacy decryption
        return $this->decryptV1($input);
    }

    private function decryptV2(string $input): string|false {
        [$version, $hex] = explode(':', $input, 2) + [null, null];
        if ($version !== self::VERSION || $hex === null || strlen($hex) % 2 !== 0 || !ctype_xdigit($hex)) {
            return false;
        }

        $binaryInput = hex2bin($hex);
        if ($binaryInput === false) {
            return false;
        }

        $iv = substr($binaryInput, 0, self::IV_LENGTH_V2);
        $tag = substr($binaryInput, self::IV_LENGTH_V2, self::TAG_LENGTH);
        $cipherText = substr($binaryInput, self::IV_LENGTH_V2 + self::TAG_LENGTH);

        $key = $this->deriveKeyV2();

        return openssl_decrypt(
            $cipherText,
            self::ENCRYPTION_ALGORITHM_V2,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $version
        );
    }

    private function decryptV1(string $input): string|false {
        $crypt = CryptStr_V1::instance($this->secret);
        $result = $crypt->decrypt($input);
        return $result;
    }
}