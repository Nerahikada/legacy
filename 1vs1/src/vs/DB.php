<?php

namespace vs;

use pocketmine\item\Item;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\MainLogger;
use pocketmine\Server;

class DB extends AsyncTask{

	public $callable;
	public $xuid;
	public $key;		//or name, edition(int)
	public $value;	//or callback(func_name)


	public function __construct(string $callable, string $xuid, $key = null, $value = null){
		$this->callable = $callable;
		$this->xuid = $xuid;
		$this->key = $key;
		$this->value = $value;
	}


	public function connect(){
		$mysqli = new \mysqli('host', 'user', 'password', 'database');
		if($mysqli->connect_errno){
			MainLogger::getLogger()->error("Connect failed: " . $mysqli->connect_error);
			sleep(60);
			return $this->connect();
		}
		return $mysqli;
	}

	protected function query($db, $query){
		$result = $db->query($query);
		if(!$result){
			MainLogger::getLogger()->error("MySQL try: " . $query);
			MainLogger::getLogger()->error("MySQL error: " . $db->error);
			sleep(60);
			return $this->query($db, $query);
		}
		return $result;
	}


	public function onRun(){
		$db = $this->connect();
		$callable = [$this, $this->callable];
		$callable($db);
		$db->close();

		$result = $this->getResult();
		$result["inventory"] = $result["inventory"] === "" ? "" : json_decode($result["inventory"], true);
		$this->setResult($result);
	}

	public function onCompletion(Server $server){
		$result = $this->getResult();
		if(isset($result["rankingResult"])){
			$rankingResult = $result["rankingResult"];
			unset($result["rankingResult"]);
			Ranking::update($rankingResult);
		}
		if($result !== null){
			call_user_func_array([Main::getInstance(), 'updatePlayerData'], [$result, $this->value]);
		}
	}


	public function setup(\mysqli $db){
		$result = $this->query($db, "SELECT * FROM ".Main::DB_TABLE." WHERE xuid = '$this->xuid'");

		if($result->num_rows === 0){
			$this->query($db, "INSERT INTO ".Main::DB_TABLE." VALUES('$this->xuid', '$this->key', $this->value, 0, 0, 0, 0, 0, ".Main::UPDATE_VERSION.", '')");
			$this->value = "showUpdateFlag";
			$this->get($db, false);
		}else{
			$this->get($db, false);
			$this->query($db, "UPDATE ".Main::DB_TABLE." SET name = '$this->key', edition = $this->value, version = ".Main::UPDATE_VERSION." WHERE xuid = '$this->xuid'");
			(int) $this->getResult()["version"] !== Main::UPDATE_VERSION ? $this->value = "showUpdateFlag" : $this->value = null;
		}

		$result->close();
	}

	public function get(\mysqli $db, $getRanking = true){
		$result = $this->query($db, "SELECT * FROM ".Main::DB_TABLE." WHERE xuid = '$this->xuid'");
		$mainResult = $result->fetch_assoc();  // Volatile Object
		$result->close();

		if(!$getRanking){
			$this->setResult($mainResult);
			return;
		}

		$rankings = ['totalRanking' => "", 'editionRanking' => "AND edition = $this->key"];
		$columns = ['win', 'lose', 'draw', 'total', 'ratio'];
		foreach($rankings as $ranking => $addQuery){
			foreach($columns as $column){
				$result = $this->query($db, "SELECT *, (SELECT count(*)+1 FROM ".Main::DB_TABLE." AS t2 WHERE t2.$column > t1.$column $addQuery) AS rank FROM ".Main::DB_TABLE." AS t1 WHERE xuid = '$this->xuid'");
				$mainResult[$ranking][$column] = $result->fetch_assoc()["rank"];
				$result->close();
			}
		}

		$this->setResult($mainResult);
	}

	public function update(\mysqli $db){
		if($this->xuid !== "糞設計"){
			$thisKey = $this->key;
			$thisValue = $this->value;
			if(!$thisKey instanceof \Volatile){
				$thisKey = [$thisKey];
				$thisValue = [$thisValue];
			}

			// 0で割れない時の対処
			$this->get($db, false);
			$result = $this->getResult();
			if($result["lose"] == 0){
				$this->query($db, "UPDATE ".Main::DB_TABLE." SET lose = 1 WHERE xuid = '$this->xuid'");
				$zero = true;
			}

			$query = "";
			foreach($thisValue as $arrayKey => $value){
				if(is_string($value) &&
					strpos($value, "+") === false &&
					strpos($value, "-") === false &&
					strpos($value, "*") === false &&
					strpos($value, "/") === false
				) $value = "'$value'";
				$query .= "{$thisKey[$arrayKey]}=$value,";
			}
			$query = rtrim($query, ",");
			$this->query($db, "UPDATE ".Main::DB_TABLE." SET $query WHERE xuid = '$this->xuid'");

			// 0で割れない時の対処
			if(isset($zero)){
				$this->query($db, "UPDATE ".Main::DB_TABLE." SET lose = 0, total = total - 1 WHERE xuid = '$this->xuid'");
			}

			//Fix Ratio
			$this->get($db, false);
			$result = $this->getResult();
			if($result["ratio"] == null){
				$this->query($db, "UPDATE ".Main::DB_TABLE." SET ratio = win WHERE xuid = '$this->xuid'");
			}

			// Inventory
			if($this->key === "inventory" && $this->value === ""){
				$this->value = "setVsInventory";
			}
		}

		// Ranking!
		$rankingResult = [];
		$addQueries = [
			"",
			"WHERE edition = 0",
			"WHERE edition = 1",
		];
		$rankings = ["win", "ratio"];

		foreach($addQueries as $key => $addQuery){
			foreach($rankings as $ranking){
				$result = $this->query($db, "SELECT name, edition, win, lose, ratio FROM ".Main::DB_TABLE." $addQuery ORDER BY $ranking DESC LIMIT ".Ranking::LIMIT);
				while($row = $result->fetch_assoc()){
					$rankingResult[$key][$ranking][] = $row;
				}
				$result->close();
			}
		}

		$result = $this->getResult();
		$result["rankingResult"] = $rankingResult;
		$this->setResult($result);
	}

}