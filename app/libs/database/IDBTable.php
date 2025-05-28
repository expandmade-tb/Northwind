<?php

namespace database;

/**
 * interface for database tables
 * Version 1.5.1
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

interface IDBTable {
    public function database() : DbSQ3|DbMSQ|DbFlat; 
    public function tablename() : string;
    public function primaryKey () : array;
    public function fieldlist () : string;
    public function fields(string $field='') : array;
    public function name() : string;
    public function create(string $sql='') : static;
    public function drop() : static ;
    public function insert(array $data, bool $empty_is_null=true) : bool;
    public function delete(array|string $id) : bool;
    public function update(array|string $id, array $data) : bool;
    public function find(array|string $id) : mixed;
    public function where (string $field, mixed $value, string $compare='=', string $conditional='and') : static;
    public function orderby (array|string $fields, string $direction='ASC') : static;
    public function count(string $select='', ?array $prepared_params=null) : int;
    public function findAll(string $select='', ?array $prepared_params=null) : array;
    public function findColumn (string $column, string $select='') : array;
    public function findFirst(string $select='', ?array $prepared_params=null) : array;
    public function limit (int $limit=0) : static;
    public function offset (int $offset=0) : static;
    public function identify (bool $identify=false) : static;
    public function deleteAll() : void ;
}