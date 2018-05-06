<?php

namespace Nerahikada;

use pocketmine\math\Vector3;
use pocketmine\Server;

use Nerahikada\bullet\FastBullet;
use Nerahikada\bullet\WallBullet;

class Enemy extends Vector3{

	public static $enemyCount = 1;


	public $id;
	public $tickCounter = 0;

	public function __construct($x, $y = null, $z = null){
		if($x instanceof Vector3){
			$this->x = $x->x;
			$this->y = $x->y;
			$this->z = $x->z;
		}else{
			$this->x = $x;
			$this->y = $y;
			$this->z = $z;
		}
		$this->id = Enemy::$enemyCount++;
		Server::getInstance()->getScheduler()->scheduleRepeatingTask(new TickTask($this), 1);
	}

	public function tick(){
		$this->tickCounter++;


		// Search Target
		$found = null;
		$delta = PHP_INT_MAX;
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			$curDelta = ($player->x - $this->x)**2 + ($player->y + $player->getEyeHeight() - $this->y)**2 + ($player->z - $this->z)**2;
			if($curDelta < $delta){
				$found = $player;
				$delta = $curDelta;
			}
		}
		if($found === null) return;

		// Convert to Vector
		$unknown = atan2($found->x - $this->x, $found->z - $this->z) * -1; //yaw?
		$unknown2 = atan2(($found->y + $found->getEyeHeight() - $this->y)**2, ($found->x - $this->x)**2 + ($found->z - $this->z)**2) * -1; //pitch?

		$motionX = -sin($unknown) * cos($unknown2);
		$motionY = -sin($unknown2);
		$motionZ = cos($unknown) * cos($unknown2);

		if($delta >= 6**2){
			$this->x += $motionX;
			$this->y += $motionY;
			$this->z += $motionZ;
		}

		if($this->tickCounter % 2 === 0){
			for($i = -1; $i <= 1; $i+=2){
				$tu = $unknown;
				$tu += deg2rad(mt_rand(20, 40)) * $i;
				$tmotionX = -sin($tu) * cos($unknown2);
				$tmotionZ = cos($tu) * cos($unknown2);
				new WallBullet($this->x, $this->y, $this->z, $tmotionX, $motionY, $tmotionZ);
			}
		}
		if($this->tickCounter % 20 === 0){
			new FastBullet($this->x, $this->y, $this->z, $motionX, $motionY, $motionZ);
		}
	}

}