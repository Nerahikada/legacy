<?php

namespace Nerahikada\Ban;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class DB extends AsyncTask{

	public $function;
	public $data;
	public $sender = null;

	// Banされていたら
	public $banned;
	// Banに成功
	public $success;


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
			$player = Main::$players[(int) $ban->xuid];
			$player->banned = $ban;
		}else if(isset($this->success)){
			$ban = $this->success;
			$this->sendMessage($ban->by, $ban->name."をBanしました");
			$player = Server::getInstance()->getPlayerExact($ban->name);
			if($player !== null){
				Main::move($player, $ban);
			}
		}else if($this->function === "ban"){
			$this->sendMessage($this->data->by, "§cBanに失敗しました");
		}else if($this->function === "banlist"){
			$this->sendMessage($this->data, $this->getResult());
		}
	}

	public function sendMessage(string $sender, string $message){
		if($sender === "CONSOLE"){
			Server::getInstance()->getLogger()->info($message);
		}else{
			$sender = Server::getInstance()->getPlayerExact($sender);
			if($sender !== null){
				$sender->sendMessage($message);
			}
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


	public function onJoin($db, $player){
		$xuid = $player->xuid;
		$name = $player->name;
		$ip = $player->ip;
		$cid = $player->cid;

		$result = $db->query("SELECT * FROM players WHERE xuid = '$xuid'");

		if($result->num_rows === 0){
			$db->query("INSERT INTO players VALUES('$xuid', '$name', '$ip', $cid)");
		}else{
			$db->query("UPDATE players SET name = '$name', ip = '$ip', cid = $cid WHERE xuid = '$xuid'");
		}

		$result->close();
	}



	public function ban($db, $ban){
		$found = $this->getPlayerData($db, $ban->name);
		if($found === null) return;

		$ban->addPlayerData($found);

		$id = $ban->id;
		$xuid = $ban->xuid;
		$ip = $ban->ip;
		$cid = $ban->cid;
		$reason = $ban->reason;
		$time = $ban->time;
		$forever = $ban->forever ? 'TRUE' : 'FALSE';
		$limit = $ban->limit;
		$by = $ban->by;

		//$db->query("INSERT INTO ban VALUES('$id', '$xuid', '$ip', $cid, TRUE, '$reason', $time, $forever, $limit, '$by')");
		$stmt = $db->prepare("INSERT INTO ban VALUES('$id', '$xuid', '$ip', $cid, TRUE, ?, $time, $forever, $limit, '$by')");
		$stmt->bind_param('s', $reason);
		$stmt->execute();

		$this->success = $ban;
	}

	public function pardon($db, $id){
		$db->query("UPDATE ban SET banned = FALSE WHERE id = '$id'");
	}


	public function banlist($db){
		$list = "";
		$result = $db->query("SELECT * FROM ban");
		while($row = $result->fetch_assoc()){
			$ban = new Ban($row);
			$name = $db->query("SELECT name FROM players WHERE xuid = '{$ban->xuid}'")->fetch_assoc()["name"];
			$banned = $ban->banned ? "§cBanned" : "§aPardon";
			$list .= "§b{$ban->id}: §f$name $banned §f\n";
		}
		$this->setResult($list);
	}



	public function getPlayerData($db, $name){
		$found = null;
		$delta = PHP_INT_MAX;
		$result = $db->query("SELECT * FROM players WHERE name LIKE '$name%'");
		while($row = $result->fetch_assoc()){
			$curDelta = strlen($row['name']) - strlen($name);
			if($curDelta < $delta){
				$found = $row;
				$delta = $curDelta;
			}
			if($curDelta === 0) break;
		}
		$result->close();
		return $found;
	}

}