<?php

namespace database;

/**
 * pdo base class for sqlite3
 * Version 1.0.2
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */


use PDO;

class DbSQ3 extends PDO {
    private static ?DbSQ3 $instance = null;

    public static function dbtype() : bool {
        return self::$instance !== null;
    }

    public static function instance(string $filename='') : DbSQ3 {
        if (self::$instance == null) {
            $options = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            self::$instance = new DbSQ3('sqlite:' . $filename, null, null, $options);
            self::$instance->exec('PRAGMA foreign_keys = ON');
        }
   
        return self::$instance;
    }
}