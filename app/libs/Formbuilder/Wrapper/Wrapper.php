<?php

namespace Formbuilder\Wrapper;

use Exception;

class Wrapper {
    private static bool $is_loaded = false;
    private static array $elements_array = [];
    private static array $element_parts_array = [];

    public static function factory (string $name='bootstrap') : void {
        if ( self::$is_loaded === false ) {
            self::$is_loaded = true;
            $array = require $name.'.php';
            self::$elements_array = $array["elements"];
            self::$element_parts_array = $array["element_parts"];
        }
    }

    public static function elements (string $key, string $name='', string $label='', string $id='', string $value='', string $attribute='', string $options='', string $row='', string $col='', string $min='', string $max='', string $step='') : string {
        if ( self::$is_loaded === false )
            throw new Exception('Wrapper not initialized');
        
        if ( isset(self::$elements_array[$key]) === false )
            return '';
            
        $result = self::$elements_array[$key];

        if ( strpos($attribute, 'class=') !== false ) {
            $pattern = "/\[:class-ovwr\]\s?class\s?=\s?([\"'])(.*?)\\1/";
            $result = preg_replace($pattern, $attribute,  $result);
        }

        return str_replace(['[:name]','[:label]','[:id]','[:value]','[:attributes]','[:options]','[:row]','[:col]','[:class-ovwr]','[:min]','[:max]','[:step]'],
                             [$name,$label,$id,$value,$attribute,$options,$row,$col,'',$min,$max,$step],$result);
    }

    public static function element_parts (string $key, string $name='', string $label='', string $id='', string $value='', string $attribute='', string $options='', string $row='', string $col='', string $min='', string $max='', string $step='') : string {
        if ( self::$is_loaded === false )
            throw new Exception('Wrapper not initialized');

        if ( isset(self::$element_parts_array[$key]) === false )
            return '';
            
        $result = self::$element_parts_array[$key];

        if ( strpos($attribute, 'class=') !== false ) {
            $pattern = "/\[:class-ovwr\]\s?class\s?=\s?([\"'])(.*?)\\1/";
            $result = preg_replace($pattern, $attribute,  $result);
            $attribute='';
        }

        return str_replace(['[:name]','[:label]','[:id]','[:value]','[:attributes]','[:options]','[:row]','[:col]','[:class-ovwr]'],
                             [$name,$label,$id,$value,$attribute,$options,$row,$col,'',$min,$max,$step],$result);
    }

    public static function classes (string $key, string $classes='bootstrap') : string {
        $array = require "classes.php";
        return $array[$classes][$key]??'';
    }
}  
