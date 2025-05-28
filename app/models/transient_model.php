<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class transient_model extends DBTable {
    protected string $name = 'transient';

    public function DDL() : DbDDL {
        return DbDDL::table($this->name)
            ->text('id', 255, true)
            ->text('data', 8192)
            ->integer('valid_until')
            ->primary_key('id');
    }
}