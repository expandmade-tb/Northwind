<?php

namespace controller;

use dbgrid\DbCrud;
use models\shippers_model;

class Shippers extends CrudController {

    function __construct() {
        parent::__construct();
        
        $this->crud = new DbCrud(new shippers_model());
        $this->crud->grid_show = '';
        $this->crud->grid_search = '';
        $this->crud->grid_delete = '';
        $this->crud->limit = 15;
        $this->crud->fields('CompanyName,Phone');
        $this->crud->setContstraints('ShipperID', 'Orders', 'ShipVia');
    }
}