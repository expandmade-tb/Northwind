<?php

namespace models;

use database\DBTable;
use Exception;
use database\DbDDL;

class orderdetails_model extends DBTable {
    protected string $name = 'OrderDetails';

    public function DDL() : DbDDL {
        return DbDDL::table($this->name)
            ->integer('OrderDetailID', true, true)
            ->integer('OrderID', true )
            ->integer('ProductID', true)
            ->numeric('UnitPrice', true)
            ->integer('Quantity', true)
            ->real('Discount')
            ->foreign_key('ProductID', 'Products', 'ProductID')
            ->foreign_key('OrderID', 'Orders', 'OrderID');
    }

    public function deleteOrder($id) : void {
        $sql = "delete from {$this->name} where OrderID = ?";
        $stmt = $this->database()->prepare($sql);

        if ( $stmt === false )
            throw new Exception("delete stmt not prepared for table $this->name");

        $result = $stmt->execute([$id]);
        
        if ( $result === false )
            throw new Exception("data cannot be deleted from table $this->name");
    }
}