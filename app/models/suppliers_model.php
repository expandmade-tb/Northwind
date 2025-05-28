<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class suppliers_model extends DBTable {
    protected string $name = 'Suppliers';

    public function DDL() : DbDDL {
        return DbDDL::table($this->name)
            ->integer('SupplierID', true, true)
            ->text('CompanyName', 255, true)
            ->text('ContactName')
            ->text('ContactTitle')
            ->text('Address')
            ->text('City')
            ->text('Region')
            ->text('PostalCode')
            ->text('Country')
            ->text('Phone')
            ->text('Fax')
            ->text('HomePage');
    }
}