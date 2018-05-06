<?php

namespace Nerahikada;

use pocketmine\entity\Entity;
use pocketmine\level\particle\HugeExplodeSeedParticle;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MoveEntityPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;

class Tail{

	public $server;
	public $plugin;
	public $player;
	public $eid;
	public $pos;
	public $yaw = 0;
	public $pitch = 0;
	public $removed = false;
	public $gameCount;

	public function __construct($plugin, $player){
		$this->server = $plugin->server;
		$this->plugin = $plugin;
		$this->player = $player;
		$this->eid = Entity::$entityCount++;
		$this->pos = new Vector3(0, 0, 0);
		$this->gameCount = $plugin->gameCount;
	}

	public function spawn(){
		if($this->plugin->gameCount !== $this->gameCount) return;

		foreach($this->plugin->players as $p) $this->player->showPlayer($p);

		$pk = new AddEntityPacket();
		$pk->eid = $this->eid;
		$pk->type = 39; // Silverfish

		$player = $this->player;
		$this->pos = new Vector3($player->x, $player->y, $player->z);
		$yaw = $player->yaw;
		$pitch = $player->pitch;
		$this->yaw = $yaw;
		$this->pitch = $pitch;

		$pk->x = $this->pos->x/* - -sin($yaw/180*M_PI)*cos($pitch/180*M_PI) * 0.2*/;
		$pk->y = $this->pos->y/* + 0.7*/;
		$pk->z = $this->pos->z/* - cos($yaw/180*M_PI)*cos($pitch/180*M_PI) * 0.2*/;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = [
			Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
			Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0.75]
		];

		$this->sendPacket($pk);
		$this->move();
	}

	public function move(){
		$player = $this->player;
		if(!$player->loggedIn) return $this->remove();
		$this->pos->x = $player->x;
		$this->pos->y = $player->y;
		$this->pos->z = $player->z;
		$yaw = $player->yaw;
		$pitch = $player->pitch;
		$this->yaw = $yaw;
		$this->pitch = $pitch;

		if($player->isSneaking()){
			$xz = 0.5; $y = 0.65;
		}else{
			$xz = 0.2; $y = 0.75;
		}

		$pk = new MoveEntityPacket();
		$pk->eid = $this->eid;
		$pk->x = $this->pos->x - -sin($yaw / 180 * M_PI) * $xz;
		$pk->y = $this->pos->y + $y;
		$pk->z = $this->pos->z - cos($yaw / 180 * M_PI) * $xz;
		$pk->yaw = $this->yaw;
		$pk->headYaw = $this->yaw;
		$pk->pitch = $this->pitch;

		$this->sendPacket($pk);
	}

	public function remove(){
		if(!$this->removed){
			$pk = new RemoveEntityPacket();
			$pk->eid = $this->eid;
			$this->sendPacket($pk);

			$this->removed = true;
		}
	}

	public function showNameTag(){
		if($this->removed) return;
		$pk = new SetEntityDataPacket();
		$pk->eid = $this->eid;
		$flags = ((1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) | (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG));
		$pk->metadata = [
			Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
			Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0.75],
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, "§l§6".$this->player->getName()."§f's Tail"]
		];
	}


	public function sendPacket($pk){
		if($pk instanceof AddEntityPacket){
			$temp = clone $pk;
			$temp->metadata[Entity::DATA_SCALE][1] = 0;
		}else if($pk instanceof MoveEntityPacket){
			$temp = clone $pk;
			$temp->y -= 3.5;
		}else if($pk instanceof SetEntityDataPacket){
			$temp = clone $pk;
			$pk->metadata = [
				Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
				Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0]
			];
		}
		foreach($this->plugin->players as $p){
			if($this->player !== $p) $p->dataPacket($pk);
			else if(isset($temp)) $p->dataPacket($temp); else $p->dataPacket($pk);
		}
	}


	public function preAttack($attacker, $d2){
		$a = $attacker;
		$p = $this->pos;
		$d = $d2 + ($a->y - $p->y) ** 2;
		//20.25
		if($d > 4.5**2) return false;

		$pos = new Vector3($a->x, $a->y + $a->getEyeHeight() + 0.03, $a->z);
		$yaw = atan2($p->x - $a->x, $p->z - $a->z) * 180 / M_PI * -1;
		$pitch = atan2($p->y - $a->y, sqrt($d2)) * 180 / M_PI * -1;
		$motion = new Vector3(
			-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI),
			-sin($pitch / 180 * M_PI),
			cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI)
		);
		if($this->isWall($pos, $motion)) return false;
		$this->explosion();
		return true;
	}

	public function isWall($pos, $motion){
		for($i = 0; $i < 150; $i++){
			$dis = ($pos->x - $this->pos->x)**2 + ($pos->y - $this->pos->y)**2 + ($pos->z - $this->pos->z)**2;
			if($dis < 2.6) return false;
			if($this->player->level->getBlock($pos)->isSolid()) return true;
			$pos->x += $motion->x / 10;
			$pos->y += $motion->y / 10;
			$pos->z += $motion->z / 10;
		}
		return false;
	}

	public function explosion(){
		$this->remove();
		$this->player->getLevel()->addParticle(new HugeExplodeSeedParticle($this->pos));
		$pk = new LevelSoundEventPacket();
		$pk->sound = LevelSoundEventPacket::SOUND_EXPLODE;
		$pk->x = $this->pos->x;
		$pk->y = $this->pos->y;
		$pk->z = $this->pos->z;
		$pk->extraData = 256;
		$pk->pitch = 1;
		$this->server->broadcastPacket($this->plugin->players, $pk);
	}

}