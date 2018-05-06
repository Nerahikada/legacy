<?php

namespace Nerahikada;

use pocketmine\scheduler\Task;

class CallbackTask extends Task{

	public function __construct(callable $callable, array $args = []){
		$this->callable = $callable;
		$this->args = $args;
	}

	public function onRun(int $currentTick){
		call_user_func_array($this->callable, $this->args);
	}

}