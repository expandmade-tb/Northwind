<?php

namespace controller;

/**
 * 
 * This controller initializes the database
 * 
 */

use database\DBTable;
use helper\Helper;
use models\categories_model;
use models\customers_model;
use models\employee_territory_model;
use models\employees_model;
use models\orderdetails_extended_view;
use models\orderdetails_model;
use models\orders_model;
use models\orders_subtotals_view;
use models\orders_extended_view;
use models\products_model;
use models\regions_model;
use models\resources_model;
use models\roles_model;
use models\roles_resources_model;
use models\transient_model;
use models\upgrades_model;
use models\users_model;
use models\sessions_model;
use models\shippers_model;
use models\suppliers_model;
use models\territories_model;
use models\user_clients_model;

defined( 'BASEPATH' ) || exit;

class Init {
    private int $errors;

    private function create_models() : void {
        echo "<br>creating models/views... ";
        // internal
        new transient_model();
        new upgrades_model();
        new users_model();
        new user_clients_model();
        new roles_model();
        new resources_model();
        new roles_resources_model();
        new sessions_model();
        // app data
        new categories_model();
        new customers_model();
        new employees_model();
        new products_model();
        new regions_model();
        new territories_model();
        new employee_territory_model();
        new shippers_model();
        new suppliers_model();
        new orders_model();
        new orderdetails_model();
        new orderdetails_extended_view();
        new orders_subtotals_view();
        new orders_extended_view();
        echo "succesfully";
    }

    private function import_models() : void {
        $table_list = [
            'Categories' => 'Categories',
            'Customers' => 'Customers',
            'Employees' => 'Employees',
            'Suppliers' => 'Suppliers',
            'Products' => 'Products',
            'Regions' => 'Regions',
            'Territories' => 'Territories',
            'EmployeeTerritory' => 'EmployeeTerritories',
            'Shippers' => 'Shippers',
            'Orders' => 'Orders',
            'OrderDetails' => 'Order Details'
        ];

        $location = Helper::env('storage_location').'/data/';
        echo "<br>importing models... ";

        foreach ($table_list as $tablename => $csv) {
            if ( $tablename[0] != '*' ) {
                echo "<br>&nbsp;-&nbsp;$tablename... ";
                $table = new DBTable($tablename);
                $this->errors = 0;
                $table->import("{$location}{$csv}.csv", ['on_insert_error'=>[$this, 'onImportError']]);
                echo "{$this->errors} errors";
            }
        }

         echo "<br>done";
    }

    public function onImportError(int $linecount, string $line) : bool {
        $this->errors++;
        return false;
    }

    public function index () : void {
        $this->create_models();
        $this->import_models();
    }
}