<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class roles_resources_model extends DBTable {
    protected string $name = 'RolesResources';

    public function DDL() : DbDDL {
		return DbDDL::table($this->name)
			->integer('RoleResourceId', true, true)
			->integer('RoleId', true)
			->integer('ResourceId', true)
			->unique_constraint('RoleId,ResourceId');
	}
}