<?php

namespace controller;

use database\DBTable;

class clientRequests extends RequestController {
    const ONCLICK = 'form_field_select(this);';

    private function get_value(string $value) : string {
        $result = htmlspecialchars(urldecode($_GET[$value] ?? ''));

        if (empty($result) )
            exit();

        return $result;
    }

    public function CustomersOninput(string $input='') : void { // example code
        if ( $this->filter($input) === false ) {
            echo 'doing it wrong';
            return;           
        }
 
        $search_value = $this->get_value('search_value');
        $table = new DBTable('Customers');
        $results = $table->where('CompanyName', $search_value.'%', 'like')->limit(6)->findColumn('CompanyName');

        foreach ($results as $key => $value) {
            $safe_value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            echo '<li class="dropdown-item" onclick="'.clientRequests::ONCLICK.'">' . $safe_value . '</li>';
        }
    }

    public function CustomersOnchange(string $input='') : void {  // example code
        if ( $this->filter($input) === false ) {
            echo 'doing it wrong';
            return;           
        }

        $changed_value = urldecode($_GET['changed_value']??'');
        $table = new DBTable('Customers');
        $result = $table->where('CompanyName', $changed_value)->findFirst();
        echo json_encode($result);
    }

    public function ProductsOninput(string $input='') : void { // example code
        if ( $this->filter($input) === false ) {
            echo 'doing it wrong';
            return;           
        }
 
        $search_value = $this->get_value('search_value');
        $table = new DBTable('Products');
        $results = $table->where('ProductName', $search_value.'%', 'like')->limit(6)->findColumn('ProductName');

        foreach ($results as $key => $value) {
            $safe_value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            echo '<li class="dropdown-item" onclick="'.clientRequests::ONCLICK.'">' . $safe_value . '</li>';
        }
    }
}