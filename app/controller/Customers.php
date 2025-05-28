<?php

namespace controller;

use dbgrid\DbCrud;
use models\customers_model;

class Customers extends CrudController {

    function __construct() {
        parent::__construct();
        $this->html_compress = false;
        
        $this->crud = new DbCrud(new customers_model(), );
        $this->crud->grid_show = '';
        $this->crud->grid_delete = '';
        $this->crud->limit = 15;
        $this->crud->fields('CustomerID,CompanyName,ContactName,ContactTitle,Address,City,Region,PostalCode,Country,Phone,Fax');
        $this->crud->addFields('CompanyName,ContactName,ContactTitle,Address,City,Region,PostalCode,Country,Phone,Fax');
        $this->crud->gridFields('CustomerID,CompanyName,ContactName,ContactTitle,City,Country,Phone');
        $this->crud->searchFields('CompanyName,ContactName,City,Country');
        $this->crud->requiredFields('CompanyName,Address,City,PostalCode,Country');
        $this->crud->fieldPlaceholder('ContactTitle', 'Sales Representative, Owner, Order Administrator');
        $this->crud->fieldPlaceholder('Region', 'Western Europe, Central America');
        $this->crud->layout_grid(['CustomerID,CompanyName', 'ContactName,ContactTitle', 'Address,' ,'PostalCode,City','Region,Country','Phone,Fax']);
        $this->crud->callbackInsert([$this, 'onInsert']);
        $this->crud->setContstraints('CustomerID', 'Orders', 'CustomerID');
    }

    private function shorten(string $value) : string {
        $filter = str_replace(['.', '-', '_', '+', '&'],['', '', '', ' ', ' '],$value);
        $trim = preg_replace('/\s+/', ' ', $filter, 5);
        $result = '';
    
        foreach (explode(' ', $trim) as $key => $value) {
            $result .= substr($value, 0, 3);
    
            if ( strlen($result) > 5 )
                break;
        }
    
        if ( strlen($result) < 5 )
            return strtoupper(substr($trim, 0, 5));
        else
            return strtoupper(substr($result, 0, 5));
    }
    
    public function onInsert($data) : void {
        $id = $this->shorten($data['CompanyName']);

        if ( $this->crud->model()->find($id) !== false )
            $id = time();

        $data['CustomerID'] = $id;
        $this->crud->model()->insert($data);
    }
}