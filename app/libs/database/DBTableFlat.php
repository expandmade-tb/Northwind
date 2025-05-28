<?php

/**
 * base class for flat database tables
 * Version 1.5.1
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace database;

use Exception;
use Flatfiles\FlatTable;

class DBTableFlat implements IDBTable {
    private int $limit;
    private int $offset;
    protected DbFlat $db;
    protected string $type;
    protected FlatTable $table;
    protected bool $identify = false;
    protected array $order_by;
    protected string $sort_direction = 'ASC';
    
    public function __construct(string $name, string|callable $create_stmt, string $type='table') {
        $this->type = $type;
        $this->db = DbFlat::instance();

        if ( is_string($create_stmt) )
            $stmt = $create_stmt; // using given string
        else {
            $DDL = call_user_func($create_stmt);
            $stmt = $DDL->createFlat();
        }

        $this->table = new FlatTable($this->db->path(), $name, $stmt);
	}
    
    public function database() : DbFlat{
        return $this->db;
    }
    
    public function tablename() : string {
        return $this->table->name();
    }

    public function primaryKey () : array {
        return [$this->table->primaryKey()];
    }
    
    public function fieldlist () : string {
        return $this->table->fieldlist();
    }
    
    public function fields (string $field='') : array {
        return $this->table->fields($field);
    }
    
    public function name () : string {
        return $this->table->name();
    }
    
    public function create(string $sql='') : static  {
        $this->table->create();
        return $this;
    }
    
    public function drop() : static {
        $this->table->drop();
        return $this;
    }
    
    public function insert(array $data, bool $empty_is_null=true) : bool {
        return $this->table->insert($data);
    }
    
    public function delete(array|string $id) : bool {
        if ( is_array($id) )
            $key = implode('_', $id);
        else
            $key = $id;

        return $this->table->delete($key);
    }
    
    public function update(array|string $id, array $data) : bool {
        if ( is_array($id) )
            $key = implode('_', $id);
        else
            $key = $id;

        $result = $this->table->update($key, $data);

        if ( $result === false )
            return false;
        else
            return true;
    } 
    
    public function find(array|string $id) : mixed {
        if ( is_array($id) )
            $key = implode('_', $id);
        else
            $key = $id;

        return $this->table->find($key);
    }
    
    public function where (string $field, mixed $value, string $compare='=', string $conditional='and') : static {
        $this->table->where($field, $value, $compare, $conditional);
        return $this;
    }

    public function limit(int $limit=0) : static {
        $this->limit = $limit;
        return $this;
    }

    public function offset (int $offset=0) : static {
        $this->offset = $offset;
        return $this;
    }

    public function identify (bool $identify=false) : static {
        $this->identify = $identify;
        return $this;
    }

    public function orderby (array| string $fields, string $direction='ASC') : static {
        if ( is_string($fields) )
            $this->order_by = array_map('trim',explode(',', $fields));
        else
            $this->order_by = $fields;

        foreach ($this->order_by as $key => $value) {
            if ( !array_key_exists($value, $this->fields()) )
                throw new Exception("field $value unknown");               
        }

        $this->sort_direction = $direction;
        return $this;
    }
    
    public function count(string $select='', ?array $prepared_params=null ) : int {
        return $this->table->count();
    }
    
    public function findFirst(string $select='', ?array $prepared_params=null) : array  {
        $result = $this->limit(1)->findAll($select, $prepared_params);

        if ( empty($result) )
            return [];

        return $result[0];
    }
    
    public function findAll(string $select='', ?array $prepared_params=null)  : array {
        $offset = isset($this->offset) ? $this->offset : null;
        $limit = isset($this->limit) ? $this->limit : null;
        $result = $this->table->findAll($limit, $offset);

        if ( isset($this->limit) )
            unset($this->limit);
            
        if ( isset($this->offset) )
            unset($this->offset);

        if ( !$this->identify ) 
            if ( isset($this->order_by) ) {
                usort($result, [$this, 'compare']);
                return $result;
            } 

        $pk = $this->table->primaryKey();

        foreach ($result as $key => $value)
            $result[$key]['row_identifier'] = $result[$key][$pk];
        
        if ( isset($this->order_by) )
            usort($result, [$this, 'compare']);
        
        return $result; 
    }
    
    public function findColumn (string $column, string $select='')  : array {
        trigger_error('not supported');
        return [];
    }

    public function compare(mixed $a, mixed $b) : int {
        $a_value = '';
        $b_value = '';

        foreach ($this->order_by as $key => $value) {
            $a_value .= $a[$value];
            $b_value .= $b[$value];
        }

        return $a_value <=> $b_value;
    }

    public function deleteAll()  : void {
        trigger_error('not supported');
   }
}