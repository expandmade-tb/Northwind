<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class categories_model extends DBTable {
    protected string $name = 'Categories';

    public function DDL() : DbDDL {
        return DbDDL::table($this->name)
            ->integer('CategoryID', true, true)
            ->text('CategoryName')
            ->text('Description');
    }
}