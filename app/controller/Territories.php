<?php

namespace controller;

use dbgrid\DbCrud;
use models\territories_model;

class Territories extends CrudController {

    function __construct() {
        parent::__construct();
        
        $this->crud = new DbCrud(new territories_model());
        $this->crud->grid_show = '';
        $this->crud->grid_delete = '';
        $this->crud->grid_search = '';
        $this->crud->limit = 15;
        $this->crud->grid_sql = $this->crud->model()->getSQL('Territories-select.sql');
        $this->crud->fieldTitles('TerritoryID,TerritoryDescription,RegionID','Territory,Territory Description,Region');
        $this->crud->setRelation('RegionID', 'RegionDescription', 'Regions');
    }
}