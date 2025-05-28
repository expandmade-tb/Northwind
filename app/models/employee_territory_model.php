<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class employee_territory_model extends DBTable {
    protected string $name = 'EmployeeTerritory';

    public function DDL() : DbDDL {
        return DbDDL::table($this->name)
            ->integer('EmployeeTerritoryID', true, true)
            ->text('TerritoryID', 32, true)
            ->integer('EmployeeID', true)
            ->foreign_key('TerritoryID', 'Territories', 'TerritoryID')
            ->foreign_key('EmployeeID', 'Employees', 'EmployeeID');
    }
}