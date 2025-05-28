<?php

namespace models;

use database\DBTable;
use database\DbDDL;
use Exception;

class user_clients_model extends DBTable {
    protected string $name = 'UserClients';

	public function DDL() : DbDDL {
		return DbDDL::table($this->name)
			->text('UserId', 255, true)
			->integer('Sequence', true)
			->text('ClientId', 255, true)
			->primary_key('UserId,Sequence')
			->foreign_key('UserId', 'Users', 'UserId');
    }

	public function add(string $user_id, string $client_id) : int|bool {
		$result = $this->where('UserId', $user_id)->where('ClientId', $client_id)->findColumn('Sequence');

		if ( count($result) > 0 )
			return intval($result[0]);

		$result = $this->where('UserId', $user_id)->findColumn('max(Sequence)');

		if ( is_null($result[0]) )
			$seq = 1;
		else
			$seq = intval($result[0]) + 1;

		$result = $this->insert([
			'UserId'=>$user_id,
			'Sequence'=>$seq,
			'ClientId'=>$client_id
		]);

		if ( $result === false )
			return false;

		// cleaning up possible old client id's of this particular user
		 
		$sql = $this->getSQL('UserClientsCleanup');

		if ( $sql == false ) {
			trigger_error('sql statement not loaded');
			return $seq;
		}

		$stmt = $this->database()->prepare($sql);

		if ( $stmt === false ) {
			trigger_error('sql statement not executed');
			return $seq;
		}

		$result = $stmt->execute([$user_id, $user_id]);

		return $seq;
	}

	public function delete_all(string $user_id) : void {
        $sql = "delete from {$this->name} where UserId = ?";
        $stmt = $this->database()->prepare($sql);

        if ( $stmt === false )
            throw new Exception("delete stmt not prepared for table $this->name");

        $result = $stmt->execute([$user_id]);
        
        if ( $result === false )
            throw new Exception("data cannot be deleted from table $this->name");
	}
}