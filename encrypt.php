#!/usr/bin/env php -q
<?php  // debug: export XDEBUG_MODE=debug XDEBUG_SESSION=1

use controller\Auth;
use helper\Autoloader;
use helper\CryptStr;
use helper\Helper;

/**
 * writes string to terminal
 */
function write (string $s) : void {
    echo $s.PHP_EOL;
}

/**
 *  handle exceptions, output to terminal
 */
function exceptions( Throwable $exception ) : void {
    write("");
    write($exception->getMessage());
}

/**
 * handle errors, output to terminal
 */
function errorHandler(int $errno, string $errstr, string $errfile, int $errline) : bool {
    $errstr = htmlspecialchars($errstr);
    write("");

    switch ($errno) {
        case E_USER_ERROR:
            write("ERROR [$errno] $errstr");
            write("   Fatal error on line $errline in file $errfile");
            exit(1);

        case E_USER_WARNING:
            write("waring [$errno] $errstr");
            break;

        case E_USER_NOTICE:
            write("notice [$errno] $errstr");
            break;

        default:
            write("unknown error: [$errno] $errstr");
            exit(1);
    }

    /* Don't execute PHP internal error handler */
    return true;
}

function create_encrypted_config (string $param_infile, string $param_outfile, string $param_password) : bool {
    write("creating '$param_outfile' ...");
    require_once $param_infile;
    $vars = get_defined_vars();
    unset($vars['param_infile'], $vars['param_outfile'], $vars['param_password'],);
    putenv('APP_ENV_ENC='.$param_password);
    $contents = '<?php'.PHP_EOL;

    foreach ($vars as $var => $value) {
        $crypt_val = CryptStr::instance($param_password)->encrypt($value);

        if ( $crypt_val === false )
            trigger_error('cannot encrypt value', E_USER_ERROR);

        $contents .= PHP_EOL.'$'."{$var}='{$crypt_val}';";
        $result = CryptStr::instance($param_password)->decrypt($crypt_val);

        if ( $result === false )
            trigger_error('cannot decrypt value', E_USER_ERROR);

        if ( $value != $result ) {
            write("error in encryption with variable $var");
            return false;
        }
    }

    $result = file_put_contents($param_outfile, $contents);

    if ( $result === false ) 
        write("could not write $param_outfile");

    $userid = uniqid();
    $keycode = $vars['key_code'];
    $filename = "initial.key";
    $result = json_encode(['user_id'=>$userid,'key_code'=>$keycode]);

    if ( $result === false )
        trigger_error('cannot json encode values', E_USER_ERROR);

    $cdata = CryptStr::instance($vars['app_secret'])->encrypt($result);
    $result = file_put_contents($filename, Auth::KEY_HEADER.$cdata);

    if ( $result === false ) 
        write("could not write $filename");

    return true;
}

// --- main ----

define('VERSION','1.1.0');
define('BASEPATH', realpath(__DIR__));
define('APP', BASEPATH.'/app');

set_error_handler("errorHandler");
set_exception_handler("exceptions");

chdir(__DIR__);
require_once APP.'/libs/helper/Autoloader.php';
Autoloader::instance('app');

// show hint
if ( $argc == 1 ) {
   write(basename(__FILE__));
   write("   param: password");
   exit(1);
}

$infile='.config.php';
$outfile='.config_enc.php';
$password=$argv[1];

if (!file_exists($infile)) {
    write("input file $infile does not exit");
    exit(1);
}

$result = create_encrypted_config($infile, $outfile, $password);

?>
