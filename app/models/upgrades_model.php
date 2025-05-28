<?php

namespace models;

use database\DBTable;
use database\DbDDL;

class upgrades_model extends DBTable {
	protected string $name = 'Upgrades';

	public function DDL() : DbDDL {
		return DbDDL::table($this->name)
			->integer('upgrade_id',true)
			->integer('run_date')
			->text('version_info')
			->primary_key('upgrade_id');
	}
}