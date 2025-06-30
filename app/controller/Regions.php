<?php

namespace controller;

use dbgrid\DbCrud;
use models\regions_model;

class Regions extends CrudController {

    function __construct() {
        parent::__construct();
        
        $this->crud = new DbCrud(new regions_model());
        $this->crud->grid_title = '';
        $this->crud->grid_show = '';
        $this->crud->grid_search = '';
        $this->crud->grid_delete = '';
        $this->crud->limit = 15;
        $this->crud->fields('RegionDescription');
        $this->crud->setContstraints('RegionID', 'Territories', 'RegionID');
    }
}