<?php

namespace database;

/**
 * pdo base class for mysql
 * Version 1.0.2
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */


use PDO;

class DbMSQ extends PDO {
    private static ?DbMSQ $instance = null;

    public static function dbtype() : bool {
        return self::$instance !== null;
    }

    public static function instance(string $db_host='', string $db_name='', string $db_user='', string $db_password='') : DbMSQ {
        if (self::$instance == null) {
            $options = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            self::$instance = new DbMSQ("mysql:host=".$db_host.";dbname=".$db_name.";", $db_user, $db_password, $options);
        }
   
        return self::$instance;
    }
}