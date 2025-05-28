<?php

namespace Flatfiles;

use DirectoryIterator;
use Exception;

/**
 * Flatfile Tables 
 * Version 1.2.1
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

 class FlatTable {
    private string $path;
    private string $name;
    private string $fullpath;
    private string $pk;
    private array $fields;
    private array $where_pending;
    
    /**
     * class constructor
     *
     * @param string $path path to the subdirectory which holds all data. SQL: dbname
     * @param string $name directory which does hold all data. SQL: table
     * @param string $fields a comma separated list of fields. 
     * 
     * @example:
     *    user_id INTEGER PRIMARY_KEY,
     *    name TEXT,
     *    mail TEXT REQUIRED';
     * 
     * @return void
     * 
     * @throws exception
     */
    function __construct ( string $path, string $name, string $fields ) {
        $this->path = $path;
        $this->name = $name;
        $this->fullpath = $this->path.DIRECTORY_SEPARATOR.$this->name.DIRECTORY_SEPARATOR;
        $meta_data = array_map('trim',explode(',', $fields)); 

        foreach ( $meta_data as $field => $value) {
            $field = array_map('trim',explode(' ', $value));
            $field_name = $field[0];

            if ( isset($this->fields[$field_name]) ) 
                throw new Exception("duplicate field name '$field_name'");

            $field_type = $field[1]??'TEXT';

            if ( strcmp($field[2]??'', 'PRIMARY_KEY') === 0) {
                $this->pk = $field_name; 
                $field_required = true; 
            }
            else
                $field_required = (($field[2]??'') == 'REQUIRED');

            $this->fields[$field_name] = ['type'=>$field_type, 'required'=>$field_required];
        }

        if ( empty ($this->pk) )
            throw new Exception("no primary key defined");

        if ( !is_dir(rtrim($this->fullpath, DIRECTORY_SEPARATOR)) )
            $this->create();
    }

    protected function validateFields (array $data) : void {
        foreach ($data as $key => $value)
            if ( !isset($this->fields[$key]))
                throw new Exception("field {$key} is unknown");
    }

    protected function where_pending(array $data) : bool {
        $operator = [
            '='   => '==',
            '!='  => '!=',
            '>'   => '>',
            '<'   => '<',
            '>='  => '>=',
            '<='  => '<=',
            'and' => '&&',
            'or'  => '||',
            'like' => 'like',
            'LIKE' => 'like'
        ];

        $clause ='';
        $result = true;

        foreach ($this->where_pending as $key => $condition) {
            $field = $condition["field"];
            $op = $operator[$condition["op"]];
            $value = $condition["value"];
            $type = $condition["type"];

            if ( $op == 'like')
                $clause .= "$type strpos(\$data[\$field], \$value) !== false ";
            else
                $clause .= "$type \$data[\$field] $op \$value ";
        }

        try {
            eval('$result = ' . $clause . ';');
            return $result;
        } catch (\Throwable $th) {
            return false;
        }
    }
    
    /**
     * returns the primary key of the table
     *
     * @return string
     */
    public function primaryKey() : string {
        return $this->pk;
    }
    
    /**
     * returns a comma separated list of the fieldnames
     *
     * @return string
     */
    public function fieldlist() : string {
        return implode(',', array_keys($this->fields));
    }
    
    /**
     * returns the meta information of either one field or of all available fields
     *
     * @param string $field [explicite description]
     *
     * @return array
     */
    public function fields(string $field='') : array {
        if ( empty( $field) )
            return $this->fields;
        else
            return $this->fields[$field]??'';
    }
    
    /**
     * returns the actual tablename
     *
     * @return string
     */
    public function name() : string {
        return $this->name;
    }
    
    /**
     * checks if an id exist
     *
     * @param $id the id to check
     *
     * @return bool
     */
    public function idExists(string $id) : bool {
        if ( file_exists($this->fullpath.$id) )
            return true;
        else
            return false;
    }
        
    /**
     * inserts a new row
     *
     * @param array $data a key => value list
     *
     * @return bool
     * 
     * @throws exception
     */
    public function insert(array $data) : bool {
        $primary_key = $data[$this->pk]??'';

        if ( empty($primary_key) )
            throw new Exception("table {$this->name} primary key is misssing");

        $this->validateFields($data);

        if ( $this->idExists($primary_key) === true)
            throw new Exception("table {$this->name} duplicate primary key");

        $file = fopen($this->fullpath.$primary_key, 'w');
        
        if ( $file === false ) 
            throw new Exception("table {$this->name} cannot open");

        $result = fwrite($file, serialize($data));

        if ( $result === false)
            throw new Exception("table {$this->name} cannot write");

        fclose($file);
        return true;
    }
    
    /**
     * updates an existing row
     *
     * @param string $id the id to update
     * @param array $data a key => value list
     *
     * @return array | false
     * 
     * @throws exception
     */
    public function update(string $id, array $data) : array|false {
        $this->validateFields($data);

        if ( isset($data[$this->pk]) && $data[$this->pk] != $id )
            throw new Exception("table {$this->name} primary key can not be modified");
            
        if ( !$this->idExists($id) )
            return false;

        $file = fopen($this->fullpath.$id, 'r+');
        
        if ( $file === false )
            throw new Exception("table {$this->name} cannot open");

        if ( flock($file, LOCK_EX) === false) {
            fclose($file);
            throw new Exception("table {$this->name} cannot lock");
        }

        $size = filesize($this->fullpath.$id);
        $result = fread($file, $size === false ? 1 : $size + 1);

        if ( $result === false ) {
            fclose($file);
            throw new Exception("table {$this->name} cannot read");
        }

        $current_data = unserialize($result);

        if ( $current_data === null )  {
            fclose($file);
            throw new Exception("table {$this->name} cannot decode");
        }

        $updated_data = array_merge($current_data, $data);
        rewind($file);
        $result = fwrite($file, serialize($updated_data));

        if ( $result === false ) {
            fclose($file);
            return false;
        }
      
        return $updated_data;
    }
    
    /**
     * retuns the row data of the given id
     *
     * @param string $id the id to find
     *
     * @return array | false
     * 
     * @throws exception
     */
    public function find(string $id) : array|false {
        if ( !$this->idExists($id) )
            return false;
            
        $data = file_get_contents($this->fullpath.$id);

        if ( $data === false)
            return false;

        $data = unserialize($data);
            
        if ( $data === null )
            throw new Exception("{$id} cannot decode data");

        return $data;
    }
    
    /**
     * deletes the row with the given id
     *
     * @param string $id [explicite description]
     *
     * @return bool
     */
    public function delete(string $id) : bool {
        return unlink($this->fullpath.$id);
    }
    
    /**
     * counts all rows of the table which meet the criteria
     *
     * @return int
     */
    public function count() : int {
        $counter = 0;
        $di = new DirectoryIterator(rtrim($this->fullpath, DIRECTORY_SEPARATOR));

        foreach ( $di as $file) {
            if( $file->isDot() )
                continue;

            if ( isset($this->where_pending) ) {
                $data = $this->find($file->getFilename());

                if ( $data === false )
                    $data = [];

                if ( $this->where_pending($data) === false )
                    continue;
            }
            
            $counter++;    
        }

        unset($this->where_pending);
        return $counter;
    }
        
    /**
     * creates the table  
     *
     * @return FlatTable $this 
     * 
     * @throws exception
     */
    public function create() : FlatTable {
        if ( !is_dir($this->path) )
            if ( mkdir($this->path, 0755) === false )
                throw new Exception("table $this->name cannot be created");

        if ( !is_dir(rtrim($this->fullpath, DIRECTORY_SEPARATOR)) )
            if ( mkdir($this->fullpath, 0755) === false)
                throw new Exception("table $this->name cannot be created");

        return $this;
    }
    
    /**
     * drops the table
     *
     * @return FlatTable $this
     * 
     * @throws exception
     */
    public function drop() : FlatTable  {
        if ( !is_dir(rtrim($this->fullpath, DIRECTORY_SEPARATOR)) )
            return $this;

        $di = new DirectoryIterator(rtrim($this->fullpath, DIRECTORY_SEPARATOR));

        foreach ( $di as $file)
            if( !$file->isDot())
                $this->delete($di->getFilename());
                
        $result = rmdir(rtrim($this->fullpath, DIRECTORY_SEPARATOR));

        if ( $result === false )
            throw new Exception("table $this->name cannot be droped");

        return $this;
    }
    
    /**
     * add a where condition
     *
     * @param string $field the fieldname
     * @param string $value the value to check for
     * @param string $op the operator ['=','!=','>','<','>=','<=','like']
     * @param $conditional logiacal operator ['and','or']
     *
     * @return FlatTable $this
     */
    public function where(string $field, string $value, string $op='=', string $conditional='and') : FlatTable {
        if ( !isset($this->where_pending) )
            $conditional = '';
        
        if ( !isset($this->fields[$field]) ) // field might contain a sql function
            foreach ($this->fields as $key => $data) { // try to filter just the field name
                $field_filter = preg_match("/\b$key\b/", $field);

                if ( $field_filter === 1 ) {
                    $field = $key;
                    break;
                }
            }

        if ( !isset($this->fields[$field]) )
           throw new Exception("field {$field} unknown");

        $evil = [
        '/\bsystem\b/i', 
        '/\bshell_exec\b/i',
        '/\bexec\b/i',
        '/\bpassthru\b/i'];
        
        $value = str_replace('`', '', $value); // remove all backticks
        $value = preg_replace($evil, ['','','',''], $value); // try to filter some shit
        $this->where_pending[] = ['type'=>$conditional, 'field'=>$field, 'op'=>$op, 'value'=>$value];
        return $this;
    }
     
    /**
     * returns all row which meet the criteria
     *
     * @param ?int $limit maximum no. of rows to return
     * @param ?int $offset offset to start
     *
     * @return array
     */
    public function findAll(?int $limit=null, ?int $offset=null) : array {
        $result = [];
        $offset_cnt = 0;
        $limit_cnt = 0;
        $di = new DirectoryIterator(rtrim($this->fullpath, DIRECTORY_SEPARATOR));

        foreach ( $di as $file) {
            if( $file->isDot() )
                continue;

            if ( isset($offset) && $offset_cnt < $offset ) {
                $offset_cnt++;
                continue;
            }

            $data = $this->find($file->getFilename());

            if ( $data === false )
                continue;

            if ( isset($this->where_pending) )
                if ( $this->where_pending($data) === false )
                    continue;

            $limit_cnt++;
            $result[] = $data;

            if ( isset($limit) && $limit_cnt >= $limit )
                break;
       }

       unset($this->where_pending);
       return $result;
    }

}