<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class employees_model extends DBTable {
    protected string $name = 'Employees';

    public function DDL() : DbDDL {
      return DbDDL::table($this->name)
          ->integer('EmployeeID', true, true)
          ->text('LastName')
          ->text('FirstName')
          ->text('Title')
          ->text('TitleOfCourtesy')
          ->text('BirthDate')
          ->text('HireDate')
          ->text('Address')
          ->text('City')
          ->text('Region')
          ->text('PostalCode')
          ->text('Country')
          ->text('HomePhone')
          ->text('Extension')
          ->text('Notes')
          ->integer('ReportsTo')
          ->text('PhotoPath')
          ->foreign_key('ReportsTo', 'Employees', 'EmployeeID');
     }
}