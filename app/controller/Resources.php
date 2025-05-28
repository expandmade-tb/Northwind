<?php

namespace controller;

use dbgrid\DbCrud;
use models\resources_model;
use Router\Routes;

class Resources extends CrudController { 

    function __construct() {
        parent::__construct();
        $this->html_compress = false;
        
        $this->crud = new DbCrud(new resources_model()); 
        $this->crud->grid_show = '';
        $this->crud->grid_delete = '';
        $this->crud->limit = 15;

        $this->crud->fields('ResourceId,Description,Controller');
        $this->crud->addFields('Description,Controller');
        $this->crud->readonlyFields('ResourceId');
        $this->crud->searchFields('Description,Controller');
        $this->crud->setContstraints('ResourceId', 'RolesResources', 'ResourceId');

        $valuelist = implode(',', array_keys(Routes::$routes));
        $this->crud->fieldType('Controller', 'select', $valuelist);
    }
}