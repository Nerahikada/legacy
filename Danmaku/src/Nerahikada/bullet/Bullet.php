<?php

namespace Nerahikada\bullet;

use pocketmine\level\particle\FlameParticle;
use pocketmine\math\Vector3;
use pocketmine\Server;

use Nerahikada\TickTask;

class Bullet{

	public $x;
	public $y;
	public $z;
	public $motionX;
	public $motionY;
	public $motionZ;
	public $task;

	public $particleVector;

	const CAN_ALIVE = 20;
	const SPEED = 0.5;
	public $tickCounter = 0;

	public function __construct($x, $y, $z, $motionX, $motionY, $motionZ){
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		$this->motionX = $motionX;
		$this->motionY = $motionY;
		$this->motionZ = $motionZ;
		$this->task = new TickTask($this);
		$this->particleVector = new Vector3();
		Server::getInstance()->getScheduler()->scheduleRepeatingTask($this->task, 1);
	}

	public function tick(){
		$this->tickCounter++;

		// issue of Level...
		$level = Server::getInstance()->getDefaultLevel();
		$vector3 = $this->particleVector->setComponents($this->x, $this->y, $this->z);
		$level->addParticle(new FlameParticle($vector3));

		// MOVE!
		$this->x += $this->motionX * self::SPEED;
		$this->y += $this->motionY * self::SPEED;
		$this->z += $this->motionZ * self::SPEED;

		if($this->tickCounter >= self::CAN_ALIVE){
			Server::getInstance()->getScheduler()->cancelTask($this->task->getTaskId());
		}
	}

}