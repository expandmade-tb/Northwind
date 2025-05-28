<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class resources_model extends DBTable {
    protected string $name = 'Resources';

    public function DDL() : DbDDL {
		return DbDDL::table($this->name)
			->integer('ResourceId', true, true)
			->text('Description')
			->text('Controller', 64, true);
    }
}