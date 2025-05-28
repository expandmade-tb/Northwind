<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class sessions_model extends DBTable {
    protected string $name = 'sessions';

    public function DDL() : DbDDL {
        return DbDDL::table($this->name)
            ->text('session_id', 32, true)
            ->text('session_data', 16384)
            ->integer('session_expire')
            ->primary_key('session_id');
    }
}