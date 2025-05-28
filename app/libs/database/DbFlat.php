<?php

namespace database;

use Exception;
use PDOStatement;

/**
 * base class for Flatfiles
 * Version 1.0.4
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

class DbFlat {
    private static ?DbFlat $instance = null;
    private string $path = '';

    protected function __construct(string $path) {
        if ( empty($path) )
            throw new Exception("path must not be empty");

        $this->path = $path;
    }

    public static function dbtype() : bool {
        return self::$instance !== null;
    }

    public function path() : string {
        return $this->path;
    }

    public static function instance(string $path='') : DbFlat {
        if (self::$instance == null) {
            self::$instance = new DbFlat($path);
        }
   
        return self::$instance;
    }

    public function beginTransaction() : bool {
        return true;
    }

    public function exec(string $statement) : int|false {
        return 0;
    }

    public function commit() : bool {
        return true;
    }

    public function rollBack() : bool {
        return true;
    }

    public function query(string $query, ?int $fetchMode = null) : PDOStatement|false {
        return false;
    }

    public function lastInsertId(?string $name = null): string|false {
        return false;
    }

    public function prepare(string $query, array $options = []): PDOStatement|false {
        return false;
    }

}