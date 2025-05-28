<?php

namespace Router;

use Exception;
use helper\Helper;

/**
 * Simple MVC Router
 * Version 1.7.0
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

 class Router {  
    private static ?Router $instance = null;
    private string $namespace;
    private string $authController;
    private string $serverBasePath;
    private mixed $authFunction; 
    public bool $debug = false;
       
    private function match(string $request, array|string $pattern, string $method) : string|false {
        if ( is_array($pattern) ) { // check request method + controller + method
            $result = explode('|', $pattern[0]);
            $methodList = $pattern[1];
        }
        else
            $result = explode('|', $pattern);  // check request method + controller 

        if ( in_array($request, $result))
            $return = end($result);  // request method + controller = ok
        else
            return false;
        
        // check method if available
        if ( (!empty($method)) && (isset($methodList)) && (!in_array($method, explode(',', $methodList))) )
            $return = false;

        return $return;
    }

    private function validate(string $request_method, string $controller, string $method='') : string|false {
        $pattern = $controller;

        if ( isset(Routes::$routes[$pattern]) )
            return $this->match($request_method, Routes::$routes[$pattern], $method);

        return false;
    }

    private function execute (string $controller, string $method, array $parameters) : void {
        if ( isset($this->authFunction) && !isset(Routes::$auth_exceptions[$controller]) )
            if ( !call_user_func($this->authFunction) && $controller != $this->authController ) {
                $url = $this->url().'/'.$this->authController;
                $this->redirect($url);
                exit();
            }

        if ( Helper::canAccess($controller) === false ) { // can controller be accessed by the user ?
            $this->redirect();
            return;
        }

        $request_method = strtoupper($_SERVER['REQUEST_METHOD']);
        $result = $this->validate($request_method, $controller, $method);

        if ( $result === false )
            $this->throw_405();

        $controllerName = $this->namespace.$result;

        if ( !class_exists($controllerName) )
            $this->throw_404();

        $class = new $controllerName();
        $method = empty($method) ? Routes::$defaultMethod : $method;

        if ( !method_exists($class, $method))
            $this->throw_404();

        try {
    	    /* @phpstan-ignore-next-line (phpstan just not accepting this...) */
            if ( call_user_func_array([$class, $method], $parameters) === false )
                $this->throw_404();
        } catch (\Throwable $th) {
            if ( $this->debug) {
                echo '<h3>An error occured</h3>';
                echo 'message: ' .$th->getMessage();
                echo '<br>file: '.basename($th->getFile());
                echo '<br>line: '.$th->getLine();
                exit();
            }
            else
                $this->throw_400();
        }
    }

    protected function __construct() {
        $this->namespace = Routes::$defaultNamespace;
        $this->debug = (boolean)Helper::env('debug', false);
    }
    
    private function basepath() : string {
        if ( isset($this->serverBasePath) )
            return $this->serverBasePath;

        $this->serverBasePath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
        return $this->serverBasePath;
    }

    /**
     * creates / returns the instance of the router 
     *
     * @return Router $this
     */
    public static function instance () : Router {
        if ( self::$instance == null )
            self::$instance = new Router();
   
        return self::$instance;
    }
    
    /**
     * throws a 404 html page
     *
     * @return void
     */
    public function throw_404 () : void {
        header('HTTP/1.0 404 Not Found');
        exit('<h3>error 404 - page not found</h3>');
    }

    /**
     * throws a 405 html page
     *
     * @return void
     */
    public function throw_405 () : void {
        header('HTTP/1.0 405 Method Not Allowed');
        exit('<h3>error 405 - Method Not Allowed</h3>');
    }
           
    /**
     * throws a 400 html page
     *
     * @return void
     */
    public function throw_400 () : void {
        header('HTTP/1.0 400 Bad Request');
        exit('<h3>error 400 - Bad Request</h3>');
    }
       
    /**
     * set the authorization controller. When set, it will always be called before another controller is executed
     *
     * @param string $authController name of the controller (i.e. login)
     * @param callable $authFunction function to check if the authorization controller has to be executed
     *
     * @return Router self
     */
    public function setAuth(string $authController, callable $authFunction) : Router { 
        if ( $this->validate('GET', $authController) === false || $this->validate('POST', $authController) === false)
            throw new Exception("route to auth controller not defined: ".$authController);

        $this->authController = $authController;
        $this->authFunction = $authFunction;
        /* @phpstan-ignore-next-line (we wont be here w/o instance) */
        return self::$instance;
    }
    
    public function getAuth() : string {
        return $this->authController;
    }

    /**
     * return the current uri of a site
     *
     * @return string the uri
     */
    public function current_uri() : string {
        $uri = substr(rawurldecode($_SERVER['REQUEST_URI']), strlen($this->basepath()));

        if (strstr($uri, '?')) {
            $len = strpos($uri, '?');

            if ( $len === false )
                $len = null;

            $uri = substr($uri, 0, $len);
        }

        return '/' . trim($uri, '/');
    }
        
    /**
     * Retrieve all current query vars.
     *
     * @return array all current query vars
     */
    public function current_query() : array {
        $result = [];

        if ( empty($_SERVER['QUERY_STRING']) )
            return $result;

        parse_str($_SERVER['QUERY_STRING'], $result);

        if ( isset($result['url']) )
            unset($result['url']);

        return $result;
    }

    /**
     * set the namespace for controllers
     *
     * @param string $namespace the namespace
     *
     * @return Router $this
     */
    public function setNamespace(string $namespace) : Router {
        $this->namespace = $namespace;
        /* @phpstan-ignore-next-line (we wont be here w/o instance) */
        return self::$instance;
    }

    /**
     * Redirects to the root if empty or to given location
     *
     * @return void
     */
    public function redirect(string $to='') : void {
        if ( empty($to) )
            $to = $this->url();
            
        header("Location: ".$to);
    }

    /**
     * gets the url
     *
     * @return string the url of the current app
     */
    public function url() : string {
        return sprintf(
          "%s://%s%s",
          isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
          $_SERVER['SERVER_NAME'],
          php_sapi_name() == "cli-server" ? ':'.$_SERVER['SERVER_PORT'] : ''
        );
    }    
        
    /**
     * runs the router
     *
     * @return Router $this
     */
    public function run() : Router {
        $url = parse_url($this->current_uri());
        $path = isset($url['path']) ? $url['path'] : '';
        $path_parts = explode('/', $path);
        $controller = !empty($path_parts[1]) ? $path_parts[1] : Routes::$defaultHome;
        $function  = isset($path_parts[2]) ? $path_parts[2] : '' ;

        if ( isset($path_parts[3]) )
            $parameter[] = $path_parts[3];
        else
            $parameter = [];
            
        $this->execute($controller, $function, $parameter);
            
        /* @phpstan-ignore-next-line (we wont be here w/o instance) */
        return self::$instance;
    }    
}