<?php

namespace controller;

use database\DBTable;
use dbgrid\DbCrud;
use Formbuilder\Button;
use helper\Session;
use models\orderdetails_model;
use models\products_model;

class OrderDetails extends BaseController {
    private DbCrud $crud;

    function __construct() {
        parent::__construct();
        $this->data['js_files'][] = JAVASCRIPT.'/livesearch.js';
       
        $this->crud = new DbCrud(new orderdetails_model());
        $this->crud->grid_title = '';
        $this->crud->grid_show = '';
        $this->crud->grid_search = '';
        $this->crud->form_delete = '';
        $this->crud->limit = 15;
        $this->crud->addFields('ProductID,Quantity,Discount');
        $this->crud->editFields('ProductID,Quantity,UnitPrice,Discount');
        $this->crud->gridFields('ProductName,UnitPrice,Quantity,Discount');
        $this->crud->fieldTitles('ProductID,ProductName,UnitPrice,Quantity,Discount','Product,Product,Price,Quantity,Discount');
        $this->crud->readonlyFields('UnitPrice');
        $this->crud->setSearchRelation('ProductID', 'Products', 'ProductName');
        $this->crud->fieldPlaceholder('ProductID', 'enter product');
        $this->crud->fieldPlaceholder('Discount', '%discount for this product');
        $this->crud->fieldValue('Quantity', 1);
        $this->crud->callbackInsert([$this, 'onInsert']);
    }

    private function filter() : void {
        $order_id = Session::instance()->get('orderdetails', -1);
        $this->crud->gridSQL( DBTable::getSQL('orderdetails-crud-filter'), [$order_id]);
        $backlink = "/orders/edit/$order_id";
        $btn = new Button();
        $this->crud->grid_title = "Products for Order no. ".$btn->class('btn btn-info')->href($backlink)->button('<i class="bi bi-caret-right-fill"></i>&nbsp;'.$order_id);
    }
    
    public function onInsert(array $data) : void {
        $order_id = Session::instance()->get('orderdetails', -1);
        $products = new products_model();
        $result = $products->find($data['ProductID']??'');
        $data['UnitPrice'] = $result['UnitPrice'];
        $data['OrderID'] = $order_id;
        $this->crud->model()->insert($data);
    }

    public function index () : void {
        $this->filter();
        $this->grid(1);
    }

    public function selectorder(int $order_id) : void {
        Session::instance()->set('orderdetails', $order_id);
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