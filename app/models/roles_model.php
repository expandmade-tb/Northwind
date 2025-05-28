<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class roles_model extends DBTable {
    protected string $name = 'Roles';

    public function DDL() : DbDDL {
		return DbDDL::table($this->name)
			->integer('RoleId', true, true)
			->text('Name', 64, true)
			->text('Description');
    }
}