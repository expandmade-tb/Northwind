<?php

namespace controller;

/**
 * 
 * This controller initializes the environment
 * 
 */

use controller\Auth;
use helper\CryptStrSingleton;
use helper\Helper;
use models\resources_model;
use models\roles_model;
use models\roles_resources_model;
use models\transient_model;
use models\upgrades_model;
use models\users_model;
use models\sessions_model;
use models\user_clients_model;

defined( 'BASEPATH' ) || exit;

class Init {
    private function create_models() : void {
        echo "<br>creating models... ";
        new transient_model();
        new upgrades_model();
        new users_model();
        new user_clients_model();
        new roles_model();
        new resources_model();
        new roles_resources_model();
        new sessions_model();
        echo "models created succesfully";
    }

    private function create_encrypted_config() : void {
        $outfile='.config_enc.php';
        echo("<br>creating file <i>$outfile</i> ... ");

        if ( file_exists(BASEPATH.'/'.$outfile) ) {
            echo "file <i>$outfile</i> does already exist";
            return;
        }

        $sec = getenv('APP_ENV_ENC');
        $contents = '<?php'.PHP_EOL;
    
        foreach ($_ENV as $key => $value)
            $contents .= PHP_EOL.'$'."{$key}='{$value}';";
    
        $result = file_put_contents(BASEPATH.'/'.$outfile, $contents);
    
        if ( $result === false ) 
            echo "could not write file <i>$outfile</i>";
        else
            echo("file <i>$outfile</i> created");
    }

    private function create_initial_key_file() : void {
        $userid = uniqid();
        $keycode = Helper::env('key_code');
        $filename = "initial.key";
        echo("<br>creating file <i>$filename</i>... ");
        $result = json_encode(['user_id'=>$userid,'key_code'=>$keycode]);
    
        if ( $result === false ) {
            echo("cannot json encode values");
            return;
        }
    
        $crypt = CryptStrSingleton::getInstance(Helper::env('app_secret'));
        $cdata = $crypt->encrypt($result);
        $result = file_put_contents(BASEPATH.'/'.$filename, Auth::KEY_HEADER.$cdata);
    
        if ( $result === false ) 
            echo "could not write file <i>$filename</i>";
        else
            echo("file <i>$filename</i> created ...");
    }

    public function index () : void {
        $filename = "initial.key";

        if ( file_exists(BASEPATH.'/'.$filename) ) {
            echo "application already initialized";
            die();
        }

        $this->create_models();
        $this->create_encrypted_config();
        $this->create_initial_key_file();
    }
}