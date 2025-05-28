<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class products_model extends DBTable {
    protected string $name = 'Products';

    public function DDL() : DbDDL {
      return DbDDL::table($this->name)
          ->integer('ProductID', true, true)
          ->text('ProductName', true, false, true)
          ->integer('SupplierID')
          ->integer('CategoryID')
          ->text('QuantityPerUnit')
          ->numeric('UnitPrice')
          ->integer('UnitsInStock')
          ->integer('UnitsOnOrder')
          ->integer('ReorderLevel')
          ->text('Discontinued')
          ->foreign_key('CategoryID', 'Categories', 'CategoryID')
          ->foreign_key('SupplierID', 'Suppliers', 'SupplierID');
  }
}