<?php

/**
 * Home Controller
 * Version 1.0.1
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace controller;

use helper\Helper;

class Home extends BaseController {
    function __construct() {
        parent::__construct();
    }

    public function index () : void {
        Helper::log('Home '.Helper::get_ip_addr());
        $this->view('Home');
    }
}