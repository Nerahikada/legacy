<?php

namespace Nerahikada;

use pocketmine\math\Vector3;
use pocketmine\Server;

use Nerahikada\bullet\Bullet2;

class Enemy2 extends Vector3{

	public $tickCounter = 0;
	public $t1 = 0;
	public $t2 = 0;

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
		Server::getInstance()->getScheduler()->scheduleRepeatingTask(new TickTask($this), 1);
	}

	public function tick(){
		$this->tickCounter++;

		if($this->tickCounter % 2 === 0){
			$this->t1 += 0.0005;
			if($this->t1 > M_PI) $this->t1 -= M_PI * 2;
			$this->t2 += $this->t1;
			if($this->t2 > M_PI) $this->t2 -= M_PI * 2;
			$g = 5;
			for($i = 1; $i <= $g; $i++){
				$t = M_PI * 2 * $i / $g + $this->t2;
				new Bullet2($this->x, $this->y, $this->z, cos($t), 0, sin($t));
			}
		}
	}

}