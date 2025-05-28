<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class customers_model extends DBTable {
    protected string $name = 'Customers';

    public function DDL() : DbDDL {
        return DbDDL::table($this->name)
            ->text('CustomerID')
            ->text('CompanyName')
            ->text('ContactName')
            ->text('ContactTitle')
            ->text('Address')
            ->text('City')
            ->text('Region')
            ->text('PostalCode')
            ->text('Country')
            ->text('Phone')
            ->text('Fax')
            ->primary_key('CustomerID');
    }
}