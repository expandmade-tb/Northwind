<?php

namespace database;

/**
 * implementation class of interface IDBView
 * Version 1.1.1
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

use Exception;

class DBView {
    protected IDBView $table;
    protected string $name = '';
    protected string $create_stmt = ''; 
    protected string $dbconnect = '';
    protected array $where_pending = [];
    
    /**
     * Method __construct
     *
     * @param string $name name of the table in the db
     * @param string $create_stmt create statement if view doesnt exist in the db
     *
     * @return void
     */
    public function __construct(string $name='', string $create_stmt='') {
        if ( !empty($name) )
            $this->name = $name;
            
        if ( !empty($create_stmt) )
            $this->create_stmt = $create_stmt;
            
        // check if there is a preset connection
        
        if ( !empty($this->dbconnect) ) { 
            if ( $this->dbconnect === 'MSQ' ) {
                $this->table = new DBTableMSQ($this->name, $this->create_stmt, 'view');
                return;
            } 

            if ( $this->dbconnect === 'SQ3' ) {
                $this->table = new DBTableSQ3($this->name, $this->create_stmt, 'view');
                return;
            } 
        }

        // connect to a db which is already connected

        if ( DbMSQ::dbtype() === true ) {
            $this->table = new DBTableMSQ($this->name, $this->create_stmt, 'view');
            return;
        }

        if ( DbSQ3::dbtype() === true ) {
            $this->table = new DBTableSQ3($this->name, $this->create_stmt, 'view');
            return;
        }

        throw new Exception("no database connection found");
    }

    /**
     * gets the database connection
     *
     * @return DbSQ3|DbMSQ|DbFlat the connection object 
     */
    public function database () : DbSQ3|DbMSQ|DbFlat {
        return $this->table->database();
    }

    /**
     * gets the tablename
     *
     * @return string
     */
    public function tablename() : string {
        return $this->table->tablename();
    }

    /**
     * get a list of all fields in the table
     *
     * @return string a comma separated field list
     */
    public function fieldlist () : string {
        return $this->table->fieldlist();
    }
    
    /**
     * get all the fields meta data of a table
     *
     * @param string $field fields name, if empty all fields
     *
     * @return array fields metadata
     */
    public function fields (string $field='') : array {
        return $this->table->fields($field);
    }

    /**
     * get the tables name in the db
     *
     * @return string
     */
    public function name () : string {
        return $this->table->name();
    }

    /**
     * creates the table in the db
     *
     * @param string $sql the create statement
     *
     * @return static $this
     * 
     * @throws exception
     */
    public function create(string $sql='') : static {
        $this->table->create($sql);
        return $this;
    }

    /**
     * chain function: where
     *
     * @param string $field the fields name in the table
     * @param mixed $value the fields value
     * @param string $compare operator
     * @param string $conditional operator
     *
     * @return static $this
     */
    public function where (string $field, $value, string $compare='=', string $conditional='and') : static {
        $this->where_pending[] = [$field, $value, $compare, $conditional];
        $this->table->where($field, $value, $compare, $conditional);
        return $this;
    }

    /**
     * chain function: orderby
     *
     * @param mixed $fields field names
     *
     * @return static $this
     */
    public function orderby ($fields, string $direction='ASC') : static {
        $this->table->orderby($fields, $direction);
        return $this;
    }

    /**
     * counts the tables rows
     *
     * @param string $select select statement
     * @param array $prepared_params list of parameters for the select statement
     *
     * @return int row count
     * 
     * @throws exception
     */
    public function count(string $select='', ?array $prepared_params=null) : int {
        $this->where_pending = [];
        return $this->table->count($select, $prepared_params);
    }

    /**
     * finds all occurences of the specified select statement
     *
     * @param string $select the sql select statement
     * @param array $prepared_params list of parameters for the select statement
     *
     * @return array 
     * 
     * @throws exception
     */
    public function findAll(string $select='', ?array $prepared_params=null) : array {
        $this->where_pending = [];
        return $this->table->findAll($select, $prepared_params);
    }

    /**
     * finds all occurrences of the specified select statment and returns the speicifed column
     *
     * @param string $column column name to return
     * @param string $select the sql select statement
     *
     * @return array
     * 
     * @throws exception
     */
    public function findColumn (string $column, string $select='') : array {
        $this->where_pending = [];
        return $this->table->findColumn( $column, $select);
    }

    /**
     * finds the first occurence of the specified select statement
     *
     * @param string $select the sql select statement
     * @param array $prepared_params list of parameters for the select statement
     *
     * @return array
     */
    public function findFirst(string $select='', ?array $prepared_params=null) : array {
        return $this->table->findFirst($select, $prepared_params);
    }
    
    /**
     * loads a sql statement from a file
     *
     * @param string $filename the filename of the sql statement
     *
     * @return string|false
     */
    public static function getSQL( string $filename ) : string|false {
        $parts = pathinfo($filename);
	    /* @phpstan-ignore-next-line (ullCoalesce.offset) */
        $fdir = (($parts['dirname']??'.') == '.') ? APP . '/sql' : $parts['dirname']??'.'; 
        $fname = (empty($parts['extension']) == true) ? $parts['filename'] . '.sql' : $parts['basename'];

        if ( file_exists($fdir . '/' . $fname) === true )
            return file_get_contents($fdir . '/' . $fname);
    
        if ( file_exists($filename) === true )
            return file_get_contents($filename);

        return false;
    }
    
    /**
     * chain function: offset
     *
     * @param int $offset the offset to use
     *
     * @return static $this
     */
    public function offset (int $offset=0) : static {
        $this->table->offset($offset);
        return $this;
    }
    
    /**
     * chain function: limit
     *
     * @param int $limit limit to selection
     *
     * @return static $this
     */
    public function limit(int $limit=0) : static {
        $this->table->limit($limit);
        return $this;
    }
}