<?php

namespace controller;

use dbgrid\DbCrud;
use models\employees_model;

class Employees extends CrudController {

    function __construct() {
        parent::__construct();
        
        $this->crud = new DbCrud(new employees_model());
        $this->crud->grid_title = '';
        $this->crud->grid_show = '';
        $this->crud->grid_delete = '';
        $this->crud->limit = 15;
        $this->crud->searchFields('LastName,City');
        $this->crud->fields('LastName,FirstName,Title,TitleOfCourtesy,BirthDate,HireDate,Address,City,Region,PostalCode,Country,HomePhone,Extension,Notes,ReportsTo,PhotoPath');
        $this->crud->gridFields('LastName,FirstName,Title,TitleOfCourtesy,BirthDate,HireDate,City,Country,HomePhone,Extension');
        $this->crud->requiredFields('LastName,FirstName,Title,TitleOfCourtesy,BirthDate,HireDate,Address,City,PostalCode,Country,HomePhone,Extension');
        $this->crud->setRule('BirthDate', 'date');
        $this->crud->setRule('HireDate', 'date');
        $this->crud->setRelation('ReportsTo', 'LastName', 'Employees');
        $this->crud->setFieldProperty('Notes', type: 'textarea');
        $this->crud->fieldPlaceholder('Title', 'Sales Representative, Sales Manager');
        $this->crud->fieldPlaceholder('TitleOfCourtesy', 'Mr./Ms./Mrs.');
        $this->crud->fieldPlaceholder('BirthDate', '1968-01-30');
        $this->crud->fieldPlaceholder('HireDate', '1968-01-30');
        $this->crud->fieldPlaceholder('Region', 'North America');
        $this->crud->setContstraints('EmployeeID', 'Orders', 'EmployeeID');
        $this->crud->setContstraints('ReportsTo', 'Employees', 'EmployeeID');
        $this->crud->setDatepicker('BirthDate');
        $this->crud->setDatepicker('HireDate');
    }
}