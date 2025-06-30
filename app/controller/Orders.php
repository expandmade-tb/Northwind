<?php

namespace controller;

use dbgrid\DbCrud;
use models\orders_model;

class Orders extends CrudController {

    function __construct() {
        parent::__construct();
        $this->crud = new DbCrud(new orders_model());
        $this->crud->grid_title = '';
        $this->crud->grid_show = '';
        $this->crud->grid_delete = '';
        $this->crud->limit = 15;
        $this->crud->fields('OrderID,CustomerID,EmployeeID,OrderDate,RequiredDate,ShippedDate,ShipVia,Freight,ShipName,ShipAddress,ShipCity,ShipRegion,ShipPostalCode,ShipCountry');
        $this->crud->addFields('CustomerID,EmployeeID,OrderDate,RequiredDate,ShippedDate,ShipVia,Freight,ShipName,ShipAddress,ShipCity,ShipRegion,ShipPostalCode,ShipCountry');
        $this->crud->gridFields('OrderID,OrderDate,RequiredDate,ShipName,ShippedDate,Freight,ShipCity,ShipCountry');
        $this->crud->searchFields('OrderDate,ShipName,ShipCity,ShipCountry');
        $this->crud->requiredFields('ShipName,ShipAddress,ShipCity,ShipPostalCode,ShipCountry');
        $this->crud->fieldTitles('CustomerID,EmployeeID','Customer,Employee');
        $this->crud->setRelation('EmployeeID', 'LastName', 'Employees');
        $this->crud->setRelation('ShipVia', 'CompanyName', 'Shippers');
        $this->crud->setSearchRelation('CustomerID', 'Customers', 'CompanyName');
        $this->crud->setRule('OrderDate','date');
        $this->crud->setRule('RequiredDate','date');
        $this->crud->setRule('ShippedDate','date');
        $this->crud->setDatepicker('OrderDate');
        $this->crud->setDatepicker('RequiredDate');
        $this->crud->setDatepicker('ShippedDate');
        $this->crud->linkedTable('OrderDetails', 'Details', 'selectorder');

        $this->crud->fieldOnChange('CustomerID', 'Customers', 
            ['CompanyName'=>'ShipName', 'Address'=>'ShipAddress','City'=>'ShipCity','Region'=>'ShipRegion',
             'PostalCode'=>'ShipPostalCode','Country'=>'ShipCountry',]);
        
            $this->crud->layout_grid(['OrderID,CustomerID','EmployeeID','OrderDate,','ShippedDate,RequiredDate','ShipVia,Freight','ShipName,ShipAddress','ShipCity,ShipRegion','ShipPostalCode,ShipCountry']);
    }
}