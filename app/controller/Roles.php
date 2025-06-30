<?php

namespace controller;

use dbgrid\DbCrud;
use models\roles_model; 

class Roles extends CrudController {

    function __construct() {
        parent::__construct();
        
        $this->crud = new DbCrud(new roles_model());
        $this->crud->grid_title = '';
        $this->crud->grid_show = '';
        $this->crud->grid_delete = '';
        $this->crud->limit = 15;

        $this->crud->fields('RoleId,Name,Description');
        $this->crud->addFields('Name,Description');
        $this->crud->readonlyFields('RoleId');
        $this->crud->searchFields('Name');
        $this->crud->setContstraints('RoleId', 'RolesResources', 'RoleId');
    }
}