<?php

namespace Nerahikada\Ban;

use pocketmine\Player;

class Ban{

	public $id;			// string
	public $xuid;		// string
	public $name;		// string
	public $ip;			// string
	public $cid;		// int
	public $banned;		// bool
	public $reason;		// string
	public $time;		// int
	public $forever;	// bool
	public $limit;		// INT
	public $by;			// string

	// "P"arameter
	public function __construct($p = null){
		if($p instanceof Player){
			$this->xuid = $p->getXuid();
			$this->name = $p->getName();
			$this->ip = $p->getAddress();
			$this->cid = $p->getClientId();
		}else{
			if(isset($p["id"])){ //Ban済みデータ
				$this->id = $p["id"];
				$this->xuid = $p["xuid"];
				$this->banned = (bool) $p["banned"];
				$this->reason = $p["reason"];
				$this->time = $p["time"];
				$this->forever = (bool) $p["forever"];
				$this->limit = $p["limit"];
			}else{
				$this->id = strtoupper(uniqid());
				$this->time = time();
				$this->forever = false;
				$this->limit = -1;
			}
		}
	}

	// Ban専用
	public function addPlayerData($data){
		$this->xuid = $data["xuid"];
		$this->name = $data["name"];
		$this->ip = $data["ip"];
		$this->cid = $data["cid"];
	}

}