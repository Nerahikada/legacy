<?php

namespace Nerahikada;

use pocketmine\scheduler\Task;

class TickTask extends Task{

	public function __construct($owner){
		$this->owner = $owner;
	}

	public function onRun(int $currentTick){
		$this->owner->tick();
	}

}