<?php

namespace controller;

use dbgrid\DbCrud;
use models\categories_model;

class Categories extends CrudController {

    function __construct() {
        parent::__construct();
        
        $this->crud = new DbCrud(new categories_model());
        $this->crud->grid_delete = '';
        $this->crud->grid_show = '';
        $this->crud->limit = 15;
        $this->crud->searchFields('Description');
        $this->crud->fields('CategoryName,Description');
        $this->crud->setContstraints('CategoryID', 'Products', 'CategoryID');
    }
}