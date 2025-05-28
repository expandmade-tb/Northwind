<?php

use helper\Autoloader;
use helper\Helper;
use Router\Router;

define('VERSION','1.0.0'); // version of this app
define('BASEPATH', realpath(__DIR__.'/../'));
define('APP', BASEPATH.'/app');
define('JAVASCRIPT', '/js');
define('STYLESHEET', '/css');
define('IMAGES', '/img');

chdir(__DIR__);
require_once APP.'/libs/helper/Autoloader.php';
Autoloader::instance();
helper::init_env();
helper::is_logged_in();
Router::instance()->run();