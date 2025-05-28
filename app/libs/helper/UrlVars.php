<?php

namespace helper;

/**
 * encode/decode method parameters in an MVC url as a single hex string
 * Version 1.0.2
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

class UrlVars {
    const CIPHERING = "AES-128-CTR";
    const IV        = '3543ca5c5575842e';
    const H_IDENT   = 'h__ident';
    const H_VALID   = 'h__valid';

    private array $vars = [];
    private string $secret;
    private string $header_ident='eae40352fa4e';
    private int $header_valid=10;
        
    /**
     * set header values
     *
     * @param string $ident header identification
     * @param int $valid duration in seconds until the header is invalid
     *
     * @return $this
     */
    public function set_header (string $ident, int $valid) {
        $this->header_ident = $ident;
        $this->header_valid = $valid;
        return $this;
    }

    /**
     * gets the contents of a variable
     *
     * @param string $var name of the variable
     * @param string $default default value if the variable isnt set
     *
     * @return string the value of the variable
     */
    public function get(string $var, string $default = null) : string {
        return $this->vars[$var]??$default;
    }
    
    /**
     * sets the secret key to encrypt/decrypt the url vars
     *
     * @param string $secret the secret key
     *
     * @return $this
     */
    public function set_secret(string $secret) {
        $this->secret = $secret;
        return $this;
    }
    
    /**
     * encodes the url parameter(s)
     *
     * @param array $param the url parameter(s)
     * @param bool $secure_header add a secure header
     *
     * @return mixed string | false
     */
    public function encode(array $param, bool $secure_header=false) {
        // add a header to the array to encode
        if ( $secure_header )
            $this->vars = array_merge([UrlVars::H_IDENT=>$this->header_ident,UrlVars::H_VALID=>time() + $this->header_valid], $param);
        else
            $this->vars = $param;
        
        // encode as a json string
        $jparam = json_encode($this->vars);

        if ( $jparam === false )
            return false;

        if ( !empty($this->secret) ) {  // ssl encrypt if a secret was set and compress the string

            $sparam = openssl_encrypt($jparam, UrlVars::CIPHERING, $this->secret, 0, UrlVars::IV);

            if ( $sparam === false )
                return false;

            $cparam = gzdeflate($sparam, 9);
        }
        else // just compress the string
            $cparam = gzdeflate($jparam, 9);

        if ( $cparam === false )
            return false;

        // finally build the hex string
        $hparam = bin2hex($cparam);
        return $hparam; 
    }
        
    /**
     * decodes the url parameter(s)
     *
     * @param string $param the url parameter(s)
     *
     * @return mixed array | false
     */
    public function decode(string $param) {
        // is this a valid hex string ?
        if (strlen($param) % 2 || ! ctype_xdigit($param))
            return false;

        $hparam = hex2bin($param);

        if ( $hparam === false )
            return false;

        $gparam = gzinflate($hparam);

        if ( $gparam === false )
            return false;

        // decrypt the string if a secret was set
        if ( !empty($this->secret) ) {
            $sparam = openssl_decrypt($gparam, UrlVars::CIPHERING, $this->secret, 0, UrlVars::IV);

            if ( $sparam === false )
                return false;

            $jparam = json_decode($sparam, true);
        }
        else
            $jparam = json_decode($gparam, true);

        if ( is_null($jparam) )
            return false;
            
        // if there is an identifier set, check and remove it
        if ( isset($jparam[UrlVars::H_IDENT]) && $jparam[UrlVars::H_IDENT] != $this->header_ident )
            return false;
        else
            unset($jparam[UrlVars::H_IDENT]);

        // if there is a time validation set, check and remove it
        if ( isset($jparam[UrlVars::H_VALID]) && (time() > $jparam[UrlVars::H_VALID]) )
            return false;
        else
            unset($jparam[UrlVars::H_VALID]);

        $this->vars = $jparam;
        return $jparam;
    }
}