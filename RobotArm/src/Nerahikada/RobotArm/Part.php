<?php

namespace Nerahikada\RobotArm;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\utils\UUID;
use pocketmine\Server;

class Part{

	public $arm;
	public $id;
	public $x;
	public $y;
	public $z;
	public $yaw = 0;
	public $pitch = 0;

	public $uuid;
	public $eid;


	public function __construct($arm, $id){
		$this->arm = $arm;
		$this->id = $id;
		$this->x = $arm->x;
		$this->y = $arm->y;
		$this->z = $arm->z;
	}


	public function move($distance){
		$reference = $this->arm->parts[$this->id - 1];
		$y = -sin(deg2rad($reference->pitch));
		$xz = cos(deg2rad($reference->pitch));
		$x = -$xz * sin(deg2rad($reference->yaw));
		$z = $xz * cos(deg2rad($reference->yaw));

		$x *= $distance;
		$y *= $distance;
		$z *= $distance;

		$this->x = $reference->x + $x;
		$this->y = $reference->y + $y;
		$this->z = $reference->z + $z;

		$this->yaw = $reference->yaw;
		$this->pitch = $reference->pitch;
	}

	public function spawn(){
		$pk = new AddPlayerPacket;
		$pk->uuid = ($this->uuid = UUID::fromRandom());
		$pk->username = "";
		$pk->entityRuntimeId = ($this->eid = Entity::$entityCount++);
		$pk->position = new Vector3($this->x, $this->y, $this->z);
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->item = Item::get(0);
		$flags = (1 << Entity::DATA_FLAG_IMMOBILE | 1 << Entity::DATA_FLAG_INVISIBLE);
		$pk->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags]
		];
		$this->broadcastPacket($pk);

		$pk = new MobArmorEquipmentPacket;
		$pk->entityRuntimeId = $this->eid;
		if($this->id === 15){
			$item = Item::get(Item::MOB_HEAD);
		}else{
			$item = Item::get(Item::MOB_HEAD, 1);
		}
		$pk->slots[0] = $item;
		for($i = 1; $i < 4; $i++){
			$pk->slots[$i] = Item::get(0);
		}
		$this->broadcastPacket($pk);
	}

	public function update(){
		$pk = new MovePlayerPacket;
		$pk->entityRuntimeId = $this->eid;
		$pk->position = new Vector3($this->x, $this->y, $this->z);
		$pk->pitch = $this->pitch;
		$pk->yaw = $this->yaw;
		$pk->headYaw = $this->yaw;
		$this->broadcastPacket($pk);
	}

	public function broadcastPacket($pk){
		Server::getInstance()->broadcastPacket(Server::getInstance()->getOnlinePlayers(), $pk);
	}

}