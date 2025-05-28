<?php

namespace controller;

use database\DBTable;

class clientRequests extends RequestController {

    public function CustomersSearch($input='') {
        if ( $this->filter($input) === false ) {
            echo 'doing it wrong';
            return;           
        }

        $table = new DBTable('Customers');
        $results = $table->where('CompanyName',$input.'%', 'like')->limit(6)->findColumn('CompanyName');

        foreach ($results as $key => $value) 
            echo '<li class="dropdown-item" onclick="searchrelationSelect(this);">'.$value.'</li>';
    }

    public function ProductsSearch($input='') {
        if ( $this->filter($input) === false ) {
            echo 'doing it wrong';
            return;           
        }

        $table = new DBTable('Products');
        $results = $table->where('ProductName',$input.'%', 'like')->limit(10)->findColumn('ProductName');

        foreach ($results as $key => $value) 
            echo '<li class="dropdown-item" onclick="searchrelationSelect(this);">'.$value.'</li>';
    }
}