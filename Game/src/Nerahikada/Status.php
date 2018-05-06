<?php

namespace Nerahikada;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;
use pocketmine\utils\UUID;

class Status{

	const X = 368.5;
	const Y = 41.5;
	const Z = -865.2;

	public $server;
	public $plugin;
	public $player;
	public $add = [];
	public $remove = [];

	public function __construct($plugin, $player){
		$this->server = $plugin->server;
		$this->plugin = $plugin;
		$this->player = $player;
		for($i = 0; $i < 7; $i++){
			$eid = Entity::$entityCount++;
			$pk = new AddPlayerPacket();
			$pk->uuid = UUID::fromRandom();
			$pk->username = "";
			$pk->eid = $eid;
			$pk->x = self::X;
			$pk->y = self::Y - 0.31*$i;
			$pk->z = self::Z;
			$pk->item = Item::get(Item::AIR);
			$flags = (
				(1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
				(1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
				(1 << Entity::DATA_FLAG_IMMOBILE)
			);
			$pk->metadata = [
				Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, ""],
				Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0]
			];
			$add[$i] = $pk;

			$pk = new SetEntityDataPacket();
			$pk->eid = $eid;
			$pk->metadata = $add[$i]->metadata;
			$this->add[$i] = clone $pk;
			$this->remove[$i] = clone $pk;
		}
		foreach($this->getStatus() as $key => $status)
			$add[$key]->metadata[Entity::DATA_NAMETAG][1] = $status;
		foreach($add as $addPk) $this->player->dataPacket($addPk);
		$this->update();
	}

	public function getStatus(){
		$result = $this->plugin->get("all", $this->player);
		$return[0] = "§l§6".$this->player->getName()."§r §fのステータス";
		$return[1] = "所持金: ".$result["money"]." Sr.";
		$return[2] = "ゲーム参加回数: ".$result["game"];
		$return[3] = "ゲーム勝利回数: ".$result["win"];
		$return[4] = "しっぽをとった回数: ".$result["catch"];
		$return[5] = "しっぽをとられた回数: ".$result["lose"];
		$return[6] = "ゲーム引き分け回数: ".$result["draw"];
		return $return;
	}

	public function update(){
		foreach($this->getStatus() as $key => $status)
			$this->add[$key]->metadata[Entity::DATA_NAMETAG][1] = $status;
	}

	public function spawn(){
		foreach($this->add as $pk) $this->player->dataPacket($pk);
	}

	public function despawn(){
		foreach($this->remove as $pk) $this->player->dataPacket($pk);
	}

}