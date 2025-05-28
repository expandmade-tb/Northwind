<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class shippers_model extends DBTable {
    protected string $name = 'Shippers';

    public function DDL() : DbDDL {
      return DbDDL::table($this->name)
          ->integer('ShipperID', true, true)
          ->text('CompanyName', 255, true)
          ->text('Phone');
    }
}