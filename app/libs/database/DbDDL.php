<?php

namespace database;

/**
 * DDL creator class
 * Version 1.2.2
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

class DbDDL {
    private static ?DbDDL $instance = null;
    private string $table = '';
    private string $primary_key = '';
    private array $fields = [];
    private array $foreign_keys = [];
    private array $unique = [];
    private array $unique_constraint = [];
    private array $indexes = [];

    function __construct(string $table) {
        $this->table = $table;
    }

    public static function table (string $table) : DbDDL {
        self::$instance = new DbDDL($table);
        return self::$instance;
    }
  
    public function integer(string $name, bool $not_null=false, bool $auto_increment=false, bool $unique=false, ?int $default=null) : DbDDL {
        $this->fields[$name] = ['type'=>'integer', 'not_null'=>$not_null, 'auto_increment'=>$auto_increment, 'unique'=>$unique, 'default'=>$default];
        return $this;
    }

    public function text(string $name, int $size=255, bool $not_null=false, bool $unique=false, ?string $default=null) : DbDDL {
        if (!is_null($default))
            $default = '"'.$default.'"';

        $this->fields[$name] = ['type'=>'text', 'size'=> $size,'not_null'=>$not_null, 'auto_increment'=>false, 'unique'=>$unique, 'default'=>$default];
        return $this;
    }

    public function real(string $name, bool $not_null=false, ?float $default=null) : DbDDL {
        $this->fields[$name] = ['type'=>'real', 'not_null'=>$not_null, 'auto_increment'=>false, 'unique'=>false, 'default'=>$default];
        return $this;
    }

    public function blob(string $name, bool $not_null=false) : DbDDL {
        $this->fields[$name] = ['type'=>'blob', 'not_null'=>$not_null, 'auto_increment'=>false, 'unique'=>false, 'default'=>null];
        return $this;
    }

    public function datetime(string $name, bool $not_null=false, bool $unique=false, ?string $default=null) : DbDDL {
        $this->fields[$name] = ['type'=>'datetime', 'not_null'=>$not_null, 'auto_increment'=>false, 'unique'=>$unique, 'default'=>$default];
        return $this;
    }

    public function numeric(string $name, bool $not_null=false, ?string $default=null) : DbDDL {
        $this->fields[$name] = ['type'=>'numeric', 'not_null'=>$not_null, 'auto_increment'=>false, 'unique'=>false, 'default'=>$default];
        return $this;
    }

    public function unique(string $fields) : DbDDL {
        $this->unique[] = $fields;
        return $this;
    }

    public function unique_constraint(string $fields) : DbDDL {
        $this->unique_constraint[] = $fields;
        return $this;
    }

    public function primary_key(string $fields) : DbDDL {
        $this->primary_key = $fields;
        return $this;
    }

    public function foreign_key(string $fields, string $parent_table, string|array $primary_key) : DbDDL {
        $this->foreign_keys[$fields] = ['parent_table'=>$parent_table, 'primary_key'=>$primary_key];
        return $this;
    }

    public function index(string $fields, string $index_name='') : DbDDL {
        if (!empty($index_name)) {
            $i = count($this->indexes) + 1;
            $index_name = "idx_{$this->table}_$i";
        }
        else
           $index_name = "idx_{$this->table}_1";


        $this->indexes[$index_name] = $fields;
        return $this;
    }

    public function createFlat() : string {
        $sql = '';

        foreach ($this->fields as $field => $values) {
            $type = strtoupper($values['type']);

            if ( !empty($this->primary_key && $this->primary_key == $field) )
                $sql .= "{$field} {$type} PRIMARY_KEY, ";
            else
                $sql .= "{$field} {$type}, ";
        }

        $sql = substr($sql, 0, -2).')';
        return $sql;
    }

    public function createMSQ() : string {
        $sql = "create table $this->table (";

        foreach ($this->fields as $field => $values) {
            switch ($values['type']) {
                case 'integer':
                    $type='INT';
                    break;
                case 'text':
                    $size = $values['size'];
                    $type="VARCHAR($size)";
                    break;
                case 'real':
                    $type='FLOAT';
                    break;
                case 'blob':
                    $type='BLOB';
                    break;
                case 'datetime':
                    $type='DATETIME';
                    break;
                default:
                    $type='INT';
                    break;
            }

            $not_null = $values['not_null'] === true ? ' NOT NULL' : '';

            if ( $values['auto_increment'] === true ) {
                $auto_increment = ' AUTO_INCREMENT';

                if (empty($this->primary_key))
                    $this->primary_key($field);
            }
            else
                $auto_increment = '';

            if ( $values['unique'] === true )
                $this->unique($field);

            $default = is_null($values['default']) ? '' : " DEFAULT {$values['default']}";
            $sql .= "{$field} {$type}{$not_null}{$default}{$auto_increment}, ";
        }

        if ( !empty($this->primary_key) )
            $sql .= "PRIMARY KEY($this->primary_key), ";

        if ( !empty($this->unique) )
            foreach ($this->unique as $key => $value)
                $sql .= "UNIQUE($value), ";

        if ( !empty($this->unique_constraint) )
            foreach ($this->unique_constraint as $key => $value) {
                $constraint_name = "{$this->table}_constraint{$key}";
                $sql .= "CONSTRAINT $constraint_name UNIQUE ($value), ";
            }
        
        if ( !empty($this->foreign_keys) )
            foreach ($this->foreign_keys as $key => $value) {
                $parent_table = $value['parent_table'];
                $parent_pk = $value['primary_key'];
                $sql .= "FOREIGN KEY($key) REFERENCES $parent_table ($parent_pk), ";
            }
            
        $sql = substr($sql, 0, -2).')';

        if (!empty($this->indexes)) {
            $sql .= ';';

            foreach ($this->indexes as $index => $fields) {
                $table = $this->table;
                $sql .= "CREATE INDEX $index ON $table ($fields);";
            }
        }

        return $sql;
    }

    public function createSQ3() : string {
        $sql = "create table $this->table (";

        foreach ($this->fields as $field => $values) {
            $type = strtoupper($values['type']);
            $not_null = $values['not_null'] === true ? ' NOT NULL' : '';
            $default = is_null($values['default'])  ? '' : " DEFAULT {$values['default']}";

            if ( $values['auto_increment'] === true ) {
                $this->primary_key("$field AUTOINCREMENT");
                $not_null = '';
            }

            $unique = $values['unique'] === true && $values['auto_increment'] !== true ? ' UNIQUE' : '';
            $sql .= "{$field} {$type}{$not_null}{$default}{$unique}, ";
        }

        if ( !empty($this->primary_key) )
            $sql .= "PRIMARY KEY($this->primary_key), ";

        if ( !empty($this->unique) )
            foreach ($this->unique as $key => $value)
                $sql .= "UNIQUE($value), ";

        if ( !empty($this->unique_constraint) )
            foreach ($this->unique_constraint as $key => $value) {
                $constraint_name = "{$this->table}_constraint{$key}";
                $sql .= "CONSTRAINT $constraint_name UNIQUE ($value), ";
            }
    
        if ( !empty($this->foreign_keys) )
            foreach ($this->foreign_keys as $key => $value) {
                $parent_table = $value['parent_table'];
                $parent_pk = $value['primary_key'];
                $sql .= "FOREIGN KEY($key) REFERENCES $parent_table ($parent_pk), ";
            }
            
        $sql = substr($sql, 0, -2).')';

        if (!empty($this->indexes)) {
            $sql .= ';';
            
            foreach ($this->indexes as $index => $fields) {
                $table = $this->table;
                $sql .= "CREATE INDEX $index ON $table ($fields);";
            }
        }

        return $sql;
    }

}