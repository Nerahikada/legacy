<?php

namespace Nerahikada\Rank;

use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\MainLogger;
use pocketmine\Server;

class DB extends AsyncTask{

	public $function;
	public $args;

	public function __construct(string $function, ...$args){
		$this->function = $function;
		$this->args = $args;
	}

	public function onRun(){
		$db = new \mysqli('host', 'user', 'password', 'database');

		$callable = [$this, $this->function];
		$callable($db, ...$this->args);

		$db->close();
	}

	public function onCompletion(Server $server){
		if($this->function === "getRank"){
			$result = $this->getResult();
			Rank::setPlayerRank(Main::$players[$this->args[0]], $result ?? 0);
		}else if($this->function === "setRank"){
			$result = $this->getResult();
			if($result === null){
				$this->sendMessage($this->args[2], "§cプレイヤーが見つかりませんでした");
			}else{
				$message = $this->args[1] === 0 ? "削除しました" : Rank::get($this->args[1])->getTag()."§r §fにセットしました";
				$this->sendMessage($this->args[2], "$result のRankを ".$message);
			}
		}else if($this->function === "displayRankList"){
			$result = $this->getResult();
			$list = "";
			foreach($result as $data)
				$list .= "§f{$data[0]}: ".Rank::get($data[1])->getTag()."§r\n";
			$this->sendMessage($this->args[0], $list);
		}
	}


	public function getRank(\mysqli $db, string $xuid){
		$result = $db->query("SELECT * FROM rank WHERE xuid = '$xuid'");
		if($result->num_rows !== 0)
			$this->setResult($result->fetch_assoc()["rank"]);
		$result->close();
	}

	public function setRank(\mysqli $db, string $name, int $rank, string $sender){
		$found = null;
		$delta = PHP_INT_MAX;
		$result = $db->query("SELECT * FROM ban.players WHERE name LIKE '$name%'");
		while($row = $result->fetch_assoc()){
			$curDelta = strlen($row["name"]) - strlen($name);
			if($curDelta < $delta){
				$found = $row;
				$delta = $curDelta;
			}
			if($curDelta === 0) break;
		}
		$result->close();

		if($found === null){
			$this->setResult(null);
			return;
		}

		$this->getRank($db, $found["xuid"]);
		$result = $this->getResult();
		$xuid = $found["xuid"];
		if($result === null){
			$db->query("INSERT INTO rank VALUES('$xuid', $rank)");
		}else{
			$db->query("UPDATE rank SET rank = $rank WHERE xuid = '$xuid'");
		}
		if($rank === 0){
			$db->query("DELETE FROM rank WHERE xuid = '$xuid'");
		}
		$this->setResult($found["name"]);
	}

	public function displayRankList(\mysqli $db, string $sender){
		$result = $db->query("SELECT * FROM rank");
		while($row = $result->fetch_assoc()){
			$name = $db->query("SELECT name FROM ban.players WHERE xuid = '{$row["xuid"]}'")->fetch_assoc()["name"];
			$list[] = [$name, $row["rank"]];
		}
		$this->setResult($list);
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

}