<?php

/**
 * CRUD for database tables
 * Version 1.27.0
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace dbgrid;

use database\DBTable;
use Exception;
use InvalidArgumentException;
use helper\Helper;

class DbCrud {
    public string $grid_add = '<a class="btn btn-primary" href="[:script_name]/add" role="button"><i class="bi bi-plus-circle"></i> Add</a>';    
    public string $grid_show = '<a class="btn btn-info btn-sm" href="[:script_name]/show/[:identifier]" role="button"><i class="bi bi-eyeglasses"></i> Show</a>';
    public string $grid_edit = '<a class="btn btn-warning btn-sm" href="[:script_name]/edit/[:identifier]" role="button"><i class="bi bi-pen"></i> Edit</a>';
    public string $grid_delete = '<a class="btn btn-danger btn-sm" href="[:script_name]/delete/[:identifier]" role="button"><i class="bi bi-trash"></i> Delete</a>';
    public string $grid_search = '<form class="d-flex" method="post"> <input class="form-control" type="search" name="search" value="[:search]" placeholder="Search" aria-label="Search"><a href="[:script_name]/clear" style="margin: 0 10px 0 -20px; display: inline-block;" title="Clear Search">x</a> <button class="btn btn-primary" name="search_submit" type="submit"><i class="bi bi-search"></i></button> </form>';
    public string $grid_title = '';
    public string $form_save = '<i class="bi bi-check-circle"></i> save';
    public string $form_back = '<i class="bi bi-arrow-left"></i> back';
    public string $form_delete = '<i class="bi bi-trash"></i> delete';

    public int $limit = 100;                        // sql selection limit for one page
    public string $grid_sql = '';                   // sql from grid selection
    public ?array $grid_sql_params = null;          // params for grid sql
    public string $date_fmt = 'Y-m-d';              // the output format for dates
    public string $time_fmt = 'G:i';                // the output format for time
    public bool $show_titles = true;

    protected DBTable $table;                       // database table we are using
    protected FormHandler $formHandler;
    protected array $field_titles;                  // titles / headers for fields
    protected array $field_types;                   // the field property / value pairs
    protected array $grid_fields;                   // fields to show in grid
    protected array $search_fields;                 // searchable fields
    protected array $callback_fields;               // fields to callback to format values
    protected string $primaryKey;                   // for now just a single field which is pk
    protected string $grid_info = '';               // shows an information at the top of the grid
    protected array $uri;                           // current uri split into its parts
    protected bool $encode_identifier = false;
    protected mixed $callback_delete;

    /**
     * Constructor initializes the DBTable instance and default field types.
     */
    public function __construct(DBTable $table) {
        $this->table = $table;
        $this->grid_title = $table->name();
        $this->formHandler = new FormHandler($this);

        if ( count($this->table->primaryKey()) > 1 )
            throw new Exception("only single column primary keys supported");
            
        $this->uri = $this->current_uri();
        $this->primaryKey = $this->table->primaryKey()[0]??'';;
        $this->fields($this->table->fieldlist());
        
        // initialze basic datatypes
        foreach (explode(',',$this->table->fieldlist()) as $key => $field ) {
            switch ($this->table->fields($field)['type']) {
                case 'INTEGER':
                    $this->setFieldProperty($field, type: 'integer');
                    break;
                case 'REAL':
                    $this->setFieldProperty($field, type: 'numeric');
                    break;
                case 'NUMERIC':
                    $this->setFieldProperty($field, type: 'numeric');
                    break;
                default:
                    $this->setFieldProperty($field, type: 'text');
                    break;
           }
        }
    }
    
    /**
     * Defines the fields to be used in add forms.
     */
    public function addFields (string $fields) : DbCrud {
        $this->formHandler->addFields = array_map('trim',explode(',', $fields));
        return $this;
    }

    /**
     * Defines the fields to be used in edit forms.
     */
    public function editFields (string $fields) : DbCrud {
        $this->formHandler->editFields = array_map('trim',explode(',', $fields));
        return $this;
    }

    /**
     * Defines the fields to be used in read-only forms.
     */
    public function readFields (string $fields) : DbCrud {
        $this->formHandler->readFields = array_map('trim',explode(',', $fields));
        return $this;
    }

    /**
     * Defines the fields to be displayed in the grid view.
     */
    public function gridFields (string $fields) : DbCrud {
        $this->grid_fields = array_map('trim',explode(',', $fields));
        return $this;
    }

    /**
     * Defines which fields are searchable via the search box.
     */
    public function searchFields (string $fields) : DbCrud {
        $this->search_fields = array_map('trim',explode(',', $fields));
        return $this;
    }

    /**
     * Defines specific fields as readonly in forms.
     */
    public function readonlyFields (string $fields) : DbCrud {
        foreach (array_map('trim',explode(',', $fields)) as $key => $value)
            $this->formHandler->readonlyFields[$value] = $key;

        return $this;
    }

    /**
     * Defines specific fields as required in forms.
     */
    public function requiredFields (string $fields) : DbCrud {
        foreach (array_map('trim',explode(',', $fields)) as $key => $value)
            $this->formHandler->requiredFields[$value] = $key;

        return $this;
    }

    /**
     * Assigns a placeholder text for a form field.
     */
    public function fieldPlaceholder (string $field, string $placeholder) : DbCrud {
        $this->formHandler->fieldPlaceholders[$field] = $placeholder;
        return $this;
    }
    
    /**
    * @deprecated deprecated, use setFieldProperty instead
    */
    public function fieldType (string $field, string $type, string $valuelist='', int $rows=2, int $cols=40) : DbCrud {
        if ( !in_array($type, ['text', 'integer', 'numeric', 'checkbox', 'select', 'date', 'datetext', 'datetime', 'datalist', 'textarea', 'timetext','grid']) )
            throw new Exception("unsupported field type $type");

        if ( $type == 'checkbox' && empty($valuelist ) )
            $valuelist = '0,1';
             
        if ( in_array($type, ['grid','textarea']) )
            $this->field_types[$field] = ['type'=>$type, 'values'=>$valuelist, 'rows'=>$rows, 'cols'=>$cols];
        else
            $this->field_types[$field] = ['type'=>$type, 'values'=>$valuelist]; 

        return $this;
    }

    /**
     * Sets multiple properties for a field, such as type, values, etc.
     * 
     *  @param string $field the fields name
     *  @param string $config. current valid properties are:
     *                'type, relation', 'search', 'text', 'integer', 'numeric', 'checkbox', 'select',
     *       '         date', 'datetext', 'datetime', 'datalist', 'textarea', 'timetext', 'grid'
     * 
     */
    public function setFieldProperty(string $field, ...$config) : DbCrud {
        if (!isset($config['type'])) {
            throw new InvalidArgumentException("Missing 'type' key in field configuration.");
        }

        foreach ($config as $property => $value) {
            $type = $config['type'];
    
            switch ($type) {
                case 'checkbox':
                    if ( empty($config['values']) )
                        $this->field_types[$field]['values'] = '0,1';
                    
                    break;
                case 'textarea':
                case 'grid':
                    if ( empty($config['rows']) )
                        $this->field_types[$field]['rows'] = 2;

                    if ( empty($config['cols']) )
                        $this->field_types[$field]['cols'] = 2;

                    break;
            }
        
            $allowedTypes = [
                'type', 'relation', 'search', 'text', 'integer', 'numeric', 'checkbox', 'select',
                'date', 'datetext', 'datetime', 'datalist', 'textarea', 'timetext', 'grid'
            ];
        
            if (!in_array($type, $allowedTypes, true)) {
                throw new InvalidArgumentException("Invalid field type: {$type}");
            }
        
            $this->field_types[$field][$property] = $value;
        }

        return $this;
    }
    
    /**
     * Retrieves a single property of a given field.
     * 
     *  @param string $field the field name
     *  @param string $property. the property to retrieve
     */
    public function getFieldProperty(string $field, string $property) : string {
        return $this->field_types[$field][$property] ?? '';
    }
    
    /**
     * Retrieves all configured properties of a given field.
     * 
     *  @param string $field the field name
     */
    public function getFieldProperties(string $field) : array {
        return $this->field_types[$field] ?? [];
    }
    
    /**
     * Sets a static value for a specific field.
     */
    public function fieldValue (string $field, string $value) : DbCrud{
        $this->formHandler->fieldValues[$field] = $value;
        return $this;
    }

    /**
     * Registers a JavaScript onchange handler for a field, with optional mapping and defer flag.
     */
    public function fieldOnchange(string $field, string $method, array $mapping, bool $defer=false) : DbCrud {
        $this->formHandler->fieldOnchange[$field] = ['method'=>$method, 'mapping'=>$mapping, 'defer' => $defer];
        return $this;
    }

    /**
     * Registers a JavaScript oninput handler for a field.
     */
    public function fieldOninput(string $field, string $method) : DbCrud {
        $this->formHandler->fieldOninput[$field] = ['method'=>$method];
        return $this;
    }

    /**
     * Attaches custom data attributes to a form field.
     */
    public function fieldDataAttr(string $field, mixed ...$data) : DbCrud {
        foreach ($data as $attr => $value) {
            $this->formHandler->fieldDataAttr[$field][$attr] = $value;
        }

        return $this;
    }

    /**
     * Assigns display titles to fields, optionally mapping custom titles.
     */
    public function fieldTitles (string $fields, string $titles='') : DbCrud {
        $afields = explode(',', $fields);

        if ( empty($titles) ) {
            foreach ($afields as $key => $field) {
                $atitles[$key] = ucwords(str_replace(['_', '-', '.'], ' ', $field));
            }
        }
        else
            $atitles = explode(',', $titles);

        $c1 = count($afields);
        $c2 = count($atitles);
        
        if ( $c1 != $c2 )
            throw new Exception("mismatch of fields($c1) and titles($c2)");

        $this->field_titles = array_merge($this->field_titles, array_combine($afields, $atitles));
        return $this;
    }

    /**
     * Bulk sets field usage across add/edit/read/grid and assigns default titles.
     */
    public function fields (string $fields) : DbCrud {
        $this->addFields($fields);
        $this->editFields($fields);
        $this->readFields($fields);
        $this->gridFields($fields);
        $this->field_titles = [];
        $this->fieldTitles($fields);
        return $this;
    }

    /**
     * Enables a datepicker widget for a specific field.
     */
    public function setDatepicker (string $field, string $format='yyyy-mm-dd', string $type='text') : DbCrud {
        $this->setRule('field', 'date');
        $defined_type = $this->getFieldProperty($field, 'type'); // make sure there isnt already a type defined
        $type = empty($defined_type) ? $type : $defined_type;
        $this->setFieldProperty($field, type: $type, datepicker: $format);
        return $this;
    }

    /**
     * Defines a relation for a field to another table's field.
     */
    public function setRelation(string $field, string $relatedField, string $relatedTable) : DbCrud {
        $this->setFieldProperty($field, type: 'relation', rel_table: $relatedTable, rel_field: $relatedField);
        return $this;
    }

    /**
    * @deprecated deprecated, use fieldType instead
    */
    public function setGrid(string $field, int $rows, int $cols) : DbCrud {
        $this->setFieldProperty($field, type: 'grid', rows: (string)$rows, cols: (string)$cols);
        return $this;
    }

    /**
     * Defines a live search for a field
     */
    public function setSearchRelation(string $field, string $relatedTable, string $relatedField, bool $constraint=true) : DbCrud {
        $this->setFieldProperty($field, type: 'search', rel_table: $relatedTable, rel_field: $relatedField, constraint: (string)$constraint);
        $this->fieldOninput($field, "{$relatedTable}Oninput");
        return $this;
    }

    /**
     * Registers a custom callback to format a field's value in grid view.
     */
    public function formatField(string $field, callable $callable) : DbCrud {
        $this->callback_fields[$field] = $callable;
        return $this;
    }

    /**
     * Attaches a linked table controller with a button to open it.
     */
    public function linkedTable(string $controller, string $button_value, string $method='index') : DbCrud {
        unset($this->linked_table);
        $this->formHandler->linkedTable['controller'] = $controller;
        $this->formHandler->linkedTable['button_value'] = $button_value;
        $this->formHandler->linkedTable['method'] = $method;
        return $this;
    }

    /**
     * Adds a subform rendered through a callback method and shown via a button.
     */
    public function subForm(callable $controller, string $button_value) : DbCrud {
        unset($this->subform);
        $this->formHandler->subform['callback'] = $controller;
        $this->formHandler->subform['button_value'] = $button_value;
        return $this;
    }

    /**
     * Registers an exception handler callback for form actions.
     */
    public function onException(callable $callback) : DbCrud {
        $this->formHandler->callbackException = $callback;
        return $this;
    }

    /**
     * Manually defines the form layout grid using row/column schema.
     */
    public function layout_grid(array $rows) : DbCrud {
        $this->formHandler->rows = $rows;
        return $this;
    }

    /**
     * Adds dependency constraints to a field (used for conditional filtering).
     */
    public function setContstraints(string $field, string $depending_table, string $depending_field) : DbCrud {
        $this->formHandler->constraints[$field][] = ['table'=>$depending_table,  'field'=>$depending_field];
        return $this;
    }

    /**
     * Replaces the default SQL used for fetching grid data.
     */
    public function gridSQL (string $sql, ?array $params=null ) : DbCrud {
        $this->grid_sql = $sql;
        $this->grid_sql_params = $params;
        return $this;
    }

    /**
     * Renders the grid view for the current table with pagination and sorting.
     */
    public function grid (int $page=1, string $orderby='') : string {
        if ( empty($orderby) && !empty($_REQUEST['orderby']) )
            $orderby = $_REQUEST['orderby'];

        ob_start();
        $this->show_grid($page, $orderby);
        $html = ob_get_clean();

        if ( $html === false )
            $html = 'error';

        return '<div id="dbc-container">' . $html . '</div>';
    }

    /**
     * Renders a form for add/edit/show actions based on the route action and optional record ID.
     */
    public function form(string $action, string $id='', string $msg='', string $wrapper='') : string {
        return $this->formHandler->handle($action, $id, $msg, $wrapper);
    }

    /**
     * deletes a record in the database
     *
     * @param string $id the primary key to the record to be deleted
     *
     * @return int|false
     */
    public function delete(string $id) : int|false {
        $page = $_REQUEST['last_page']??1;

        if ( $this->encode_identifier )
            $id = $this->base64url_decode($id);

        try {
            if ( isset($this->callback_delete) )
                $result = call_user_func($this->callback_delete, $id);
        else
            $result = $this->table->delete($id); 
        } catch (\Throwable $th) {
            $this->grid_info = '<div class="alert alert-danger" role="alert">'.$th->getMessage().'</div>';            
            $result = false;
        }

        if ( $result !== false )
            return intval($page);
        else
            return false;
    }

    /**
     * Clears the current search and redirects to the default grid view.
     */
    public function clear() : void {
        $to = '/'.$this->get_uri('class');
        Helper::redirect("$to/grid/1");
    }

    /**
     * Adds a validation rule (function or method name) to a field.
     */
    public function setRule(string $field, string|callable $callback) : DbCrud {
        $this->formHandler->callbackRules[$field] = $callback;
        return $this;
    }

    /**
     * Registers a custom callback for update operations.
     */
    public function callbackUpdate(callable $callback) : DbCrud {
        $this->formHandler->callbackUpdate = $callback;
        return $this;
    }

    /**
     * Registers a custom callback for insert operations.
     */
    public function callbackInsert(callable $callback) : DbCrud {
        $this->formHandler->callbackInsert = $callback;
        return $this;
    }

    /**
     * Registers a custom callback for delete operations.
     */
    public function callbackDelete (callable $callback) : DbCrud {
        $this->callback_delete = $callback;
        return $this;
    }

    /**
     * Returns the current table model.
     */
    public function model() : DBTable {
        return $this->table;
    }

    /**
     * Enables/disables encoding of row identifiers.
     */
    public function encode_identifier (bool $encode = true) : DbCrud {
        $this->encode_identifier = $encode;
        return $this;
    }

    /**
     * Returns the number of rows in the current grid query.
     */
    public function rowcount () : int {
        return $this->table->count($this->grid_sql, $this->grid_sql_params);
    }

    /**
     * Returns a specific segment of the current URI (e.g., class, method, id).
     */
    public function get_uri(string $part) : string { //new
        return $this->uri[$part] ?? '';
    }

    /**
     * Returns the title for a given field.
     */
    public function get_field_title(string $field) : string {
        return $this->field_titles[$field] ?? ''; 
    }

    /**
     * Returns the field formatting callback, if any.
     */
    public function get_callback_field(string $field) : callable | null {
        return $this->callback_fields[$field] ?? null;
    }

    /**
     * Returns the primary key name for the current model.
     */
    public function get_primary_key() : string {
        return $this->primaryKey;
    }

    protected function show_grid(int $page, string $orderby) : void {
        $offset = ($page - 1) * $this->limit;

        // headerbar
        $this->headerbar();
        echo $this->grid_info;

        $this->gridSearch();
        $total_rows = $this->rowcount();
        $this->gridSearch(); // yes, we have to call this again

        if ( empty($orderby) )
            $data = $this->table->limit($this->limit)->offset($offset)->identify(true)->findAll($this->grid_sql, $this->grid_sql_params);
        else
            $data = $this->table->limit($this->limit)->offset($offset)->identify(true)->orderby($orderby)->findAll($this->grid_sql, $this->grid_sql_params);

        $total_pages = intval(ceil( ($total_rows / $this->limit) ));
        $uri = '/'.$this->get_uri('class'); 
        $search = $_REQUEST["search"]??'';

        if ( !empty($search) )
            $query="?search=$search&last_page=$page";
        else
            $query="?last_page=$page";

        $grid_show = str_replace('[:script_name]', $uri, $this->grid_show);
        $grid_edit = str_replace('[:script_name]', $uri, $this->grid_edit);
        $grid_delete = str_replace('[:script_name]', $uri, $this->grid_delete);

        // --> start grid table 
        echo '<table class="table table-bordered table-hover dbc-table">';

        // --> grid table header titles
        if ( $this->show_titles ) {
            echo '<thead><tr>';

            foreach ($this->grid_fields as $key => $field) {
                $marker = (in_array($field, $this->search_fields??[]) === true) ? '&nbsp;<i class="bi bi-search"></i>' : '';
                $title = $this->get_field_title($field);
                $sort = ($field === $orderby) ? '<i class="bi bi-sort-alpha-down">&nbsp;</i>' : '';
                $link = '/' . $this->get_uri('class') . $this->query('orderby', $field);
                $html = '<span class="page-item"><a class="page-link" href="' . $link . '">' . $sort . $title . $marker . '</a></span>';
                echo "<th>$html</th>";
            }

            if ( !empty($grid_show.$grid_edit.$grid_delete) )
                echo '<th>Actions</th>';
                
            echo '</tr></thead>';
        }
        // <-- grid table header titles

        foreach ($data as $row => $column) {
            // --> grid table rows
            echo '<tr>';

            foreach ($this->grid_fields as $key => $field) {
                $value = $column[$field];

                switch ($this->getFieldProperty($field, 'type') ) {
                    case 'date':
                    case 'datetext':
                        if ( !empty($value) )
                            $value = date($this->date_fmt, $value);
                            
                        break;
                    case 'timetext':
                        if ( !empty($value) )
                            $value = gmdate($this->time_fmt, $value);
                            
                        break;
                    case 'datetime':
                        if ( !empty($value) )
                            $value = date($this->date_fmt.' '.$this->time_fmt, $value);

                        break;
                    case 'checkbox':
                        // booleans can be stored in db as 0|1, false|true, off|on, -|+, no|yes etc
                        // where array[0] represents false, array[1] represents true
                        $values = explode(',', $this->getFieldProperty($field, 'values'));

                        if ( array_search($value, $values) == 1)
                            $checked = 'checked';
                        else
                            $checked = '';
    
                        $value = '<input type="checkbox" class="form-check-input" '.$checked.' readonly >';
                        break;
                }

                if ( !is_null($this->get_callback_field($field)) )
                    $value = call_user_func($this->get_callback_field($field), 'grid', $value, $column);

                echo "<td>$value</td>";
            }

            if ( $this->encode_identifier ) 
                $id = $this->base64url_encode($column['row_identifier']);
            else
                $id = $column['row_identifier']??'';
          
            $actioncolumn = str_replace('[:identifier]', "{$id}{$query}", '<div class="d-flex flex-row  mb-3">'."$grid_show$grid_edit$grid_delete".'</div>');
            echo "<td>$actioncolumn</td>";

            echo '</tr>';
            // <-- grid table rows
        }
 
        // <-- end gridtable
        
        echo '</table>';

        // footerbar
        $this->footerbar($page, $total_pages);
    }

    protected function current_uri() : array {
        $result = parse_url(urldecode($_SERVER['REQUEST_URI']));
        $path = substr($result["path"]??'', 1);
        $uriSegments = explode("/", $path);
        $class = $uriSegments[0];
        $method = $uriSegments[1]??'';
        $uri = $class.'/'.$method;
        $id = $uriSegments[2]??'';
        $query = $result['query']??'';
        return ['path'=>$path,'uri'=>$uri, 'class'=>$class,'method'=>$method,'id'=>$id,'query'=>$query];
    }

    protected function query(string $var, string $value='') : string {
        $qry = [];
        parse_str($this->get_uri('query'), $qry);

        if (empty($value) )
            unset($qry[$var]);
        else
            $qry[$var] = $value;

        $result = http_build_query($qry);
        return empty($result) ? '' : '?'.$result;
    }

    protected function gridSearch() : bool {
        if ( empty( $this->grid_search) )
            return false;

        if ( empty( $this->search_fields) )
            return false;

        $search = $_REQUEST["search"]??'';
        $value = "%$search%";

        foreach ($this->search_fields as $key => $field) 
            $this->table->where("coalesce($field, '')", $value, 'like', 'or');

        return true;
    }
    
    protected function headerbar() : void {  
        if ( !empty($this->grid_title) )
            echo '<h4 class="dbc-title">'.$this->grid_title.'</h4>';

        if ( empty($this->grid_add) && empty($this->grid_search) )
            return;

        if ( isset ($_REQUEST["search_submit"]) ) {
            $search = $_REQUEST["search"]??'';
            $to = '/'.$this->get_uri('class');

            if ( empty($search) )
                Helper::redirect("$to/grid/1".$this->query('search'));
            else
                Helper::redirect("$to/grid/1".$this->query('search', $search));

            return;
        }
        else 
            $search = $_REQUEST["search"]??'';

        $uri = '/'.$this->get_uri('class');
        echo '<table class="table table-bordered table-hover dbc-headerbar"><tr>';

        if ( !empty($this->grid_add) ) {
            $grid_add = str_replace('[:script_name]', $uri, $this->grid_add);
            echo '<td>'.$grid_add.'</td>';
        }

        if ( !empty($this->grid_search) ) {
            $this->grid_search = str_replace(['[:script_name]','[:search]'], [$uri, $search], $this->grid_search);
            echo '<td>'.$this->grid_search.'</td>';
        }
    
        echo '</tr></table>';
    }

    protected function footerbar(int $current_page, int $total_pages, int $max_pages=5) : void {
        if ( $total_pages == 1 )
            return;
        
        $query = $this->query('search', $_REQUEST["search"]??'');
        echo '<div class="dbc-footerbar">';
        $c = $current_page - 1;
        $c = max(1, $c);
        $min = (intdiv($c, $max_pages) * $max_pages) + 1;  
        $max = (intdiv(($c + 5), $max_pages) * $max_pages) + 1;  
        $max = min($max, $total_pages + 1);
        $uri = '/'.$this->get_uri('class');
        echo '<nav aria-label="Page navigation"><ul class="pagination">';

        if ( $min > $max_pages ) {
            $page = $min - 1;
            $link = $uri . "/grid/1{$query}";
            echo '<li class="page-item"><a class="page-link" href="'.$link.'">First</a></li>';
            $link = $uri . "/grid/{$page}{$query}";
            echo '<li class="page-item"><a class="page-link" href="'.$link.'">Previous</a></li>&nbsp';
        }

        for ($i=$min; $i < $max; $i++) { 
            $link = $uri . "/grid/{$i}{$query}";

            if ( $i == $current_page )
                echo '<li class="page-item active" aria-current="page"><a class="page-link" href="'.$link.'">'.$i.'</a></li> ';
            else 
                echo '<li class="page-item"><a class="page-link" href="'.$link.'">'.$i.'</a></li>';
        }

        if ( $max < $total_pages ) {
            $link = $uri . "/grid/{$max}{$query}";
            echo '&nbsp<li class="page-item"><a class="page-link" href="'.$link.'">Next</a></li>';
            $link = $uri . "/grid/{$total_pages}{$query}";
            echo '<li class="page-item"><a class="page-link" href="'.$link.'">Last</a></li>';
        }

        echo '</ul></nav></div>';
    }

    public function base64url_encode(string $string_value) : string {
        return rtrim(strtr(base64_encode($string_value), '+/', '-_'), '=');
    }

    public function base64url_decode(string $base64_value) : string {
        return base64_decode(strtr($base64_value, '-_', '+/'));
    }
}