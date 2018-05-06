<?php

namespace Nerahikada\Ban;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Player;
use pocketmine\Server;

class DB extends AsyncTask{

	public function __construct($callable, $data, $sender = null){
		$this->callable = $callable;

		if($data instanceof Player){
			$pd = new \stdClass;
			$pd->name = strtolower($data->getName());
			$pd->ip = $data->getAddress();
			$pd->cid = $data->getClientId();
			$this->data = $pd;
		}else{
			$this->data = $data;
		}

		if($sender !== null) $this->sender = $sender->getName();
	}

	public function onRun(){
		$db = new \mysqli('host', 'user', 'password', 'database');
		$db->set_charset("utf8");

		$callable = [$this, $this->callable];
		$callable($db, $this->data);

		$db->close();
	}

	public function onCompletion(Server $server){
		if(isset($this->banned)){
			$ban = $this->banned;
			$msg = $this->getMsg($ban);
			$player = $server->preLogin[$ban->name];
			$player->kick(
				'§l§4BANされています§r: §f'.$msg."\n".
				'§7理由: §f'.$ban->reason."\n".
				'§7Ban ID: §f'.$ban->id
			, false);

		}else if(isset($this->ban)){
			$ban = $this->ban;

			if($this->sender !== null) $this->sendMessage($server, $this->sender, $ban->name.'をBanしました');

			$player = $server->getPlayerExact($ban->name);
			if($player !== null){
				$msg = $this->getMsg($ban);
				$player->kick(
					'§l§4BANされました§r: §f'.$msg."\n".
					'§7理由: §f'.$ban->reason."\n".
					'§7Ban ID: §f'.$ban->id
				, false);
			}

		}/*else if(isset($this->pardon)){
			if($this->sender !== null) $this->sendMessage($server, $this->sender, $ban->name.'のBanを解除しました');
		}*/
	}

	protected function sendMessage($server, $sender, $msg){
		$sender = $server->getPlayerExact($sender);
		if($sender !== null) $sender->sendMessage($msg);
		else $server->getLogger()->info($msg);
	}

	protected function getMsg($ban){
		if($ban->forever) $msg = '§c無期限BAN§f';
		else{
			if(isset($ban->time)) $limit = $ban->limit - (time() - $ban->time);
			else $limit = $ban->limit;

			$day = floor($limit / 86400);
			$hour = floor(($limit / 3600) % 24);
			$min = floor(($limit / 60) % 60);
			$sec = $limit % 60;

			$date = [[$day, '!d'], [$hour, '!h'], [$min, '!m'], [$sec, '!s']];
			foreach($date as $key => $value) if($value[0] == 0) unset($date[$key]); else break;
			$msg = '';
			foreach($date as $value) $msg .= str_replace('!', $value[0], $value[1]).' ';
		}
		return $msg;
	}

	protected function banned($db, $player){
		$name = $player->name;
		$ip = $player->ip;
		$cid = $player->cid;

		$result = $db->query("SELECT * FROM ban WHERE name = '$name' || ip = '$ip' || cid = $cid");

		if($result->num_rows === 0) return;

		while($row = $result->fetch_assoc()){
			if($row['banned']){
				if(!$row['forever'] && $row['time'] + $row['limit'] < time()){
					$this->pardon($db, $name);
					continue;
				}
				$ban = new \stdClass;
				$ban->id = $row['id'];
				$ban->name = $name;
				$ban->reason = $row['reason'];
				$ban->forever = $row['forever'] ? true: false;
				$ban->time = $row['time'];
				$ban->limit = $row['limit'];
				$this->banned = $ban;
			}
		}
		$result->close();
	}

	protected function ban($db, $ban){
		$found = $this->getPlayerData($db, $ban->name);
		if($found === null) return;

		$id = strtoupper(uniqid());
		$name = $found['name'];
		$ip = $found['ip'];
		$cid = $found['cid'];
		$reason = $ban->reason;
		$now = time();
		$forever = $ban->forever ? 'true' : 'false';
		$limit = $ban->limit;
		$db->query("INSERT INTO ban VALUES('$id', '$name', '$ip', $cid, true, '$reason', $now, $forever, $limit)");

		$ban->id = $id;
		$ban->name = $name;
		$this->ban = $ban;
	}

	protected function pardon($db, $name){
		$found = $this->getPlayerData($db, $name);
		$name = $found['name'];
		$db->query("UPDATE ban SET banned = false WHERE name = '$name'");
		$this->pardon = $name;
	}


	protected function getPlayerData($db, $name){
		$found = null;
		$delta = PHP_INT_MAX;
		$result = $db->query("SELECT * FROM players WHERE name LIKE '$name'");
		while($row = $result->fetch_assoc()){
			$curDelta = strlen($row['name']) - strlen($name);
			if($curDelta < $delta){
				$found = $row;
				$delta = $curDelta;
			}
			if($curDelta === 0) break;
		}
		return $found;
	}



	protected function players($db, $player){
		$name = $player->name;
		$ip = $player->ip;
		$cid = $player->cid;

		$result = $db->query("SELECT * FROM players WHERE name = '$name'");

		if($result->num_rows === 0){
			$db->query("INSERT INTO players VALUES('$name', '$ip', $cid)");
		}else{
			$db->query("UPDATE players SET name = '$name', ip = '$ip', cid = $cid WHERE name = '$name'");
		}

		$result->close();
	}

}