<?php

namespace Nerahikada\RobotArm;

use pocketmine\block\BlockIds;
use pocketmine\entity\Item as EntityItem;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\Server;

class Arm{

	const STRAIGHT = 0.64;
	const CURVE = 0.48;

	public $x;
	public $y;
	public $z;

	public $time = 0;

	public $parts = [];


	public $motionVector3;


	public function __construct($vector3){
		$this->motionVector3 = new Vector3(0, 0, -0.1);

		$this->x = intval($vector3->x);
		$this->y = intval($vector3->y) + 0.5;
		$this->z = intval($vector3->z);

		for($i = 0; $i < 16; $i++){
			$this->parts[$i] = new Part($this, $i);
		}
		$this->parts[0]->yaw = 90;
		$this->parts[0]->pitch = -90;

		$this->spawn();


		Server::getInstance()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, "onTick"], []), 1);
	}


	public function onTick(){
		$this->time++;

		if(1 <= $this->time && $this->time <= 10){
			$this->parts[0]->pitch += 2.6;
		}
		if(11 <= $this->time && $this->time <= 20){
			$this->parts[0]->pitch -= 2.6;
		}
		if($this->time === 20){
			$this->parts[0]->yaw += 180;
		}
		if(22 <= $this->time && $this->time <= 31){
			$this->parts[0]->pitch += 4;
		}
		if(32 <= $this->time && $this->time <= 41){
			$this->parts[0]->pitch -= 4;
		}
		if($this->time === 41){
			$this->parts[0]->yaw -= 180;
		}


		for($i = 1; $i < 4; $i++){
			$this->parts[$i]->move(self::STRAIGHT);
		}

		$this->parts[4]->move(self::CURVE);
		$this->parts[4]->yaw = 90;
		$this->parts[4]->pitch += 65;

		if(22 <= $this->time && $this->time <= 31){
			$this->parts[4]->pitch -= 20;
		}
		if(32 <= $this->time && $this->time <= 41){
			$this->parts[4]->x = $this->parts[4]->x;
			$this->parts[4]->y = $this->parts[4]->y;
			$this->parts[4]->z = $this->parts[4]->z;
			$this->parts[4]->yaw = $this->parts[4]->yaw;
			$this->parts[4]->pitch = $this->parts[4]->pitch;
		}


		for($i = 5; $i < 10; $i++){
			$this->parts[$i]->move(self::STRAIGHT);
		}

		$this->parts[10]->move(self::CURVE);
		$this->parts[10]->pitch += 75;

		if(22 <= $this->time && $this->time <= 31){
			$this->parts[10]->pitch += 10;
		}
		if(32 <= $this->time && $this->time <= 41){
			$this->parts[10]->x = $this->parts[10]->x;
			$this->parts[10]->y = $this->parts[10]->y;
			$this->parts[10]->z = $this->parts[10]->z;
			$this->parts[10]->yaw = $this->parts[10]->yaw;
			$this->parts[10]->pitch = $this->parts[10]->pitch;
		}


		for($i = 11; $i < 13; $i++){
			$this->parts[$i]->move(self::STRAIGHT);
		}

		$this->parts[13]->move(self::CURVE);
		$this->parts[13]->pitch += 40;

		$this->parts[14]->move(self::STRAIGHT);

		$this->parts[15]->move(self::STRAIGHT);


		$this->update();



		$entities = Server::getInstance()->getDefaultLevel()->getEntities();
		foreach($entities as $entity){
			if($entity instanceof EntityItem){
				$item = $entity->getItem();

				if($item->getId() === ItemIds::WOOL && $item->getDamage() === 4){
					if(!isset($entity->catch)){
						$level = $entity->getLevel();
						$block = $level->getBlockAt($entity->x, $entity->y - 0.1, $entity->z, true, false);
						if($block->getId() === BlockIds::MAGENTA_GLAZED_TERRACOTTA){
							$entity->setMotion($this->motionVector3);
						}
					}
					if(!isset($entity->reserved)){
						$armHand = $this->parts[15];
						$distance = ($entity->x - $armHand->x)**2 + ($entity->y - $armHand->y)**2 + ($entity->z - $armHand->z)**2;

						if($this->time > 41 && $distance <= 1){
							$this->time = 0;
							$entity->reserved = true;
						}
					}
				}
			}
		}
	}



	public function spawn(){
		foreach($this->parts as $part){
			$part->spawn();
		}
	}

	public function update(){
		foreach($this->parts as $part){
			$part->update();
		}
	}


}