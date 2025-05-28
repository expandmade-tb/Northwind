<?php

namespace helper;

/**
 * Language Helper
 * Version 1.1.1
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

class Language {
    private static ?Language $instance = null;

    private string $locale;
    private array $i18n;
    
    protected function __construct (string $locale) {    
        $this->detect_lang($locale);
        $this->i18n = [];
    }
        
    /**
     * Builds a filename and checks if the file does exist.
     * {filename}._locale, i.e. "form._en"
     *
     * @param string $location the location of the file
     * @param string $filename the filename (wihtout the language suffix) 
     * @param string $locale locale to use
     *
     * @return mixed string "{xxxx.}_en" | false
     */
    public static function filename (string $filename='translations', string $location='i18n', string $locale='') {
        $path = $location == 'i18n' ? APP.'/'.$location : $location;

        if ( empty($locale) )
            if ( empty(self::instance()->locale() ))
                $loc =  self::instance()->detect_lang();
            else
                $loc = self::instance()->locale();
        else
            $loc = $locale;

        $file = "$path/$filename._$loc";

        if ( !file_exists($file) )
                return false;

        return $file;
    }

    /**
     * tries to detect the locale based on header and cookie settings
     *
     * @param string  $default default locale
     *
     * @return string the locale
     */
    public function detect_lang(string $default='') : string {
        if ( isset($_COOKIE["language"]) ) {
            $this->locale = $_COOKIE["language"];
            return $this->locale;
        }

        if ( !empty($default) ) {
            $this->locale = $default;
            return $this->locale;  
        }

        $locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        if ( $locale === false)
            $locale = 'en_US';

        $lang = strtok($locale,'_');

        if ( $lang == false )
            $lang =$locale;
                        
        $this->locale = $lang;
        return $lang;
    }

    /**
     * loads a language translation file.
     * 
     * @param string $location the location of the file
     * @param string $filename the filename (wihtout the language suffix) 
     *
     * @return Language self
     */
    public function load(string $location='i18n', string $filename='translations') : Language{
        $this->i18n = [];
        $file = self::filename($filename, $location);

        if ( $file === false )
            /* @phpstan-ignore-next-line (we wont be here w/o instance) */
            return self::$instance;

        $this->i18n = require($file);
        /* @phpstan-ignore-next-line (we wont be here w/o instance) */
        return self::$instance;
    }

    /**
     * adds a language translation file to already existing translation
     * 
     * @param string $location the location of the file
     * @param string $filename the filename (wihtout the language suffix) 
     *
     * @return self
     */
    public function add(string $location='', string $filename='') {
        $file = self::filename($filename, $location);

        if ( $file === false )
            /* @phpstan-ignore-next-line (we wont be here w/o instance) */
            return self::$instance;

        $add_lang = require($file);
        $this->i18n = array_merge($this->i18n, $add_lang);
        /* @phpstan-ignore-next-line (we wont be here w/o instance) */
        return self::$instance;
    }
    
    /**
     * creates / returns an instance of the Language class
     *
     * @param string $locale The locale to translate. If empty, the locale will be automatically detected
     *
     * @return Language self
     */
    public static function instance (string $locale='' ) : Language{
        if ( self::$instance == null )
            self::$instance = new Language($locale);
   
        return self::$instance;
    }
    
    /**
     * returnts the translation string
     *
     * @param string $key the keyword(s) to translate
     *
     * @return string The translation. If nothing could be found, returns the $key 
     */
    public function lang(string $key) : string {
        if ( isset($this->i18n[$key]) )
            return $this->i18n[$key];
        else
            return $key;
    } 
       
    /**
     * returns the locale set
     *
     * @return string the locale
     */
    public function locale() : string {
        return $this->locale;
    }

    public function lang_replace(string $search, string $subject) : string {
        $replace = $this->lang($search);
        return str_replace($search, $replace, $subject);
    }

    public function translate(string|array $list) : string | array {
        $result= [];

        if ( is_string($list) )
            $a = array_map('trim',explode(',', $list));
        else
            $a = $list;

        foreach ($a as $key => $value)
            $result[$key] = $this->lang($value);

        if ( is_string($list) )
            return implode(',', $result);
        else
            return $result;
    }
}