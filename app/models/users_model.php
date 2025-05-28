<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class users_model extends DBTable {
    protected string $name = 'Users';

    public function DDL() : DbDDL {
		return DbDDL::table($this->name)
			->text('UserId', 255, true)
			->text('KeyCode', 255, true, true)
			->text('Name', 255, true)
			->text('Mail')
			->integer('ValidUntil')
			->text('ClientId')
			->integer('AccessControl')
			->integer('RoleId')
			->primary_key('UserId');
    }
}