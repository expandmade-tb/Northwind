<?php

namespace controller;

use dbgrid\DbCrud;
use models\suppliers_model;

class Suppliers extends CrudController {

    function __construct() {
        parent::__construct();
        
        $this->crud = new DbCrud(new suppliers_model());
        $this->crud->grid_title = '';
        $this->crud->grid_show = '';
        $this->crud->grid_delete = '';
        $this->crud->limit = 15;
        $this->crud->fields('CompanyName,ContactName,ContactTitle,Address,City,Region,PostalCode,Country,Phone,Fax,HomePage');
        $this->crud->searchFields('City');
        $this->crud->gridFields('CompanyName,ContactName,ContactTitle,City,Phone');
        $this->crud->setContstraints('SupplierID', 'Products', 'SupplierID');
        $this->crud->layout_grid(['CompanyName','ContactName,ContactTitle','Address,','PostalCode,City','Region,Country','Phone,Fax','HomePage']);
    }
}