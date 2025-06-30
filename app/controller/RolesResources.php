<?php

namespace controller;

use dbgrid\DbCrud;
use models\roles_resources_model; 

class RolesResources extends CrudController {

    function __construct() {
        parent::__construct();
        
        $this->crud = new DbCrud(new roles_resources_model());
        $this->crud->grid_title = '';
        $this->crud->grid_show = '';
        $this->crud->grid_search = '';
        $this->crud->limit = 15;

        $sql = $this->crud->model()->getSQL('RolesResources-crud');

        if ( $sql !== false )
            $this->crud->gridSQL( $sql );
            
        $this->crud->fields('RoleResourceId,RoleId,ResourceId,RoleName,Controller');
        $this->crud->gridFields('RoleName,Controller');
        $this->crud->addFields('RoleId,ResourceId');
        $this->crud->editFields('RoleId,ResourceId');
        $this->crud->setRelation('RoleId', 'Name', 'Roles');
        $this->crud->setRelation('ResourceId', 'Controller', 'Resources');
        $this->crud->fieldTitles('RoleId,ResourceId,RoleName','Role,Resource,Role');
        $this->crud->onException([$this, 'handleException']);
    }

    public function handleException(mixed $th ) : string {
        return 'The role resource combination already exists'; 
    }
}