<?php

/**
 * Home Controller
 * Version 1.0.1
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace controller;

use helper\Helper;
use models\visitors_model;

class Home extends BaseController {
    function __construct() {
        parent::__construct();
    }

    private function request_geo_location(string $ip) : array | false {
        $headers = [
            'Accept: application/json',
        ];
    
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_URL , "http://ip-api.com/json/{$ip}"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);

        if ( curl_errno($ch) )
            return false;
        else
            return json_decode($response, true);
    }

    public function index () : void {
        $ip = Helper::get_ip_addr();

        if ($ip === '127.0.0.1' || $ip === '::1')
            $skip_log = true;
        else {
            $result = $this->request_geo_location($ip);
            $skip_log = $_SERVER['HTTP_X_ADDED_OPTION']??'' == 'skip-log';
        }

        if ( $skip_log == false ) {
            $visitor = new visitors_model();
            $result['ip'] = $ip;
            $result['visited'] = time();
            $visitor->insert($visitor->map($result));
        }

        $this->view('Home');
    }
}