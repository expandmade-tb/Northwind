<?php

namespace helper;

/**
 * encode/decode method parameters in an MVC url as a single hex string
 * Version 2.0.1
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

 class UrlVars {
     const CIPHERING = "aes-256-gcm";
     const IV_LENGTH = 12;
     const TAG_LENGTH = 16;
 
     const H_IDENT = 'i';  // Header identifier key
     const H_VALID = 'v';  // Header expiry key
 
     private array $vars = [];
     private string $secret = '395bcc259092e178cd79';
     private string $header_ident = 'eae40352fa4e';
     private int $header_valid = 10;
 
     /**
      * Sets the identifier and validity duration for secure headers.
      *
      * @param string $ident Identifier string to validate encoded payloads.
      * @param int $valid Validity duration in seconds (e.g. 86400 = 1 day).
      * @return $this
      */
     public function set_header(string $ident, int $valid) {
         $this->header_ident = $ident;
         $this->header_valid = $valid;
         return $this;
     }
 
     /**
      * Gets a value from the decoded payload.
      *
      * @param string $var Key name in the decoded data.
      * @param string|null $default Default value if key not found.
      * @return string Value of the key or default.
      */
     public function get(string $var, ?string $default = null): string {
         return $this->vars[$var] ?? $default;
     }
 
     /**
      * Sets the shared secret used to encrypt and decrypt payloads.
      *
      * @param string $secret Secret encryption key (should be 32 bytes for AES-256).
      * @return $this
      */
     public function set_secret(string $secret) {
         $this->secret = $secret;
         return $this;
     }
 
     /**
      * Encodes parameters into a compact, encrypted, URL-safe string.
      *
      * @param array $param Associative array of parameters to encode.
      * @param bool $secure_header Whether to include a time-limited header (identifier + expiry).
      * @return string|false Base64url-encoded encrypted string, or false on failure.
      */
     public function encode(array $param, bool $secure_header = false): string|false {
         if ($secure_header) {
             $this->vars = array_merge([
                 self::H_IDENT => $this->header_ident,
                 self::H_VALID => time() + $this->header_valid
             ], $param);
         } else {
             $this->vars = $param;
         }
 
         $plaintext = json_encode($this->vars);
         if ($plaintext === false) return false;
 
         $iv = random_bytes(self::IV_LENGTH);
         $tag = '';
 
         $ciphertext = openssl_encrypt(
             $plaintext,
             self::CIPHERING,
             $this->secret,
             OPENSSL_RAW_DATA,
             $iv,
             $tag,
             '',
             self::TAG_LENGTH
         );
 
         if ($ciphertext === false || empty($tag) ) return false;
 
         $combined = $iv . $tag . $ciphertext;
         return rtrim(strtr(base64_encode($combined), '+/', '-_'), '=');
     }
 
     /**
      * Decodes a previously encoded string and validates headers if present.
      *
      * @param string $param The base64url-encoded encrypted string.
      * @return array|false Associative array of decoded values, or false on failure.
      */
     public function decode(string $param): array|false {
         $padded = str_pad(strtr($param, '-_', '+/'), strlen($param) % 4 === 0 ? strlen($param) : strlen($param) + 4 - strlen($param) % 4, '=', STR_PAD_RIGHT);
         $binary = base64_decode($padded);
         if ($binary == false) return false;
 
         $iv = substr($binary, 0, self::IV_LENGTH);
         $tag = substr($binary, self::IV_LENGTH, self::TAG_LENGTH);
         $ciphertext = substr($binary, self::IV_LENGTH + self::TAG_LENGTH);
 
         if (strlen($iv) !== self::IV_LENGTH || strlen($tag) !== self::TAG_LENGTH) return false;
 
         $plaintext = openssl_decrypt(
             $ciphertext,
             self::CIPHERING,
             $this->secret,
             OPENSSL_RAW_DATA,
             $iv,
             $tag
         );
 
         if ($plaintext === false) return false;
 
         $jparam = json_decode($plaintext, true);
         if (!is_array($jparam)) return false;
 
         // Validate identifier
         if (isset($jparam[self::H_IDENT]) && $jparam[self::H_IDENT] !== $this->header_ident)
             return false;
         unset($jparam[self::H_IDENT]);
 
         // Validate expiry
         if (isset($jparam[self::H_VALID]) && time() > $jparam[self::H_VALID])
             return false;
         unset($jparam[self::H_VALID]);
 
         $this->vars = $jparam;
         return $jparam;
     }
 }