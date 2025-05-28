<?php

namespace database;

/**
 * implementation class of interface IDBTable
 * Version 1.9.2
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

use Exception;

class DBTable {
    protected IDBTable $table;
    protected string $name = '';
    protected string $create_stmt; 
    protected string $dbconnect = '';
    protected array $where_pending = [];

    /**
     * this method is meant to be overwritten and will be called by the respective database driver class
     * 1. when a table does not exist
     * 2. there is no ddl to execute
     */
    public function DDL() : ?DbDDL { return null; }
    
    /**
     * Method __construct
     *
     * @param string $name name of the table in the db
     * @param string $create_stmt create statement if table doesnt exist in the db
     *
     * @return void
     */
    public function __construct(string $name='', string $create_stmt='') {
        if ( !empty($name) )
            $this->name = $name;
            
        if ( !empty($create_stmt) ) // use the passed stmt
            $this->create_stmt = $create_stmt;
        
        if ( !empty($this->create_stmt) )
            $stmt = $this->create_stmt; // passing string
        else
            $stmt = [$this, 'DDL']; // passing callback DDL function 

        // check if there is a preset connection
        
        if ( !empty($this->dbconnect) ) { 
            if ( $this->dbconnect === 'MSQ' ) {
                $this->table = new DBTableMSQ($this->name, $stmt);
                return;
            } 

            if ( $this->dbconnect === 'SQ3' ) {
                $this->table = new DBTableSQ3($this->name, $stmt);
                return;
            } 

            if ( $this->dbconnect === 'Flat' ) {
                $this->table = new DBTableFlat($this->name, $stmt);
                return;
            } 
        }

        // connect to a db which is already connected

        if ( DbMSQ::dbtype() === true ) {
            $this->table = new DBTableMSQ($this->name, $stmt);
            return;
        }

        if ( DbSQ3::dbtype() === true ) {
            $this->table = new DBTableSQ3($this->name, $stmt);
            return;
        }

        if ( DbFlat::dbtype() === true ) {
            $this->table = new DBTableFlat($this->name, $stmt);
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
     * gets the primary key of the table
     *
     * @return array the primary key
     */
    public function primaryKey () : array {
        return $this->table->primaryKey();
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
     * drops the table in the db
     *
     * @return static $this
     * 
     * @throws exception
     */
    public function drop() : static {
        $this->table->drop();
        return $this;
    }

    /**
     * inserts a single row into the table
     *
     * @param array $data the fields and data to insert
     * @param bool $empty_is_null by default empty columns will be treated as null
     *
     * @return bool 
     * 
     * @throws exception
     */
    public function insert(array $data, bool $empty_is_null=true) : bool {
        return $this->table->insert($data, $empty_is_null);
    }

    /**
     * deletes single row from the table
     *
     * @param array|string $id the primary key of the table
     *
     * @return bool
     * 
     * @throws exception
     */
    public function delete(array|string $id) : bool {
        return $this->table->delete($id);
    }

    /**
     * updates a signle row of a table
     *
     * @param array|string $id the tables primary key
     * @param array $data the data to update
     *
     * @return bool
     * 
     * @throws exception
     */
    public function update(array|string $id, array $data) : bool {
        return $this->table->update($id, $data);
    } 

    /**
     * finds a single row in the table
     *
     * @param array|string $id the primary key of the table
     *
     * @return mixed array | false
     * 
     * @throws exception
     */
    public function find(array|string $id) : mixed {
        return $this->table->find($id);
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
    
    /**
     * chain function: identify
     *
     * @param bool $identify add an identifier to the selection
     *
     * @return static $this
     */
    public function identify (bool $identify=false) : static {
        $this->table->identify($identify);
        return $this;
    }
    
    /**
     * import a csv file into a table
     *
     * @param string $filename the filename to the csv file to import
     * @param array $args one or more of the following arguments:
     * 
     *| arg             | description 
     *|:----------------|:-----------------------------------------------
     *| separator       | Set the field delimiter (one single-byte character only).
     *| enclosure       | Set the field enclosure character (one single-byte character only). 
     *| escape          | Set the escape character (at most one single-byte character). Defaults as a backslash (\) An empty string ("") disables the proprietary escape mechanism.  
     *| offset          | At what line of the input file to start to import      
     *| limit           | Maximum number of lines to import
     *| on_insert_error | Callback on insert error. function (int $linecount, string $line) : bool;
     *
     * @return bool
     */
    public function import(string $filename, array $args=[] ) : bool  {
        $separator = ',';
        $enclosure = '"';
        $escape = '\\';
        $offset = 0;
        $limit = 999999; 
        $on_insert_error = null;
        extract($args, EXTR_IF_EXISTS);

        $linecount = 0;
        $fields = [];
        $file = fopen($filename, 'r');

        if ( $file === false ) {
            trigger_error("file $filename cannot be opened", E_USER_WARNING );
            return false;    
        }
 
        /* @phpstan-ignore-next-line ( method is defined in DbFlat but in others might not) */
        if ( method_exists($this->table->database(), 'beginTransaction'))
            $this->table->database()->beginTransaction();

        while ( !feof($file) && ($limit > 0) ) {
            $line = fgetcsv($file, null,$separator, $enclosure, $escape);
            
            if ( $line !== false ) {
                switch (true) {
                    case ($linecount == 0) :
                        $fields = $line;
                        break;
                    /* @phpstan-ignore-next-line (extract statement not recognized by phpstan */
                    case ($linecount < $offset): 
                        break;
                    default:
                        $values = $line;

                        try {
                            foreach ($values as $key => $value) {
                                $col = $fields[$key]??'col'.$key;
                                $data[$col] = $value;
                            }
             
                            $this->insert($data);
                        } catch (\Throwable $th) {
                            /* @phpstan-ignore-next-line (extract statement not recognized by phpstan */
                            if ( !empty($on_insert_error) ) {  
                                if ( call_user_func($on_insert_error, (int)$linecount, (string)$line) === true ) {
                                    $this->table->database()->rollBack();
                                    return false;
                                }
                            }
                        }
                        
                        $limit--;
                        break;
                }

                $linecount++;
            }
        }

        fclose($file);
 
        /* @phpstan-ignore-next-line ( method is defined in DbFlat but in others might not) */
        if ( method_exists($this->table->database(), 'commit'))
            $this->table->database()->commit();
    
        return true;
    }

    /**
     * exports a table to csv file
     *
     * @param string $filename the filename to the csv file to export
     * @param array $args one or more of the following arguments:
     * 
     *| arg             | description 
     *|:----------------|:-----------------------------------------------
     *| separator       | Set the field delimiter (one single-byte character only).
     *| enclosure       | Set the field enclosure character (one single-byte character only). 
     *| escape          | Set the escape character (at most one single-byte character). Defaults as a backslash (\) An empty string ("") disables the proprietary escape mechanism.  
     *| limit           | number of lines to export in a chunk
     *
     * @return bool|int
     */
    public function export (string $filename, string|array $fields='', array $args=[]) : bool|int {
        $separator = ',';
        $enclosure = '"';
        $escape = '\\';
        $limit = 1000;
        extract($args, EXTR_IF_EXISTS);

        if ( empty($fields) === true )
            $fieldlist = explode(',', $this->fieldlist());
        else
            if ( is_array($fields) )
                $fieldlist = $fields;
            else
                $fieldlist = array_map('trim',explode(',', $fields));

        $where_pending = $this->where_pending;
        $rowcount = $this->count();
        $offset = 0;
        $lines = 0;
        $file = fopen($filename, 'w');

        if ( $file === false ) {
            trigger_error("file $filename cannot be opened", E_USER_WARNING );
            return false;    
        }
 
        $result = fputcsv($file, $fieldlist, $separator, $enclosure, $escape);

        if ( $result === false ) {
            fclose($file);
            return false;
        }

        while ($offset < $rowcount ) {
            if ( empty($where_pending) === false )
                foreach ($where_pending as $key => $value) {
                    list($param_field, $param_value, $param_compare, $param__conditional) = $value;
                    $this->table->where($param_field, $param_value, $param_compare, $param__conditional);
                }

            $data = $this->table->limit($limit)->offset($offset)->findAll();

            foreach ($data as $data_key => $data_row) {
                $offset++;
                $fields_value = [];

                foreach ($fieldlist as $key => $fieldname)
                    $fields_value[] = $data_row[$fieldname]??'';
                
                $result = fputcsv($file, $fields_value, $separator, $enclosure, $escape);

                if ( $result === false ) {
                    fclose($file);
                    return false;
                }

                $lines++;
            }
        }

        fclose($file);
        return $lines;       
    }

    public function deleteAll()  : void  {
        $this->where_pending = [];
        $this->table->deleteAll();
    }

}