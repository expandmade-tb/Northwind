<?php

/**
 * CRUD for database tables
 * Version 1.23.0
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace dbgrid;

use database\DBTable;
use Exception;
use Formbuilder\Formbuilder;
use Formbuilder\StatelessCSRF;
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
    protected string $echo_data = '';               // data to be returned instead of echo
    protected array $field_titles;                  // titles / headers for fields
    protected array $add_fields;                    // fields to show during form add action
    protected array $edit_fields;                   // fields to show during form edit action
    protected array $read_fields;                   // fields to show during form read action
    protected array $grid_fields;                   // fields to show in grid
    protected array $search_fields;                 // searchable fields
    protected array $callback_fields;               // fields to callback to format values
    protected array $callback_rules;                // fields rules at form validation
    protected array $readonly_fields;               // fields which are readonly on form
    protected array $required_fields;               // fields which are required on form
    protected array $field_types;                   // the field types to deal with
    protected array $field_values;                  // the field initial values
    protected array $field_placeholders;            // the field placeholders when editing form
    protected array $field_onchange;                // adds an ajax call to field onchange event
    protected mixed $callback_insert;               // replaces buildin insert
    protected mixed $callback_update;               // replaces buildin update
    protected mixed $callback_delete;               // replace buildin delete
    protected string $primaryKey;                   // for now just a single field which is pk
    protected bool $encode_identifier = false;      // identifier will be base64 encoded  
    protected string $grid_info = '';               // shows an information at the top of the grid
    protected array $uri;                           // current uri split into its parts
    protected array $datepicker;                    // fields with integrated date picker
    private array $linked_table;                    // store the controller + method to link with a button in edit mode
    private array $subform;                         // store the controller + method to handle a subform
    private array $constraints;                     // stores a list of fields which do have depending tables ( parent -> child)
    private array $rows;                            // grid layout for form
    private mixed $callbackException;               // callback on exceptions

    public function __construct(DBTable $table) {
        $this->table = $table;
        $this->grid_title = $table->name();

        if ( count($this->table->primaryKey()) > 1 )
            throw new Exception("only single column primary keys supported");
            
        $this->uri = $this->current_uri();
        $this->primaryKey = $this->table->primaryKey()[0]??'';;
        $this->fields($this->table->fieldlist());
        
        // initialze basic datatypes
        foreach (explode(',',$this->table->fieldlist()) as $key => $field ) {
            switch ($this->table->fields($field)['type']) {
                case 'INTEGER':
                    $this->fieldType($field, 'integer');
                    break;
                case 'REAL':
                    $this->fieldType($field, 'numeric');
                    break;
                case 'NUMERIC':
                    $this->fieldType($field, 'numeric');
                    break;
                default:
                    $this->fieldType($field, 'text');
                    break;
           }
        }
    }
    
    public function addFields (string $fields) : DbCrud {
        $this->add_fields = array_map('trim',explode(',', $fields));
        return $this;
    }

    public function editFields (string $fields) : DbCrud {
        $this->edit_fields = array_map('trim',explode(',', $fields));
        return $this;
    }

    public function readFields (string $fields) : DbCrud {
        $this->read_fields = array_map('trim',explode(',', $fields));
        return $this;
    }

    public function gridFields (string $fields) : DbCrud {
        $this->grid_fields = array_map('trim',explode(',', $fields));
        return $this;
    }

    public function searchFields (string $fields) : DbCrud {
        $this->search_fields = array_map('trim',explode(',', $fields));
        return $this;
    }

    public function readonlyFields (string $fields) : DbCrud {
        foreach (array_map('trim',explode(',', $fields)) as $key => $value)
            $this->readonly_fields[$value] = $key;

        return $this;
    }

    public function requiredFields (string $fields) : DbCrud {
        foreach (array_map('trim',explode(',', $fields)) as $key => $value)
            $this->required_fields[$value] = $key;

        return $this;
    }

    public function fieldPlaceholder (string $field, string $placeholder) : DbCrud {
        $this->field_placeholders[$field] = $placeholder;
        return $this;
    }
    
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

    public function fieldValue (string $field, string $value) : DbCrud{
        $this->field_values[$field] = $value;
        return $this;
    }

    public function fieldOnChange(string $field, string $rel_table, array $mapping) : DbCrud {
        $this->field_onchange[$field] = ['mapping'=>$mapping, 'rel_table'=>$rel_table];
        JsScript::instance()->add_script('onchange');
        return $this;
    }

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

    public function fields (string $fields) : DbCrud {
        $this->addFields($fields);
        $this->editFields($fields);
        $this->readFields($fields);
        $this->gridFields($fields);
        $this->field_titles = [];
        $this->fieldTitles($fields);
        return $this;
    }

    public function setDatepicker (string $field, string $format='yyyy-mm-dd', string $lang='en') : DbCrud {
        $this->datepicker[$field] = ['format'=>$format, 'lang'=>$lang];
        return $this;
    }

    public function setRelation(string $field, string $relatedField, string $relatedTable) : DbCrud {
        $this->field_types[$field] = ['type'=>'relation', 'rel_table'=>$relatedTable, 'rel_field'=>$relatedField]; 
        return $this;
    }

    /**
    * @deprecated deprecated, use fieldType instead
    */
    public function setGrid(string $field, int $rows, int $cols) : DbCrud {
        $this->field_types[$field] = ['type'=>'grid', 'rows'=>$rows, 'cols'=>$cols];
        return $this;
    }

    public function setSearchRelation(string $field, string $relatedTable, string $relatedField, bool $constraint=true) : DbCrud {
        $this->field_types[$field] = ['type'=>'search', 'rel_table'=>$relatedTable, 'rel_field'=>$relatedField, 'constraint'=>$constraint]; 
        JsScript::instance()->add_script('searchrelation');
        return $this;
    }

    public function formatField(string $field, callable $callable) : DbCrud {
        $this->callback_fields[$field] = $callable;
        return $this;
    }

    public function linkedTable(string $controller, string $button_value, string $method='index') : DbCrud {
        unset($this->linked_table);
        $this->linked_table['controller'] = $controller;
        $this->linked_table['button_value'] = $button_value;
        $this->linked_table['method'] = $method;
        return $this;
    }

    public function subForm(callable $controller, string $button_value) : DbCrud {
        unset($this->subform);
        $this->subform['callback'] = $controller;
        $this->subform['button_value'] = $button_value;
        return $this;
    }

    public function onException(callable $callback) : DbCrud {
        $this->callbackException = $callback;
        return $this;
    }

    public function layout_grid(array $rows) : DbCrud {
        $this->rows = $rows;
        return $this;
    }

    public function setContstraints(string $field, string $depending_table, string $depending_field) : DbCrud {
        $this->constraints[$field][] = ['table'=>$depending_table,  'field'=>$depending_field];
        return $this;
    }

    public function gridSQL (string $sql, ?array $params=null ) : DbCrud {
        $this->grid_sql = $sql;
        $this->grid_sql_params = $params;
        return $this;
    }

    public function grid (int $page=1) : string {
        $this->show_grid($page);
        return '<div id="dbc-container">'.$this->echo_data.'</div>';
    }

    public function form(string $action, string $id='', string $msg='', string $wrapper='') : string {

        $link_id = $id;

        if ( $this->encode_identifier && !empty($id)) 
            $id = $this->base64url_decode($id);
        
        // what fields we are dealing with ?
        switch ($action) {
            case 'add':
                $fields = $this->add_fields;
                break;
            case 'edit':
                $fields = $this->edit_fields;
                break;
            case 'show':
                $fields = $this->read_fields;
                break;
            default:
                $fields = [];
                break;
        }

        $search = $_REQUEST["search"]??'';
        $page = $_REQUEST['last_page']??1;

        if ( !empty($search) )
            $query="?search=$search&last_page=$page";
        else
            $query="?last_page=$page";


        $grid_to = '/'.$this->uri['class'];
        $backlink = "$grid_to/grid/{$page}{$query}";
        $deletelink = "$grid_to/delete/{$link_id}{$query}";
        $form_action = '/'.$this->uri['path'].$query;

        if ( empty($wrapper) ) // default wrapper
            if ( !empty($this->rows) )
                $wrapper = 'bootstrap-inline';
            else
                $wrapper = 'bootstrap-h-sm';

        // start the form
        $subform_requested = (htmlspecialchars($_REQUEST["subform"]??'') === 'true');
        $form = new Formbuilder($this->table->name(), ['action'=>$form_action, 'wrapper'=>$wrapper]);
        $disabled_main_form = $subform_requested === true ? 'disabled' : '';
        $form->fieldset_open('', $disabled_main_form);

        // overwrite date and time formats from grid
        $form->date_format = $this->date_fmt;
        $form->time_format = $this->time_fmt;

        if ( !empty($msg) )
            $form->message($msg);

        // === 2. form submitted === 

        if ( $form->submitted() && isset($_POST["mainform-save"]) ) { // if submitted we can apply rules and validate
            foreach ($fields as $key => $field) { // apply basic rules

                if ( $this->table->fields($field)['required'] ) // meta data required fields
                    $form->rule('required', $field);
                else
                    if (isset($this->required_fields[$field]) ) // user required fields 
                        $form->rule('required', $field,);

                switch ($this->field_types[$field]['type']) {
                    case 'integer':
                        $form->rule('integer', $field);
                        break;
                    case 'numeric':
                        $form->rule('numeric', $field);
                        break;
                    case 'date':
                    case 'datetext':
                    case 'datetime':
                        $form->rule('date', $field);
                        break;
                    case 'timetext':
                        $form->rule('time', $field);
                        break;
                    case 'relation':
                    case 'search': 
                        $constraint = $this->field_types[$field]['constraint']??false;

                        if ( $constraint === true )
                            $form->rule([$this, 'check_relation'], $field); // preventing fk constraints
                        
                        break;
                }
            }

            if ( !empty($this->callback_rules) )  // apply used defined rules
                foreach ($this->callback_rules as $field => $rule)
                    $form->rule($rule, $field);

            $data = $form->validate(implode(',', $fields)); // finally validate the data
    
            if ( $data === false ) // something has gone very wrong
                $form->message('data cannot be saved');

            // === 3. form validated === 

            if($form->ok()) { // validation success
                foreach ($data as $field => $value) {
                    switch ($this->field_types[$field]['type']) {
                        case 'date':
                        case 'datetext':
                        case 'datetime':
                            // dates in db are stored as integers

                            if ( empty($value) )
                                $data[$field] = null;
                            else
                                $data[$field] = strtotime($value);
                            
                            break;
                        case 'timetext':
                            // times in db are stored as integers

                            if ( empty($value) )
                                $data[$field] = null;
                            else
                                $data[$field] = strtotime($value) - strtotime('TODAY'); 

                            break;
                        case 'checkbox':
                            // booleans can be stored in db as 0|1, false|true, off|on, -|+, no|yes etc
                            // where array[0] represents false, array[1] represents true
                            $values = explode(',',$this->field_types[$field]['values']);
        
                            if ( $value === null )
                                $data[$field] = $values[0];
                            else
                                $data[$field] = $values[1];

                            break;
                        case 'relation':
                        case 'search':
                            $rel_table = $this->field_types[$field]['rel_table'];
                            $rel_field = $this->field_types[$field]['rel_field'];
                            $table = new DBTable($rel_table);
                            $rel_key = $table->primaryKey()[0];
                            $value = $table->where($rel_field, html_entity_decode($value, ENT_QUOTES | ENT_HTML5))->findColumn($rel_key);

                            if ( isset($value[0]) )
                                $data[$field] = $value[0];
                            
                            break;
                        case 'grid':
                            $data[$field] = json_encode($value);
                            break;
                        }
                }
    
                try { // db constraints might raise an exception
                    if ( empty($id) ) { // insert row
                        if ( isset($this->callback_insert) )
                            call_user_func($this->callback_insert, $data);
                        else
                            $this->table->insert($data);
                    }
                    else { // update row
                        if ( isset($this->callback_update) )
                            call_user_func($this->callback_update, $id, $data);
                        else
                            $this->table->update($id, $data);
                    }

                    if ( empty($id) && !empty($this->linked_table) ) { // in add mode, redirect to a linked table controller if set
                        $id = $data[$this->primaryKey]??''; // primary key might be in the data
                        
                        if ( empty($id) ) // primary key is autoincrement
                            $id = $this->table->database()->lastInsertId($this->table->name());
                        
                        if ( $id !== false ) {
                            $editlink =  "$grid_to/edit/$id";
                            Helper::redirect($editlink);
                            return '';
                        }
                    }

                    Helper::redirect($backlink); // redirect to grid controller
                    return '';
                } catch (\Throwable $th) {
                    if ( !empty($this->callbackException) ) {
                        $result = call_user_func($this->callbackException, $th);

                        if ( is_string($result) )
                            $form->message($result);
                        else
                            $form->message($th->getMessage());
                    }
                    else
                        $form->message($th->getMessage());
                }
            }
        }

        // === 1. form rendering === 

        if ( ! empty($id) ) // get the row
            $data = $this->table->find($id);
        else
            $data = false;

        foreach ($fields as $key => $field) { // build the form fields
            $readonly = '';
            $placeholder = '';

            if ( isset($this->field_placeholders[$field]) )
                $placeholder = ' placeholder="'.$this->field_placeholders[$field].'"';

            if ( isset($this->field_titles[$field]) ) // do we have a label for the field ?
                $label = $this->field_titles[$field];
            else
                $label = '';

            if ( $data === false ) { // do we have values ?
                if ( isset($this->field_values[$field]) )
                    $value = $this->field_values[$field];
                else
                    $value = '';
            }
            else
                $value = $data[$field]; 

            if ( $value == null )
                $value = '';

            if ( isset($this->callback_fields[$field]) ) // user defined value formatting
                $value = call_user_func($this->callback_fields[$field], 'form', $value, null);

            if ( $action == 'show' ) // show applies all readonly
                $readonly = 'readonly';
            else
                if ( isset($this->readonly_fields[$field]) ) // user defined readonly
                    $readonly = 'readonly';
           
            if ( $field == $this->primaryKey && $action == 'edit' ) // we wont allow to edit a primary key
                $readonly = 'readonly';

            $ajax_token = $this->token();

            if ( !empty($this->field_onchange[$field]) ) {
                $jsencoded = json_encode($this->field_onchange[$field]['mapping']);

                if ($jsencoded === false )
                    throw new Exception("onchange mapping $field cannot encode");

                $rel_table = $this->field_onchange[$field]['rel_table'];
                $controller = JsScript::instance()->add_var("/clientRequests/{$rel_table}");
                $mapping = JsScript::instance()->add_var($jsencoded);
                JsScript::instance()->var('token', $ajax_token);
                $onchange = " onchange=\"form_field_onchange(this, $controller, $mapping, token)\"";
            }
            else
                $onchange='';

            switch ($this->field_types[$field]['type']) {
                case 'date':
                    // dates in db are stored as integers
                    if ( !empty($value) )
                        $value = date($this->date_fmt, $value);

                    $form->date($field, ['label'=>$label, 'string'=>$readonly.$placeholder.$onchange, 'value'=>$value]);
                    break;
                case 'datetext':
                    // dates in db are stored as integers
                    if ( !empty($value) )
                        $value = date($this->date_fmt, $value);

                    $form->datetext($field, ['label'=>$label, 'format'=>$this->date_fmt, 'string'=>$readonly.$placeholder.$onchange, 'value'=>$value]);
                    break;
                case 'timetext':
                    // times in db are stored as integers
                    if ( !empty($value) )
                        $value = gmdate($this->time_fmt, $value);

                    $form->timetext($field, ['label'=>$label, 'format'=>$this->date_fmt, 'string'=>$readonly.$placeholder.$onchange, 'value'=>$value]);
                    break;
                case 'datetime':
                    // dates in db are stored as integers
                    if ( !empty($value) )
                        $value = date($this->date_fmt.' '.$this->time_fmt, $value);

                    $form->datetime($field, ['label'=>$label, 'string'=>$readonly.$placeholder.$onchange, 'value'=>$value]);
                    break;
                case 'textarea':
                    $rows = $this->field_types[$field]['rows'];
                    $cols = $this->field_types[$field]['cols'];
                    $form->textarea($field, ['label'=>$label,'string'=>$readonly.$placeholder.$onchange, 'value'=>$value, 'rows'=>$rows, 'cols'=>$cols]);
                    break;
                case 'checkbox':
                    // booleans can be stored in db as 0|1, false|true, off|on, -|+, no|yes etc
                    // where array[0] represents false, array[1] represents true
                    $values = explode(',',$this->field_types[$field]['values']);

                    if ( array_search($value, $values) == 1)
                        $checked = true;
                    else
                        $checked = false;

                    $form->checkbox($field, ['label'=>$label, 'checked'=>$checked, 'string'=>$readonly.$onchange]);
                    break;
                case 'select':
                    $values = $this->field_types[$field]['values'];
                    $form->select($field, $values, ['label'=>$label, 'value'=>$value, 'string'=>$readonly.$onchange]);
                    break;
                case 'datalist':
                    $values = $this->field_types[$field]['values'];
                    $form->datalist($field,  $values, ['label'=>$label, 'value'=>$value, 'string'=>$readonly.$placeholder.$onchange]);
                    break;
                case 'relation':
                    $rel_table = $this->field_types[$field]['rel_table'];
                    $rel_field = $this->field_types[$field]['rel_field'];
                    $table = new DBTable($rel_table);
                    $values = $table->orderby($rel_field)->findColumn($rel_field);
                    array_unshift($values, '');

                    if ( !empty($value) ) {
                        $result = $table->find($value);

                        if ($result === false)
                            $value = '';
                        else
                            $value = $result[$rel_field];
                    }
    
                    $form->select($field, $values, ['label'=>$label, 'value'=>$value, 'string'=>$readonly.$onchange]);
                    break;
                case 'search':
                    $rel_table = $this->field_types[$field]['rel_table'];
                    $rel_field = $this->field_types[$field]['rel_field'];
                    $table = new DBTable($rel_table);

                    if ( !empty($value) ) {
                        $result = $table->find($value);

                        if ($result === false)
                            $value = '';
                        else
                            $value = $result[$rel_field];
                    }
                    
                    JsScript::instance()->var('token', $ajax_token);
                    $var = JsScript::instance()->add_var("/clientRequests/{$rel_table}Search");
                    $form->search($field, ['label'=>$label, 'value'=>$value, 'string'=>$readonly.$placeholder],"searchrelationResults(this, $var, token)");
                    break;
                case 'grid':
                    $values = json_decode($value, true);

                    if ( $values === false )
                        $values =  [];

                    $rows = $this->field_types[$field]['rows'];
                    $cols = $this->field_types[$field]['cols'];

                    if ( !empty($readonly) )
                        $string = array_fill(0, $rows, array_fill(0, $cols, $readonly.$placeholder.$onchange));
                    else
                        $string = [];

                    $form->grid($field, ['label'=>$label, 'value'=>$values, 'rows'=>$rows, 'cols'=>$cols, 'string'=>$string ]);
                    break;
                default:
                    $form->text($field, ['label'=>$label,'string'=>$readonly.$placeholder.$onchange, 'value'=>$value]);
                    break;
            }
        }

        $form->html('<br>');

        // build the form button bar

        $btn_bar = [];

        if ( $action == 'show' ) {
            $btn_bar['names'] = ['mainform-back'];
            $btn_bar['values'] = [$this->form_back];
            $btn_bar['onclicks'] = [$backlink];
            $btn_bar['types'] = ['button'];
            $btn_bar['strings'] = ['class="btn btn-secondary"'];
        }
        else {
            $btn_bar['names'] = ['mainform-save','back'];
            $btn_bar['values'] = [$this->form_save,$this->form_back];
            $btn_bar['onclicks'] = ['', $backlink];
            $btn_bar['types'] = ['submit','button'];
            $btn_bar['strings'] = ['', 'class="btn btn-secondary"'];
        }
    
        if ( $action != 'add' && !empty($this->form_delete) ) { // only edit mode
            $disabled_delete = '';

            if ( !empty($this->constraints) ) // any FK constraints defined will disable the delete btn...
                foreach ($this->constraints as $field => $values) {
                    foreach ($values as $index => $value) {
                        $depending_table = $value['table'];
                        $depending_field = $value['field'];
                        $table = new DBTable($depending_table);
                        $value = $data[$field];
                        $result = $table->where($depending_field, $value)->limit(1)->count(); // check if references are found
        
                        if ( $result > 0 ) {
                            $disabled_delete = 'disabled';
                            break;
                        }
                    }
                }
    
            $btn_bar['names'][] = 'mainform-delete';
            $btn_bar['values'][] = $this->form_delete;
            $btn_bar['onclicks'][] = $deletelink;
            $btn_bar['types'][] = 'button'; // type submit not allowed here
            $btn_bar['strings'][] = 'class="btn btn-danger '.$disabled_delete.'"';

            if ( !empty($this->linked_table) ) { // add button to a linked table controller
                $controller = $this->linked_table['controller'];
                $value = $this->linked_table['button_value'];
                $method = $this->linked_table['method'];
                $btn_bar['names'][] = $controller;
                $btn_bar['values'][] = $value;
                $btn_bar['types'][] =  'button';
                $btn_bar['strings'][] =  'class="btn btn-success"';
                $btn_bar['onclicks'][] = helper::url()."/$controller/$method/$link_id";
            }

            if ( !empty($this->subform) ) { // add button to enable subform mechanism
                $value = $this->subform['button_value'];
                $btn_bar['names'][] = 'btn-subform';
                $btn_bar['values'][] = $value;
                $btn_bar['types'][] =  'button';
                $btn_bar['strings'][] =  'class="btn btn-success"';
                $btn_bar['onclicks'][] = "$grid_to/edit/$id?subform=true";;
            }
        }

        $form->button_bar($btn_bar['names'],$btn_bar['values'],$btn_bar['onclicks'],$btn_bar['types'],$btn_bar['strings']);
        $form->fieldset_close();

        if ( !empty($this->rows) )
            $form->layout_grid($this->rows);

        if ( $subform_requested ) {
            $subform = '<div>'.call_user_func($this->subform['callback'], $id).'</div>';
        }
        else
            $subform = '';

        if ( !empty($this->datepicker) ) {
            $js = JsScript::instance();

            foreach ($this->datepicker as $field => $opts) {
                $format = $opts['format']??'';
                $lang = $opts['lang']??'';
                $js->add_var("Datepicker('#$field','$format','$lang')");
            }
        }

        return '<div id="dbc-container">'.$form->render().$subform.'</div>'.JsScript::instance()->generate();
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

    public function clear() : void {
        $to = '/'.$this->uri['class'];
        Helper::redirect("$to/grid/1");
    }

    public function setRule(string $field, string|callable $callback) : DbCrud {
        $this->callback_rules[$field] = $callback;
        return $this;
    }

    public function callbackUpdate(callable $callback) : DbCrud {
        $this->callback_update = $callback;
        return $this;
    }

    public function callbackInsert(callable $callback) : DbCrud {
        $this->callback_insert = $callback;
        return $this;
    }

    public function callbackDelete (callable $callback) : DbCrud {
        $this->callback_delete = $callback;
        return $this;
    }

    public function model() : DBTable {
        return $this->table;
    }

    public function encode_identifier (bool $encode = true) : DbCrud {
        $this->encode_identifier = $encode;
        return $this;
    }

    public function check_relation(string $value, string $field) : string {
        $rel_table = $this->field_types[$field]['rel_table'];
        $rel_field = $this->field_types[$field]['rel_field'];
        $table = new DBTable($rel_table);
        $rel_key = $table->primaryKey()[0];
        $value = $table->where($rel_field, html_entity_decode($value, ENT_QUOTES | ENT_HTML5))->findColumn($rel_key);

        if ( !isset($value[0]) )
            return 'pls enter a valid value from the proposed values';

        return '';
    }

    public function rowcount () : int {
        return $this->table->count($this->grid_sql, $this->grid_sql_params);
    }

    protected function show_grid(int $page) : void {
        $offset = ($page - 1) * $this->limit;

        // headerbar
        $this->echo_data .= $this->headerbar();
        $this->echo_data .= $this->grid_info;

        $this->gridSearch();
        $total_rows = $this->rowcount();

        $this->gridSearch();
        $data = $this->table->limit($this->limit)->offset($offset)->identify(true)->findAll($this->grid_sql, $this->grid_sql_params);

        $total_pages = intval(ceil( ($total_rows / $this->limit) ));

        $uri = '/'.$this->uri['class']; 

        $search = $_REQUEST["search"]??'';

        if ( !empty($search) )
            $query="?search=$search&last_page=$page";
        else
            $query="?last_page=$page";

        $grid_show = str_replace('[:script_name]', $uri, $this->grid_show);
        $grid_edit = str_replace('[:script_name]', $uri, $this->grid_edit);
        $grid_delete = str_replace('[:script_name]', $uri, $this->grid_delete);

        // --> start grid table 
        $this->echo_data .= '<table class="table table-bordered table-hover dbc-table">';

        // --> grid table header titles
        if ( $this->show_titles ) {
            $this->echo_data .= '<thead><tr>';

            foreach ($this->grid_fields as $key => $field) {
                $marker = (in_array($field, $this->search_fields??[]) === true) ? '*' : '';
                $title = $this->field_titles[$field]??'';
                $this->echo_data .= "<th>$title$marker</th>";
            }

            if ( !empty($grid_show.$grid_edit.$grid_delete) )
                $this->echo_data .= '<th>Actions</th>';
                
            $this->echo_data .= '</tr></thead>';
        }
        // <-- grid table header titles

        foreach ($data as $row => $column) {
            // --> grid table rows
            $this->echo_data .= '<tr>';

            foreach ($this->grid_fields as $key => $field) {
                $value = $column[$field];

                switch ($this->field_types[$field]['type']??'') {
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
                        $values = explode(',',$this->field_types[$field]['values']);

                        if ( array_search($value, $values) == 1)
                            $checked = 'checked';
                        else
                            $checked = '';
    
                        $value = '<input type="checkbox" class="form-check-input" '.$checked.' readonly >';
                        break;
                }

                if ( isset($this->callback_fields[$field]) )
                    $value = call_user_func($this->callback_fields[$field], 'grid', $value, $column);

                $this->echo_data .= "<td>$value</td>";
            }

            if ( $this->encode_identifier ) 
                $id = $this->base64url_encode($column['row_identifier']);
            else
                $id = $column['row_identifier']??'';
          
            $actioncolumn = str_replace('[:identifier]', "{$id}{$query}", '<div class="d-flex flex-row  mb-3">'."$grid_show$grid_edit$grid_delete".'</div>');
            $this->echo_data .= "<td>$actioncolumn</td>";

            $this->echo_data .= '</tr>';
            // <-- grid table rows
        }
 
        // <-- end gridtable
        
        $this->echo_data .= '</table>';

        // footerbar
        $this->echo_data .= $this->footerbar($page, $total_pages);
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
    
    protected function headerbar() : string {  
        $html = '';

        if ( !empty($this->grid_title) )
            $html .= '<h4 class="dbc-title">'.$this->grid_title.'</h4>';

        if ( empty($this->grid_add) && empty($this->grid_search) )
            return $html;

        if ( isset ($_REQUEST["search_submit"]) ) {
            $search = $_REQUEST["search"]??'';
            $to = '/'.$this->uri['class'];

            if ( empty($search) )
                Helper::redirect("$to/grid/1");
            else
                Helper::redirect("$to/grid/1?search=$search");

            return '';
        }
        else 
            $search = $_REQUEST["search"]??'';

        $uri = '/'.$this->uri['class'];
        $html .= '<table class="table table-bordered table-hover dbc-headerbar"><tr>';

        if ( !empty($this->grid_add) ) {
            $grid_add = str_replace('[:script_name]', $uri, $this->grid_add);
            $html .= '<td>'.$grid_add.'</td>';
        }

        if ( !empty($this->grid_search) ) {
            $this->grid_search = str_replace(['[:script_name]','[:search]'], [$uri, $search], $this->grid_search);
            $html .= '<td>'.$this->grid_search.'</td>';
        }
    
        $html .= '</tr></table>';

        return $html;
    }

    protected function footerbar(int $current_page, int $total_pages, int $max_pages=5) :string {
        if ( $total_pages == 1 )
            return '';
        
        $search = $_REQUEST["search"]??'';
        $query = empty($search) ? '' : "?search=$search";
        $html = '<div class="dbc-footerbar">';
        $c = $current_page - 1;

        if ( $c < 1 )
            $c = 1;

        $min = (intdiv($c, $max_pages) * $max_pages) + 1;  
        $max = (intdiv(($c + 5), $max_pages) * $max_pages) + 1;  

        if ( $max > $total_pages )
            $max = $total_pages + 1;

        $uri = '/'.$this->uri['class'];
        $html .= '<nav aria-label="Page navigation"><ul class="pagination">';

        if ( $min > $max_pages ) {
            $page = $min - 1;
            $link = $uri . "/grid/1$query";
            $html .= '<li class="page-item"><a class="page-link" href="'.$link.'">First</a></li>';
            $link = $uri . "/grid/$page";
            $html .= '<li class="page-item"><a class="page-link" href="'.$link.'">Previous</a></li>&nbsp';
        }

        for ($i=$min; $i < $max; $i++) { 
            $link = $uri . "/grid/{$i}{$query}";

            if ( $i == $current_page )
                $html .= '<li class="page-item active" aria-current="page"><a class="page-link" href="'.$link.'">'.$i.'</a></li> ';
            else 
                $html .= '<li class="page-item"><a class="page-link" href="'.$link.'">'.$i.'</a></li>';
        }

        if ( $max < $total_pages ) {
            $link = $uri . "/grid/{$max}{$query}";
            $html .= '&nbsp<li class="page-item"><a class="page-link" href="'.$link.'">Next</a></li>';
            $link = $uri . "/grid/{$total_pages}{$query}";
            $html .= '<li class="page-item"><a class="page-link" href="'.$link.'">Last</a></li>';
        }

        $html .= '</ul></nav></div>';
        return $html;
    }

    private function token() : string {
        $csrf_generator = new StatelessCSRF(Helper::env('app_secret', 'empty_secret'));
        $csrf_generator->setGlueData('ip', $_SERVER['REMOTE_ADDR']);
        $csrf_generator->setGlueData('user-agent', $_SERVER['HTTP_USER_AGENT']);            
        $token = $csrf_generator->getToken(Helper::env('app_identifier','empty_identifier'), time() + 900); // valid for 15 mins.           
        return $token;
    }

    private function base64url_encode(string $string_value) : string {
        return rtrim(strtr(base64_encode($string_value), '+/', '-_'), '=');
    }

    private function base64url_decode(string $base64_value) : string {
        return base64_decode(strtr($base64_value, '-_', '+/'));
    }
}
