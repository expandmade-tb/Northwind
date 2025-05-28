<?php

namespace controller;

use Formbuilder\StatelessCSRF;
use helper\Helper;

class RequestController {

    protected function validate_token(string $token) : bool {
        $csrf_generator = new StatelessCSRF(helper::env('app_secret', 'empty_secret'));
        $csrf_generator->setGlueData('ip', $_SERVER['REMOTE_ADDR']);
        $csrf_generator->setGlueData('user-agent', $_SERVER['HTTP_USER_AGENT']);            
        $result = $csrf_generator->validate(helper::env('app_identifier','empty_identifier'), $token, time());
        return $result;
    }

    protected function filter (string $input='') : string|false{
        // check if ajax is used
        if ( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') 
            return false;

        // check if correct token is send
        if ( empty($_SERVER["HTTP_AJAX_REQUEST_TOKEN"]) )
            return false;

        if ( $this->validate_token($_SERVER["HTTP_AJAX_REQUEST_TOKEN"]) === false )
            return false;

        return $input;
    }

    public function index() : void {
        echo "nothing here";
    }

    public static function request_token() : string {
        $csrf_generator = new StatelessCSRF(Helper::env('app_secret', 'empty_secret'));
        $csrf_generator->setGlueData('ip', $_SERVER['REMOTE_ADDR']);
        $csrf_generator->setGlueData('user-agent', $_SERVER['HTTP_USER_AGENT']);            
        $token = $csrf_generator->getToken(Helper::env('app_identifier','empty_identifier'), time() + 900); // valid for 15 mins.           
        return $token;
    }
}