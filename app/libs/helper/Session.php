<?php

namespace helper;

use models\sessions_model;

/**
 * Session Handler & Manager
 * Version 1.1.3
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

class Session {
    private static ?Session $instance = null;
    private string $sec_code = '79bd1a5f1b311662';
    private int $lifetime;
    private sessions_model $sessions;
    
    /**
     * initializes the session singleton
     *
     * @param array $args functions arguments
     * 
     *| arg       | description 
     *|:----------|:-----------------------------------------------
     *| location  | session data storage. empty = standard | path = custom location | database = table in database 
     *| name      | the session name. Default is '__Secure-ID'
     *| lifetime  | session lifetime in seconds
     *| path      | Path on the domain where the cookie will work     
     *| domain    | Cookie domain       
     *| secure    | If true cookie will only be sent over secure connections.       
     *| httponly  | If set to true then PHP will attempt to send the httponly flag when setting the session cookie.     
     *| samesite  | Cookie ought not to be sent along with cross-site requests      
     *
     * @return void
     */
    protected function __construct(array $args=[]) {
        $location = '';
        $name = '__Secure-ID';
        $lifetime = 43200;
        $path = '/';
        $domain = null;
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')? true : false;
        $httponly = true;
        $samesite = 'Strict';
        extract($args, EXTR_IF_EXISTS);
        $this->lifetime = $lifetime;
        session_name($name);
        session_set_cookie_params(['lifetime'=>$lifetime, 'path'=>$path, 'domain'=>$domain, 'secure'=>$secure, 'httponly'=>$httponly, 'samesite'=>$samesite]);
        
        /* @phpstan-ignore-next-line (phpstan doesnt consider extract */
        if ( $location == 'database' ) { // use a database handler
            $this->sessions = new sessions_model();
            
            session_set_save_handler(
                [$this, 'db_open'],
                [$this, 'db_close'],
                [$this, 'db_read'],
                [$this, 'db_write'],
                [$this, 'db_destroy'],
                [$this, 'db_gc']
            );
        }
        else {
            $session_path = realpath($location.'/sessions'); // use a customer file location

            if ( $session_path !== false )
                session_save_path($session_path);
        }
    }
        
    /**
     * create / gets the singleton
     *
     * @param array $args functions arguments
     *
     *| arg       | description 
     *|:----------|:-----------------------------------------------
     *| location  | session data storage. empty = standard | path = custom location | database = table in database 
     *| name      | the session name. Default is '__Secure-ID'
     *| lifetime  | session lifetime in seconds
     *| path      | Path on the domain where the cookie will work     
     *| domain    | Cookie domain       
     *| secure    | If true cookie will only be sent over secure connections.       
     *| httponly  | If set to true then PHP will attempt to send the httponly flag when setting the session cookie.     
     *| samesite  | Cookie ought not to be sent along with cross-site requests      
     *
     * @return Session self
     */
    public static function instance(array $args=[]) : Session {
        if ( self::$instance == null )
            self::$instance = new Session($args);
   
        return self::$instance;
    }
         
    /**
     * starts the session
     *
     * @return bool returns true if a session was successfully started, otherwise false.
     */
    public function start () : bool {
        if ( session_start() !== true ) 
            return false;

         if ($this->validSession() ) {

            if ( $this->validIdent() !== true) {
                $hash = $this->sec_code;
                $hash .= $_SERVER['REMOTE_ADDR']??'';
                $hash .= $_SERVER['HTTP_USER_AGENT']??'';
                $this->unset();
                $this->set('hash', md5($hash));
                $this->set('expired', strval(time() + $this->lifetime));
            }
        }
        else {
            $this->unset()->destroy();
            $this->start();
        }

        unset($_SESSION['__flash']);

        if ( isset($_SESSION['__flash-in']) ) {
            $_SESSION['__flash'] = $_SESSION['__flash-in'];
            unset($_SESSION['__flash-in']);
        }

        return true;
    }
    
    // callback session open handler
    public function db_open(string $savePath, string $sessionName) : bool {
        return true;
    }

    // callback session close handler
    public function db_close() : bool {
        return true;
    }

    // callback session read handler
    public function db_read(string $sessionId): string  {
        try {
            $result = $this->sessions->find($sessionId);

            if ( $result === false )
                return '';
            else
                return $result['session_data'];
        } catch (\Throwable $th) {
            Helper::debug($th);
            return '';;
        }
    }

    // callback session write handler
    public function db_write(string $sessionId, string $data): bool {
        try {
            $result = $this->sessions->find($sessionId);

            if ( $result === false )
                return $this->sessions->insert(['session_id'=>$sessionId,'session_data'=>$data,'session_expire'=>time()+$this->lifetime]);
            else
                return $this->sessions->update($sessionId, ['session_data'=>$data,'session_expire'=>time()+$this->lifetime]);
        } catch (\Throwable $th) {
            Helper::debug($th);
            return false;;
        }
    }

    // callback session destroy handler
    public function db_destroy(string $sessionId): bool {
        try {
            return $this->sessions->delete($sessionId);
        } catch (\Throwable $th) {
            Helper::debug($th);
            return false;;
        }
    }

    // callback session gc handler
    public function db_gc(): bool {
        try {
            $table = $this->sessions->tablename();
            $time = time();
            $sql = "DELETE from $table where session_expire < $time";
            $result = $this->sessions->database()->query($sql);

            if ( $result === false )
                return false;
            else
                return true;
        } catch (\Throwable $th) {
            Helper::debug($th);
            return false;;
        }
    }

    /**
     * creates a new id on an existing session
     *
     * @return bool
     */
    public function regenerate () : bool {
        if ( $this->get('expired') != null )
            return false;

        $this->set('expired', strval(time() + 5));

        if ( session_regenerate_id(false) === false)
            return false;

        $this->remove('expired');

        if ( session_write_close() === false )
            return false;

        $result = $this->start();
        return $result;
    }
    
    /**
     * sets a session value
     *
     * @param string $key the key to the session value
     * @param mixed $value the value to be set
     *
     * @return Session $this
     */
    public function set ( string $key, mixed $value ) : Session {
        $_SESSION[$key] = $value;
        return $this;
    }
    
    /**
     * gets a session value
     *
     * @param string $key the key to the session value
     * @param mixed $default the default value if key doesnt exist
     *
     * @return mixed value of the key | default value | null
     */
    public function get ( string $key,  mixed $default=null) {
        if ( array_key_exists($key, $_SESSION['__flash']??[] ) ) 
            return $_SESSION['__flash'][$key];
        else
            if ( array_key_exists($key, $_SESSION??[]) )
                return $_SESSION[$key];
            else
                if ( !empty($default) )
                    return $default;
                else
                    return null;
    }
    
    /**
     * removes the key/value pair from the session
     *
     * @param string $key the key to the session value
     * 
     * @return Session $this
     */
    public function remove (string $key) : Session {
        unset($_SESSION[$key]);
        return $this;
    }
    
    /**
     * unset/remove all key/value pairs from a session
     *
     * @return Session $this
     */
    public function unset () : Session {
        session_unset();
        return $this;
    }
    
    /**
     * destroys the session
     *
     * @return Session $this
     */
    public function destroy () : Session {
        $this->set('expired', strval(time() + 5));
        session_destroy();
        return $this;
    }
    
    /**
     * status of the session
     *
     * @return int status see @link https://www.php.net/manual/en/function.session-status.php
     */
    public function status () : int {
        return session_status();
    }
    
    /**
     * checks if the current session have a valid identification regarding ip adress and user agent
     *
     * @return bool
     */
    private function validIdent () : bool {
        $hash = $this->sec_code;
        $hash .= $_SERVER['REMOTE_ADDR']??'';
        $hash .= $_SERVER['HTTP_USER_AGENT']??'';

        if ( $this->get('hash','') != md5($hash) )
            return false;

	    return true;        
    }
    
    /**
     * checks if a session isnt already expired
     *
     * @return bool
     */
    private function validSession () : bool {
        $expired = $this->get('expired');

        if ( isset($expired) && $expired < time() )
		    return false;

	    return true;
    }
    
    /**
     * sets session flash data
     *
     * @param string $key key to flash data
     * @param mixed $value flash data value(s)
     *
     * @return Session $this
     */
    public function flash (string $key, $value) : Session {
        $_SESSION['__flash-in'][$key] = $value;
        return $this;
    }
    
    /**
     * returns flash data and keeps them
     *
     * @param string $key key to flash data
     *
     * @return mixed 
     */
    public function keep_flash (string $key) {
        if ( array_key_exists($key, $_SESSION['__flash']??[] ) ) 
            $result = $_SESSION['__flash'][$key];
        else
            return null;

        $_SESSION['__flash-in'][$key] = $result;
        return $result;
    }
}