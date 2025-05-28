# DbCRUD for SQLite / mySQL

A Database CRUD Class

# Overview

| property | description|
|:--|---|
| grid_add | html to show grid button "add" |
| grid_show | html to show grid button "show" |
| grid_edit | html to show grid button "edit" |
| grid_delete | html to show grid button "delete" |
| grid_search | html to show grid button "search" |
| grid_title | the grid title |
| form_save | html to show form button "save" |
| form_back | html to show form button "back" |
| form_delete | html to show form button "delete" |
| limit | sql selection limit for one page |
| grid_sql | sql to select contents of the grid |
| grid_sql_params | params for "grid_sql" |
| date_fmt | the output format for all date values |
| time_fmt | the output format for all time values |
| use_sessions | use session or cookies |
| show_titles | show column headers |

___

| function | description|
|:--|---|
|addFields|Fields to be used on the Form when adding data.|
|callbackDelete|A function to be called when a row has be deleted|
|callbackInsert|A function to be called when a row has to be inserted|
|callbackUpdate|A function to be called when a row has to be updated|
|clear|Clears the grids search value and executes a new select|
|delete|Deletes the *id* of the current grid table|
|editFields|Fields to be used on the Form when editing data|
|encode_identifier|Encodes the *id* of a row when used in the URI|
|fieldOnChange|Set a javascript function which should be called on change for the given field|
|fieldPlaceholder|Set the placeholder for the given field|
|fieldTitles|Set the field title for the given fields|
|fieldType|Field type are automatically detected. Using this method will overwrite the field type found|
|fields|A shortcut to call addFields, editFields, gridFields and fieldTitles|
|form|shows a form|
|formatField|Callback function to format field|
|grid|Shows the grid|
|gridFields|Set the fields which should be shown in the grid|
|layout_grid|Sets the form layout to use a grid.|
|linkedTable|Adds an additional buttom to the form and links it to the depending table controller and method.|
|model|Returns the current table model used in the grid|
|onException|Sets the callback function to call in case of an exception|
|readFields|Sets the field shown in the form when action button *show* has been selected.|
|readonlyFields|Sets all readonly fields in the form.|
|requiredFields|Sets all the required fields in the form|
|rowcount|Returns the count of all rows from "grid_sql"|
|searchFields|Set all fields which can be searched/filtered.|
|setContstraints|Checks a deletion constraint|
|setRelation|Set a relation 1-n database relation.|
|setRule|Add a validation rule to the given field|
|setSearchRelation|Set a relation 1-n database relation|
|subForm|From within the detail form another from can be opened|

___

# Properties

- [Overview](#Overview)
- [Methods](#methods)
- [Controller](#controller)
- [Example](#example)

## date_fmt
```PHP
    public string $date_fmt = 'd-m-Y';
```
Defines the output format for dates.
___
## form_back
```PHP
    public string $form_back = 'back';
```
The form button text for *back*.
___
## form_delete
```PHP
    public string $form_delete = 'delete';
```
The form button text for *delete*.
___
## form_save
```PHP
    public string $form_save = 'save';
```
public function gridFields (string $fields)The form button text for *save*.
___
## grid_add
```PHP
    public string $grid_add = '<a class="btn btn-primary" href="[:script_name]/add" role="button">Add</a>';    
```
The grid button for *add*.
___
## grid_delete
```PHP
    public string $grid_delete = '<a class="btn btn-danger btn-sm" href="[:script_name]/delete/[:identifier]" role="button">Delete</a>';
```
The grid button for *delete*.
___
## grid_edit
```PHP
    public string $grid_edit = '<a class="btn btn-primary btn-sm" href="[:script_name]/edit/[:identifier]" role="button">Edit</a>';
```
The grid button for *edidt*
___
## grid_search
```PHP
    public string $grid_search = '<form class="d-flex method="post"> <input class="form-control" type="search" name="search" value="[:search]" placeholder="Search" aria-label="Search"><a href="[:script_name]/clear" style="margin: 0 10px 0 -20px; display: inline-block;" title="Clear Search">x</a> <input type="submit" name="search_submit" value="Search" id="search_submit" class="btn btn-primary"/> </form>';
```
The grid form and button for *search*
___
## grid_show
```PHP
    public string $grid_show = '<a class="btn btn-primary btn-sm" href="[:script_name]/show/[:identifier]" role="button">Show</a>';
```
The grid button for *show*
___
## grid_sql
```PHP
    public string $grid_sql = '';
```
Sql statement for grid selection
___
## limit
```PHP
    public int $limit = 100;
```
Limits the number of rows for one page.
___
## show_titles
```PHP
    public bool $show_titles = true;
```
Show the grid titles.
___
## time_fmt
```PHP
    public string $time_fmt = 'h:i:s';
```
The output format for time.
___
## use_sessions
```PHP
    public bool $use_sessions = false;
```
Use sessions or cookie (default).

# Methods

- [Overview](#Overview)
- [Properties](#properties)
- [Controller](#controller)
- [Example](#example)

## __construct
```PHP
    public function __construct(DBTable $table)
```

Constructor method of the *DbCrud* class
___
## addFields
```PHP
    public function addFields (string $fields)
```

Fields to be used on the Form when adding data.
___
## callbackDelete
```PHP
    public function callbackDelete (callable $callback)
```

A function to be called when a row has be deleted. This callback overwrites the standard delete method !
___
## callbackInsert
```PHP
    public function callbackInsert (callable $callback)
```

A function to be called when a row has to be inserted, This callback overwrites the standard insert method !
___
## callbackUpdate
```PHP
    public function callbackUpdate(callable $callback)
```

A function to be called when a row has to be updated, This callback overwrites the standard update method !
___
## clear
```PHP
    public function clear()
```

Clears the grids search value and executes a new select.
___
## delete
```PHP
    public function delete($id) : bool
```

Deletes the *id* of the current grid table.
___
## editFields
```PHP
    public function editFields (string $fields)
```

Fields to be used on the Form when editing data.
___
## encode_identifiercallbackException
```PHP
    public function encode_identifier (bool $encode = true)
```

Encodes the *id* of a row when used in the URI.
___
## fieldOnChange
```PHP
    public function fieldOnChange(string $field, string $rel_table, array $mapping)
```

Set a javascript function which should be called on change for the given field.
___
## fieldPlaceholder
```PHP
    public function fieldPlaceholder (string $field, string $placeholder)
```

Set the placeholder for the given field.
___
## fieldTitles
```PHP
    public function fieldTitles (string $fields, string $titles='')
```

Set the field titles for the given fields.
___
## fieldType
```PHP
    public function fieldType (string $field, string $type, string $valuelist='', int $rows=2, int $cols=40)
```

Field type are automatically detected. Using this method will overwrite the field type found. Currently the following field types are supported:

+ checkbox
+ datalist
+ date
+ datetime
+ integer
+ numeric
+ select
+ text
+ textarea
+ timetext
+ grid
___
## fields
```PHP
    public function fields (string $fields)
```

Calls the following methods:

+ addFields
+ editFields
+ gridFields
+ readFields
+ fieldTitles
___
## form
```PHP
    public function form(string $action, $id='')
```

The controller should call this method when a row has to be edited, added or shown. The parameter *action* can either be:

+ add
+ edit
+ show
___
## formatField
```PHP
    public function formatField(string $field, callable $callable)
```

Set the callback function to format a field. whenever a this field has to be shown in the grid or the form, this function will be called.
___
## grid
```PHP
    public function grid (int $page=1)
```

The controller should call this method when the grid has to be shown.
___
## gridFields
```PHP
    public function gridFields (string $fields)
```

Set the fields which should be shown in the grid.
___
## layout_grid
```PHP
	public function layout_grid(array $rows) : void
```

Sets the form layout to use a grid.

___
## linkedTable
```PHP
    public function linkedTable(string $controller, string $button_value, string $method='index')
```

Adds an additional buttom to the form and links it to the depending table controller and method.
___
## model
```PHP
    public function model() : DBTable
```

Returns the current table model used in the grid.
___
## onException

```PHP
    public function onException(callable $callback) : void
```

Sets the callback function to call in case of an exception.

___

## readFields

```PHP
    public function readFields (string $fields)
```

Sets the field shown in the form when action button *show* has been selected.
___
## readonlyFields
```PHP
    public function readonlyFields (string $fields)
```

Sets all readonly fields in the form.
___
## requiredFields
```PHP
    public function requiredFields (string $fields)
```

Sets all the required fields in the form. This is automatically set for all required constraints of the table.
___
## searchFields
```PHP
    public function searchFields (string $fields)
```

Set all fields which can be searched/filtered.
___
## setContstraints
```PHP
    public function setContstraints(string $field, string $depending_table, string $depending_field)
```

Checks a deletion constraint. In the form the delete button will be disabled if the constraint is not fullfilled. In the following example the parent table is "Products". The depending table is "OrderDetails". The form will check, if on deletion of a "Products" row, there are still depding "OrderDetails" rows. 

```PHP
    $this->crud->setContstraints('ProductID', 'OrderDetails', 'ProductID');
```
___
## setRelation
```PHP
    public function setRelation(string $field, string $relatedField, string $relatedTable)
```

Set a relation 1-n database relation. This will automatically create a dropdown list to the field and show the actual value of the field and not just a primary key to the list. If a table is quite large, see *setSearchRelation*.
___
## setRule
```PHP
    public function setRule(string $field, callable $callback)
```

Add a validation rule to the given field.
___
## setSearchRelation
```PHP
    public function setSearchRelation(string $field, string $relatedTable, string $relatedField, bool $constraint=true) {
```

Set a relation 1-n database relation. This will automatically create a live search to the field and show the actual value of the field and not just a primary key.
___
## subForm
```PHP
	public function subForm(callable $controller, string $button_value) : DbCrud
```
From within the detail form another from can be opened. To cater with parent - child tables, the method **linkedTable** should be used.
___
# Controller

- [Overview](#Overview)
- [Properties](#properties)
- [Methods](#methods)
- [Example](#example)

## Public Methods to be implemented

All methods are requested if they are needed. The only mandatory method is *grid*:

```PHP
    public function grid(int $page) {
        $this->data['dbgrid'] = $this->crud->grid($page);
        $this->view('Crud');
    }
```

Other methods have to be implemented if you allow to *add*, *edit*, *delete* and *search* columns:

```PHP
   public function add() { 
        $this->data['dbgrid'] = $this->crud->form('add');
        $this->view('Crud');
    }

    public function edit($id) {
        $this->data['dbgrid'] = $this->crud->form('edit', $id);
        $this->view('Crud');
    }

    public function delete($id) {
        $result = $this->crud->delete($id);

        if ( $result === false )
            $this->data['dbgrid'] = $this->crud->grid();
        else
            $this->data['dbgrid'] = $this->crud->grid($result);
        
        $this->view('Crud');
    }

    public function clear() {
        $this->crud->clear();
        $this->data['dbgrid'] = $this->crud->grid();
        $this->view('Crud');
    }
```
___

## Callback Methods

### Callback on deletion of a row
Set:
```PHP
    $this->crud->callbackDelete([$this, 'example_row_delete']);
```
Format:
```PHP
    public function example_row_delete($did) {
        // code to delete the data
    }
```
___

### Callback on formatting a value
Set:
```PHP
    $this->crud->formatField('example_field', [$this, 'example_field_formatting']);
```
Format:
```PHP
    public function example_field_formatting(string $field, string $source, string $value) : string {
        // code to format the field
    }
```
___

### Callback on inserting a row
Set:
```PHP
    $this->crud->callbackInsert([$this, 'example_row_insert']);
```
Format:
```PHP
    public function example_row_insert($data) {
        // code to insert the data
    }
```
___

### Callback on field validation
Set:
```PHP
    $this->crud->rule('example_field', [$this, 'example_field_validate']);
```
Format:
```PHP
    public function example_field_validate($value, $field) : string {
        // code to validate the field
    }
```
___
### Callback on exception

Set:

```PHP
	$this->crud->onException([$this, 'handleException']);
```

Format:
```PHP
    public function handleException(mixed $th ) : string {
        return 'The role resource combination already exists'; 
    }

```
### Callback on updating a row

Set:

```PHP
    $this->crud->callbackUpdate([$this, 'example_row_update']);
```

Format:

```PHP
    public function example_row_update($id, $data) {
        // code to update the data
    }
```

___

# Controller

- [Overview](#Overview)
- [Properties](#properties)
- [Methods](#methods)
- [Example](#example)

## Public Methods to be implemented

All methods are requested if they are needed. The only mandatory method is *grid*:

```PHP
    public function grid(int $page) {
        $this->data['dbgrid'] = $this->crud->grid($page);
        $this->view('Crud');
    }
```

Other methods have to be implemented if you allow to *add*, *edit*, *delete* and *search* columns:

```PHP
   public function add() { 
        $this->data['dbgrid'] = $this->crud->form('add');
        $this->view('Crud');
    }

    public function edit($id) {
        $this->data['dbgrid'] = $this->crud->form('edit', $id);
        $this->view('Crud');
    }

    public function delete($id) {
        $result = $this->crud->delete($id);

        if ( $result === false )
            $this->data['dbgrid'] = $this->crud->grid();
        else
            $this->data['dbgrid'] = $this->crud->grid($result);
        
        $this->view('Crud');
    }

    public function clear() {
        $this->crud->clear();
        $this->data['dbgrid'] = $this->crud->grid();
        $this->view('Crud');
    }
```
___

## Callback Methods

### Callback on deletion of a row
Set:
```PHP
    $this->crud->callbackDelete([$this, 'example_row_delete']);
```
Format:
```PHP
    public function example_row_delete($did) {
        // code to delete the data
    }
```
___

### Callback on formatting a value
Set:
```PHP
    $this->crud->formatField('example_field', [$this, 'example_field_formatting']);
```
Format:
```PHP
    public function example_field_formatting(string $field, string $source, string $value) : string {
        // code to format the field
    }
```
___

### Callback on inserting a row
Set:
```PHP
    $this->crud->callbackInsert([$this, 'example_row_insert']);
```
Format:
```PHP
    public function example_row_insert($data) {
        // code to insert the data
    }
```
___

### Callback on field validation
Set:
```PHP
    $this->crud->rule('example_field', [$this, 'example_field_validate']);
```
Format:
```PHP
    public function example_field_validate($value, $field) : string {
        // code to validate the field
    }
```
___
### Callback on updating a row

Set:
```PHP
    $this->crud->callbackUpdate([$this, 'example_row_update']);
```

Format:
```PHP
    public function example_row_update($id, $data) {
        // code to update the data
    }
```
___

# Example

- [Overview](#Overview)
- [Properties](#properties)
- [Methods](#methods)
- [Controller](#controller)

```PHP
<?php

namespace controller;

use dbgrid\DbCrud;
use helper\Session;
use models\invoices_details_model;
use models\products_model;
use Router\Router;

class InvoicesDetails extends BaseController {
    private DbCrud $crud;

    function __construct() {
        parent::__construct();
       
        $this->crud = new DbCrud(new invoices_details_model());
        $this->crud->grid_show = '';
        $this->crud->grid_search = '';
        $this->crud->form_delete = '';
        $this->crud->limit = 15;
        $this->crud->callbackInsert([$this, 'onInsert']);
        $this->crud->editFields('ProductId,Qty,Price');
        $this->crud->addFields('ProductId,Qty,Price');
        $this->crud->gridFields('Item,Qty,Price');
        $this->crud->fieldValue('Qty', 1);
        $this->crud->setRelation('ProductId', 'Item', 'Products');
        $this->crud->fieldOnChange('ProductId', 'ProductsByItem', ['Price'=>'Price']);
        $this->crud->fieldTitles('ProductId,Item,Qty,Price','Product,Item,Qty,Price');
        $this->crud->fieldPlaceholder('Price', 'leave blank to accept original product price');
    }

    private function filter() : void {
        $invoice_id = Session::instance()->get('invoicedetails', -1);
        $this->crud->gridSQL( $this->crud->model()->getSQL('invoicedetails-crud-filter'), [$invoice_id]);
        $url = Router::instance()->url();
        $this->crud->grid_title = "Items for <a href=\"$url/invoices/edit/$invoice_id\">Invoice no. $invoice_id</a>"; // gets back to the invoice
    }
    
    public function onInsert(array $data) : void {
        $invoice_id = Session::instance()->get('invoicedetails', -1);

        if ( empty($data['Price']) ) {
            $products = new products_model();
            $result = $products->find($data['ProductId']??'');
            $data['Price'] = $result['Price'];
        }

        $data['InvoiceId'] = $invoice_id;
        $this->crud->model()->insert($data);
    }

    public function index () : void {
        $this->filter();
        $this->grid(1);
    }

    public function selectinvoice(int $invoice_id) : void {
        Session::instance()->set('invoicedetails', $invoice_id);
        $this->index();
    }

    public function add() : void {
        $this->filter();
        $this->data['dbgrid'] = $this->crud->form('add');
        $this->view('Crud');
    }

    public function edit(string $id) : void {
        $this->filter();
        $this->data['dbgrid'] = $this->crud->form('edit', $id);
        $this->view('Crud');
    }

    public function delete(string $id) : void {
        $result = $this->crud->delete($id);
        $this->filter();

        if ( $result === false )
            $this->data['dbgrid'] = $this->crud->grid();
        else
            $this->data['dbgrid'] = $this->crud->grid($result);
        
        $this->view('Crud');
    }

    public function grid(int $page) : void {
        $this->filter();
        $this->data['dbgrid'] = $this->crud->grid($page);
        $this->view('Crud');
    }
}
```