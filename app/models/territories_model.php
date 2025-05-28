<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class territories_model extends DBTable {
    protected string $name = 'Territories';

    public function DDL() : DbDDL {
        return DbDDL::table($this->name)
            ->text('TerritoryID', 32, true)
            ->text('TerritoryDescription', 255, true)
            ->integer('RegionID', true)
            ->primary_key('TerritoryID')
            ->foreign_key('RegionID', 'Regions', 'RegionID');
    }
}