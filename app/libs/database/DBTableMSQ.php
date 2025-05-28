<?php

/**
 * base class for mysql database tables
 * Version 1.12.1
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace database;

use Exception;
use PDO;

class DBTableMSQ implements IDBTable, IDBView {
    protected string $type;
    protected string $where_str='';
    protected array $where_arr = [];
    protected int $limit = 0;
    protected int $offset = 0;
    protected bool $identify = false;
    protected string $orderby='';
    protected DbMSQ $db;
    protected array $pk = [];
    protected string $name = '';
    protected array $fields = [];   
    protected string $pk_query = ''; 
    protected array $meta_data = []; 
    
    public function __construct(string $name, string|callable $create_stmt, string $type='table') {
        $this->type = $type;
        $this->name = $name;
        $this->db = DbMSQ::instance();
        $sql = "SELECT 1 FROM $name LIMIT 1";  
    
        try {
            $result = $this->db->query($sql);
        } catch (\Throwable $th) {
            $result = false;
        }

        if ( $result === false )
            if ( is_string($create_stmt) )
                $this->create($create_stmt); // using given string
            else {
                $DDL = call_user_func($create_stmt);
                $stmt = $DDL->createMSQ();
                $this->create($stmt); // using given class DbDDL
            }

        $stmt = $this->db->query("DESCRIBE $this->name"); 

        if ( $stmt !== false )
            $this->meta_data = $stmt->fetchAll();
        else
            throw new Exception("cannot retrieve metadata from table {$this->name}");
       
        foreach ($this->meta_data as $key => $value) {
            if ( $value['Key'] ==  'PRI' ) {
                $keynum = $key;
                $name = $value['Field'];
                $this->pk[$keynum] = $name;
                $this->pk_query .= "$name=? and ";
            }

            $this->fields[$value['Field']] = ['type'=>$value['Type'], 'default'=>$value['Default'], 'required'=>($value['Null'] == 'NO')];            
        }

        if ( empty($this->pk) && $type == 'table' )
            throw new Exception("table {$this->name} no primary key defined");

            if ( ! empty ($this->pk_query) )
                $this->pk_query = substr($this->pk_query, 0, -5) . ';';
	}
    
    public function database() : DbMSQ {
        return $this->db;
    }
    
    public function tablename() : string {
        return $this->name;
    }

    public function primaryKey () : array {
        return $this->pk;
    }
    
    public function fieldlist () : string {
        return implode(',', array_keys($this->fields));
    }
    
    public function fields (string $field='') : array {
        if ( empty( $field) )
            return $this->fields;
        else
            return $this->fields[$field];
    }
    
    public function name () : string {
        return $this->name;
    }
    
    public function create(string $sql='') : static {
        if ( empty($sql) )
            throw new Exception("sql create statement is empty");

        if ( strpos($sql, $this->name) === false )
            throw new Exception("sql create statement invalid tablename");

        $result = $this->db->exec($sql);

        if ( $result === false ) 
            throw new Exception("sql create statement failed");

        return $this;
    }
    
    public function drop() : static {
        $sql = "DROP TABLE IF EXISTS $this->name";
        $result = $this->db->exec($sql);

        if ( $result === false )
            throw new Exception("table $this->name cannot be droped");

        return $this;
    }
    
    public function insert(array $data, bool $empty_is_null=true) : bool {
        $cols = '(';
        $params = ' values (';
        $vals = [];

        foreach ($data as $field => $value) {
            if ( !isset($value) )
                continue;

            if ( ($empty_is_null === true) && is_string($value) && empty($value) )
                continue;

            if ( !isset($this->fields[$field]) )
                throw new Exception("field: $field in table {$this->name} not defined");

            $cols .= "$field, ";
            $params .= '?, ';
            $vals[] = $value;
        }

        $cols = substr($cols, 0, -2).')';
        $params = substr($params, 0, -2).')';
        $sql = "insert into $this->name $cols $params;";
        $stmt = $this->db->prepare($sql);

        if ( $stmt === false )
            throw new Exception("insert stmt not prepared for table $this->name");
        
        $result = $stmt->execute($vals);

        if ( $result === false )
            throw new Exception("data cannot be inserted into table $this->name");
        
            return true;
    }
    
    public function delete(array|string $id) : bool {
        $sql = "delete from $this->name where $this->pk_query";
        $stmt = $this->db->prepare($sql);

        if ( $stmt === false )
            throw new Exception("delete stmt not prepared for table $this->name");

        if ( is_array($id) )
            $result = $stmt->execute($id);
        else
            $result = $stmt->execute([$id]);

        if ( $result === false )
            throw new Exception("data cannot be deleted from table $this->name");
        else
            return $result;
    }
    
    public function update(array|string $id, array $data) : bool {
        $sql = "update $this->name set ";
        $vals = [];

        foreach ($data as $field => $value) {
            if ( !isset($value) )
                continue;

            if ( !isset($this->fields[$field]) )
                throw new Exception("field: $field in table {$this->name} not defined");

            $vals[] = $value;
            $sql .= "$field=?, ";
        }

        $sql = substr($sql, 0, -2)." where $this->pk_query";
        $stmt = $this->db->prepare($sql);

        if ( $stmt === false )
            throw new Exception("update stmt not prepared for table $this->name");

        if ( is_array($id) )
            $result = $stmt->execute(array_merge($vals, $id));
        else
            $result = $stmt->execute(array_merge($vals, [$id]));

        if ( $result === false )
            throw new Exception("data cannot be updated in table $this->name");
        else
            if ( $stmt->rowCount() === 1)
                return true;
            else
                return false;
    } 
    
    public function find(array|string $id) : mixed {
        $sql = "select * from $this->name where $this->pk_query";
        $stmt = $this->db->prepare($sql);

        if ( $stmt === false )
            throw new Exception("find stmt not prepared for table $this->name");

        if ( is_array($id) )
            $result = $stmt->execute($id);
        else
            $result = $stmt->execute([$id]);

        if ( $result === false )
            throw new Exception("find data from table $this->name failed");
        else
            $result = $stmt->fetch();

        return $result;
    }
    
    public function where (string $field, mixed $value, string $compare='=', string $conditional='and') : static {
        if ( is_null($value) )
            $val = 'NULL';
        else
            $val = $value;
    
        if ( empty($this->where_str) )
            $this->where_str .= "$field $compare ?";
        else
            $this->where_str .= " $conditional $field $compare ?";

        $this->where_arr[] = $val;

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

    public function orderby (array|string $fields, string $direction='ASC') : static {
        $this->orderby = '';

        if ( is_array($fields) )
            $this->orderby = implode(',', $fields).' '.$direction;
        else
            $this->orderby = $fields.' '.$direction;

        return $this;
    }
    
    public function count(string $select='', ?array $prepared_params=null ) : int {
        $sql = '';

        if ( empty($select) )
            $sql = "SELECT * FROM $this->name ";
        else
            $sql = $select;

        $params = $prepared_params;

        if ( !empty($this->where_str) ) {
            if ( !is_null($prepared_params) ) {
                $sql .= " AND $this->where_str";
                $params = array_merge($prepared_params, $this->where_arr);
            }
            else {
                $sql .= " WHERE $this->where_str";
                $params = $this->where_arr;
            }

            $this->where_str = '';
            $this->where_arr = [];
        }

        $stmt = $this->db->prepare("SELECT count(*) from ($sql) as tmp_count_table");

        if ( $stmt === false )
            throw new Exception("count stmt not prepared for table $this->name");

        $result = $stmt->execute($params);

        if ( $result === false )
            throw new Exception("count data from table $this->name failed");

        $result = $stmt->fetchColumn(0);

        if ( $result === false )
            return 0;
        else
            return intval($result);
    }
    
    public function findFirst(string $select='', ?array $prepared_params=null) : array  {
        $result = $this->limit(1)->findAll($select, $prepared_params);

        if ( empty($result) )
            return [];

        return $result[0];
    }
    
    public function findAll(string $select='', ?array $prepared_params=null) : array {
        $sql = '';
        $include_rowid = $this->identify == true ? ", {$this->pk[0]} as row_identifier " : '';

        if ( empty($select) )
            $sql = "SELECT * $include_rowid FROM $this->name ";
        else
            $sql = preg_replace('/\bfrom/i', $include_rowid.' from ', $select);

        $params = $prepared_params;

        if ( !empty($this->where_str) ) {
            if ( !is_null($prepared_params) ) {
                $sql .= " AND $this->where_str";
                $params = array_merge($prepared_params, $this->where_arr);
            }
            else {
                $sql .= " WHERE $this->where_str";
                $params = $this->where_arr;
            }
            
            $this->where_str = '';
            $this->where_arr = [];
        }

        if ( !empty($this->orderby) ) {
            $sql .= " ORDER BY $this->orderby";
            $this->orderby = '';
        }

        if ( $this->limit > 0 ) {
            $sql .= " LIMIT $this->limit";
            $this->limit = 0;
            
            if ( $this->offset > 0 ) {
                $sql .= " OFFSET $this->offset";
                $this->offset = 0;
            }
        }

        $stmt = $this->db->prepare($sql.' ');

        if ( $stmt === false )
            throw new Exception("findAll stmt not prepared for table $this->name");

        $result = $stmt->execute($params);

        if ( $result === false )
            throw new Exception("findAll data from table $this->name failed");

        return $stmt->fetchAll();
    }
    
    public function findColumn (string $column, string $select='') : array {
        $sql = '';

        if ( empty($select) )
            $sql = "SELECT $column FROM $this->name ";
        else
            $sql = "$select FROM $this->name ";

        if ( !empty($this->where_str) ) 
            $sql .= " WHERE $this->where_str";

        $params = $this->where_arr;
        $this->where_str = '';
        $this->where_arr = [];

        if ( !empty($this->orderby) ) {
            $sql .= " ORDER BY $this->orderby";
            $this->orderby = '';
        }

        if ( $this->limit > 0 ) {
            $sql .= " LIMIT $this->limit";
            $this->limit = 0;
            
            if ( $this->offset > 0 ) {
                $sql .= " OFFSET $this->offset";
                $this->offset = 0;
            }
        }

        $stmt = $this->db->prepare($sql);

        if ( $stmt === false )
            throw new Exception("findColumn stmt not prepared for table $this->name failed");

        $result = $stmt->execute($params);

        if ( $result === false )
            throw new Exception("findAll data from table $this->name failed");

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function deleteAll() : void {
        $sql = "DELETE FROM $this->name ";

        if ( !empty($this->where_str) ) {
            $sql .= " WHERE $this->where_str";
            $params = $this->where_arr;
            $this->where_str = '';
            $this->where_arr = [];
        }
        else
            $params = null;

        $stmt = $this->db->prepare($sql.' ');

        if ( $stmt === false )
            throw new Exception("deleteAll stmt not prepared for table $this->name");

        $result = $stmt->execute($params);

        if ( $result === false )
            throw new Exception("deleteAll data from table $this->name failed");
    }

}
