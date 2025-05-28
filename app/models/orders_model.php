<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class orders_model extends DBTable {
    protected string $name = 'Orders';
  
    public function DDL() : DbDDL {
      return DbDDL::table($this->name)
          ->integer('OrderID', true, true)
          ->text('CategoryName')
          ->text('CustomerID')
          ->integer('EmployeeID')
          ->text('OrderDate')
          ->text('RequiredDate')
          ->text('ShippedDate')
          ->integer('ShipVia')
          ->numeric('Freight')
          ->text('ShipName')
          ->text('ShipAddress')
          ->text('ShipCity')
          ->text('ShipRegion')
          ->text('ShipPostalCode')
          ->text('ShipCountry')
          ->foreign_key('EmployeeID', 'Employees', 'EmployeeID')
          ->foreign_key('CustomerID', 'Customers', 'CustomerID')
          ->foreign_key('ShipVia', 'Shippers', 'ShipperID');
      }

    public function delete(array|string $id): bool {
        $od = new orderdetails_model();
        $od->database()->beginTransaction();
        
        try {
            $od->deleteOrder($id);
            parent::delete($id);
            $od->database()->commit();
        } catch (\Throwable $th) {
            $od->database()->rollBack();
            throw $th;
        }

        return true;
    }
}