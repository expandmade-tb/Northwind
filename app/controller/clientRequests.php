<?php

namespace controller;

use database\DBTable;

class clientRequests extends RequestController {
    private function get_value(string $value) : string {
        $result = htmlspecialchars(urldecode($_GET[$value] ?? ''));

        if (empty($result) )
            exit();

        return $result;
    }

    public function CustomersOninput($input='') {
        if ( $this->filter($input) === false ) {
            echo 'doing it wrong';
            return;           
        }

        $search_value = $this->get_value('search_value');
        $table = new DBTable('Customers');
        $results = $table->where('CompanyName', $search_value.'%', 'like')->limit(6)->findColumn('CompanyName');

        foreach ($results as $key => $value) 
            echo '<li class="dropdown-item" onclick="searchrelationSelect(this);">'.$value.'</li>';
    }

    public function ProductsOninput($input='') {
        if ( $this->filter($input) === false ) {
            echo 'doing it wrong';
            return;           
        }

        $search_value = $this->get_value('search_value');
        $table = new DBTable('Products');
        $results = $table->where('ProductName', $search_value.'%', 'like')->limit(10)->findColumn('ProductName');

        foreach ($results as $key => $value) 
            echo '<li class="dropdown-item" onclick="searchrelationSelect(this);">'.$value.'</li>';
    }

    public function CustomersOnchange($input='') {
        if ( $this->filter($input) === false ) {
            echo 'doing it wrong';
            return;           
        }

        $changed_value = urldecode($_GET['changed_value']??'');
        $table = new DBTable('Customers');
        $result = $table->where('CompanyName', $changed_value)->findFirst();
        echo json_encode($result);
    }
}