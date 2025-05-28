<?php

/**
 * Helper class
 * Version 1.4.1
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace helper;

use database\DbSQ3;
use database\DbMSQ;
use models\transient_model;
use models\users_model;
use Exception;
use models\user_clients_model;
use PDO;

defined( 'BASEPATH' ) || exit;

class Helper {      
    private static transient_model $transient;
    private static bool $initialized = false;  

    private static function initialize () :void {
        if (self::$initialized)
            return;

        self::$initialized = true;
        self::$transient = new transient_model();
    }

    /**
     * writes a message into the tmp location application log
     *
     * @param $message $message the message 
     *
     * @return bool
     */
    public static function log ( array|string $message ) : bool { 
        if (is_array($message)) { 
            $message = json_encode($message); 
        } 
    
        $filename = Helper::env('tmp_location').'application.log';
        $file = fopen($filename, "a"); 

        if ( $file === false ) {
            return false;
        }

        $result = fwrite($file, "\n" . date('Y-m-d h:i:s') . " :: " . $message); 
        fclose($file); 
        return $result === false ? false : true;
    }

    /**
     * Called after successfully logged in
     *
     * @param array $data variables to be passed to $_SESSION
     *
     * @return void
     */
    public static function logged_in (array $data) : void {
        $session = Session::instance();
        $client_id = $session->get('client_id','');

        if ( empty($client_id) ) 
            die('none or invalid client id');
        
        $session->regenerate();
        $session->set('logged_in', self::env('app_identifier', 'logged_in'));

        foreach ($data as $key => $value)
            $session->set($key, $value);

        $name = '__Client-ID';
        $expires = time() + 43200;
        $path = '/';
        $domain = '';
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')? true : false;
        $httponly = true;
        $samesite = 'Strict';
        $value = password_hash($client_id, PASSWORD_DEFAULT);
        setcookie($name, $value, ['expires'=>$expires, 'path'=>$path, 'domain'=>$domain, 'secure'=>$secure, 'httponly'=>$httponly, 'samesite'=>$samesite]);
    
        self::log('logged in from: '.self::get_ip_addr().' with client id: '.$client_id);
        self::redirect();
    }
    
    /**
     * Cleanup after user requested to logout
     *
     * @return void
     */
    public static function logged_out () : void {
        session_gc();
        Session::instance()->unset()->destroy();

        $name = '__Client-ID';
        $expires = time() - 43200;
        $path = '/';
        $domain = '';
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')? true : false;
        $httponly = true;
        $samesite = 'Strict';
        $value = '';
        setcookie($name, $value, ['expires'=>$expires, 'path'=>$path, 'domain'=>$domain, 'secure'=>$secure, 'httponly'=>$httponly, 'samesite'=>$samesite]);

        self::redirect();
    }
    
    /**
     * check if a users is logged 
     *
     * @return bool
     */
    public static function is_logged_in () : bool {
        $session = Session::instance(['location'=>Helper::env('sessions','')]);
        $session->start();
        
        if ( $session->get('logged_in', 'a') !== self::env('app_identifier', 'b') )
            return false; 

        $user_id = $session->get('user_id', 'empty_user_id');
        $users = new users_model();
        $user = $users->find($user_id);

        if ( $user === false )
            if ( $users->count() == 0 ) // i.e. the env has never been used before
                return true;
            else
                return false;

        $user_clients = new user_clients_model();

        $result = $user_clients->where('UserId', $user_id )
                               ->where('ClientId', $session->get('client_id','empty_client_id'))
                               ->findFirst();

        if ( count($result) == 0 )
            return false;

        if ( password_verify($result["ClientId"], $_COOKIE['__Client-ID']??'') === false )
            return false;

        return true;
    }
            
    /**
     * deletes expired transients
     *
     * @param string $ids the id's which should be deleted
     *
     * @return mixed
     */
    public static function clean_transient (string $ids) {
        self::initialize();
        $transient = self::$transient->name();
        $now = time();
        $sql = "delete from {$transient} where id like '{$ids}' and valid_until > 0 and valid_until < {$now}";
        return self::$transient->database()->query($sql);
    }

    /**
     * Retrieves/Stores the value of a transient. 
     *
     * @param string $id name of the transient
     * @param mixed $value value of the transient to set | empty value will return the value
     * @param int $expiration 0 = never expires | < 0 = delete transient | expiration in secs when set
      *
     * @return mixed FALSE if transient doesnt exist or expired | transients current value
     */
    public static function transient ( string $id, $value=null, int $expiration=0 ) {
        self::initialize();

        if ( is_null($value) ) { // --- searching for a transient --
            $result = self::$transient->find($id);

            if ( $result === false)
                return false;

            if ( $result['valid_until'] === 0 ) // an expiration time of zero wont expire
                return $result['data'];

            if ( $result['valid_until'] < time() ) { // if expiration time is reached, then it is like the id doenst exist
                self::$transient->delete($id);
                return false;
            }

            return $result['data'];
        }
        else { // --- add/update/delete transient ---
            if ( $expiration < 0 ) { // if expiration time is less than 0 delete transient
                self::$transient->delete($id);
            }
            else {
                if ( empty($value) ) // if the value is empty there is nothing to store, just return false
                    return false;

                if ( $expiration > 0 ) // set the expiration time
                    $valid_until = time()+$expiration;
                else
                    $valid_until = 0;

                $result = self::$transient->find($id);

                if ( $result === false )
                    self::$transient->insert(['id'=>$id, 'valid_until'=>$valid_until, 'data'=>$value]);
                else
                    self::$transient->update($id, ['valid_until'=>$valid_until, 'data'=>$value]);
            }
        }
    }  
    
    /**
     * redirects to the root if empty or to location
     *
     * @return void
     */
    public static function redirect(string $to='') {
        if ( empty($to) )
            $to = Helper::url();
            
        header("Location: ".$to);
    }

    /**
     * Method url
     *
     * @return string the url of the current app
     */
    public static function url() : string {
        return sprintf(
          "%s://%s%s",
          isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
          $_SERVER['SERVER_NAME'],
          php_sapi_name() == "cli-server" ? ':'.$_SERVER['SERVER_PORT'] : ''
        );
    }    
    
    /**
     * initializes the env 
     * 
     * @param string $config_filename name of the configuration file
     * 
     * if the default configuration file is used, the variables will be
     * encrypted, otherwise it will be assumed the variables already are encrypted.
     * 
     * the env var 'APP_ENV_ENC' is supposed to be set in .htaccess !
     */
    public static function init_env ( string $config_filename='.config.php' ) : void {
        try {
            require_once BASEPATH.'/'.$config_filename;
        } catch (\Throwable $th) {
            header('HTTP/1.0 400 Bad Request');
            exit('<h3>error 400 - Bad Request</h3>');
        }
        
        $vars = get_defined_vars();
        unset($vars['config_filename']);
        $sec = getenv('APP_ENV_ENC');

        if ( (strcmp($config_filename, '.config.php') != 0) || empty($sec) )
            foreach ($vars as $var => $value) $_ENV[$var] = $value; // value is already encrypted
        else
            foreach ($vars as $var => $value) $_ENV[$var] = CryptStr::instance($sec)->encrypt($value); // encrypt value now
 
        try {
            $dbname=self::env('db_name');

            if ( Helper::env('db_type', '') == 'MYSQL' ) { // MySQL
                $dbhost=self::env('db_host');
                $dbuser=self::env('db_user');
                $dbpassword=self::env('db_password');
                DbMSQ::instance($dbhost, $dbname, $dbuser, $dbpassword);
            }
            else // SQLITE+
                DbSQ3::instance(self::env('storage_location').$dbname);

        } catch (\Throwable $th) {
            if ( Helper::env('debug', false) == true ) {
                echo '<h3>An error occured</h3>';
                echo 'message: ' .$th->getMessage();
                echo '<br>file: '.basename($th->getFile());
                echo '<br>line: '.$th->getLine();
                exit();
            }
            else {
                header('HTTP/1.0 400 Bad Request');
                exit('<h3>error 400 - Bad Request</h3>');
            }
        }
    }
    
    /**
     * gets an environment variable 
     *
     * @param string $var name of the variable
     * @param $default=null $default the default to be returned if variable does not exist
     *
     * @return string
     */
    public static function env (string $var, mixed $default=null) : string {
        $sec = getenv('APP_ENV_ENC');

        if ( isset($_ENV[$var]) )
            if ( !empty($sec) ) {
                $result = CryptStr::instance($sec)->decrypt($_ENV[$var]);

                if ( $result === false )
                    return $default;
                else
                    return $result;
            }
            else
                return $_ENV[$var];
        else
            if ( isset($default) )
                return $default;
            else
                throw new Exception("environment var {$var} unknown");
    }
    
    /**
     * get the ip adress
     *
     * @return string
     */
    public static function get_ip_addr() : string {
        $ip = '0.0.0.0';

        if ( ! empty($_SERVER['REMOTE_ADDR']) )
            $ip = $_SERVER['REMOTE_ADDR'];
        else if ( ! empty($_SERVER['HTTP_X_FORWARDED_FOR']) )
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if ( ! empty($_SERVER['HTTP_CLIENT_IP']) )
            $ip = $_SERVER['HTTP_CLIENT_IP'];

        return $ip;
    }
    
    /**
     * writes debug information to the application log. 
     *
     * @param $data $data data to log
     *
     * @return void
     */
    public static function debug(mixed $data) : void {
        if (empty(self::env('debug', '')) )
            return;

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $name = basename($trace[0]["file"]??'unknown file');
        $line = $trace[0]["line"]??'unknown line';
        $result = print_r($data, true);
        self::log("PHP {$name}({$line}): $result");
    }
    
    /**
     * selects and returns all disallowed controllers
     *
     * @return array|false controller names or false if cant select
     */
    public static function disallowedAccess() : array|false {
        if ( self::env('rbac') == false )
            return [];

        $user_id = Session::instance()->get('user_id');

        $users = new users_model();
        $sql = $users->getSQL('disallowedAccess');

        if ( $sql === false )
            return false;

        $stmt = $users->database()->prepare($sql);

        if ( $stmt === false )
            return false;

        $result = $stmt->execute([$user_id]);

        if ( $result === false )
            return false;

        $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $data;
    }
        
    /**
     * checks if a controller can be used by the current user
     *
     * @param string $controller controller name
     *
     * @return bool
     */
    public static function canAccess(string $controller) : bool {
        if ( self::env('rbac') == false )
            return true;

        $user_id = Session::instance()->get('user_id');
        $users = new users_model();
        $sql = $users->getSQL('canAccess');

        if ( $sql === false )
            return false;

        $stmt = $users->database()->prepare($sql);

        if ( $stmt === false )
            return false;

        $result = $stmt->execute([$user_id, $controller]);

        if ( $result === false )
            return false;

        $data = $stmt->fetchAll();

        if ( !empty($data) )
            return false;

        return true;
    }
}
