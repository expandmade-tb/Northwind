<?php

namespace helper;

/** 
 * Version 1.1.1
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

class CryptStr {
    protected static string $ENCRYPTION_ALGORITHM = 'AES-256-CBC';
    protected static string $HASHING_ALGORITHM = 'sha256';
    private static ?CryptStr $instance = null;
    protected string $secret = '826561346285b80e4e2a85ead0be37ac';

    protected function __construct ( string $secret) {    
        $this->secret = $secret;
    }

    public static function instance (string $secret) : CryptStr {
        if ( self::$instance == null )
            self::$instance = new CryptStr($secret);
        else
            if ( !empty($secret) )
                (self::$instance)->set_secret($secret);

        return self::$instance;
    }

    /**
     * Returns the current secret
     *
     * @return string
     */
    public function get_secret(): string {
        return $this->secret;
    }
       
    /**
     * sets the new secret
     *
     * @param string $secret the new secret to be set
     *
     * @return void
     */
    public function set_secret(string $secret) {
        $this->secret = $secret;
    }

    /**
     * Decrypts a string using the application secret.
     *
     * @param string $input hex representation of the cipher text
     *
     * @return string|false UTF-8 string containing the plain text input
     */
    public function decrypt(string $input): string|false {
        if (strlen($input) % 2 || ! ctype_xdigit($input)) // prevent decrypt failing when $input is not hex or has odd length
            return false;

        $binaryInput = hex2bin($input);  // we'll need the binary cipher

        if ( $binaryInput === false )
            return false;

        $iv = substr($binaryInput, 0, 16);
        $hash = substr($binaryInput, 16, 32);
        $cipherText = substr($binaryInput, 48);
        $key = hash(self::$HASHING_ALGORITHM, $this->secret, true);

        // if the HMAC hash doesn't match the hash string, something has gone wrong
        if (hash_hmac(self::$HASHING_ALGORITHM, $cipherText, $key, true) !== $hash) {
            return false;
        }

        return openssl_decrypt($cipherText, self::$ENCRYPTION_ALGORITHM, $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Encrypts a string using the application secret. This returns a hex representation of the binary cipher text
     *
     * @param string $input plain text input to encrypt
     *
     * @return string hex representation of the binary cipher text
     * @throws \Exception
     */
    public function encrypt(string $input) : string|false {
        $key = hash(self::$HASHING_ALGORITHM, $this->secret, true);
        $iv = random_bytes(16);
        $cipherText = openssl_encrypt($input, self::$ENCRYPTION_ALGORITHM, $key, OPENSSL_RAW_DATA, $iv);

        if ( $cipherText === false )
            return false;

        $hash = hash_hmac(self::$HASHING_ALGORITHM, $cipherText, $key, true);
        return bin2hex($iv . $hash . $cipherText);
    }
}