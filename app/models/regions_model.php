<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class regions_model extends DBTable {
    protected string $name = 'Regions';

    public function DDL() : DbDDL {
      return DbDDL::table($this->name)
          ->integer('RegionID', true)
          ->text('RegionDescription', 255, true)
          ->text('Description')
          ->primary_key('RegionID');
    }
}