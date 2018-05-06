<?php

namespace Nerahikada\Banned;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class DB extends AsyncTask{

	public $function;
	public $data;

	public $banned;


	public function __construct($function, $data){
		$this->function = $function;
		$this->data = $data;
	}

	public function onRun(){
		$db = new \mysqli('host', 'user', 'password', 'database');
		$callable = [$this, $this->function];
		$callable($db, $this->data);
		$db->close();
	}

	public function onCompletion(Server $server){
		if(isset($this->banned)){
			$ban = $this->banned;
			$player = Main::$players[$ban->xuid];
			$player->banned = $ban;
		}
	}

	public function isBanned($db, $player){
		$xuid = $player->xuid;
		$ip = $player->ip;
		$cid = $player->cid;

		$result = $db->query("SELECT * FROM ban WHERE xuid = '$xuid' || ip = '$ip' || cid = $cid");

		if($result->num_rows === 0) return;

		while($row = $result->fetch_assoc()){
			$ban = new Ban($row);
			if($ban->banned){
				if(!$ban->forever && $ban->time + $ban->limit < time()){
					$this->pardon($db, $ban->id);
					continue;
				}
				$this->banned = $ban;
			}
		}

		$result->close();
	}

	public function pardon($db, $id){
		$db->query("UPDATE ban SET banned = FALSE WHERE id = '$id'");
	}

}