<?php

declare(strict_types=1);

namespace AntiCheat;

class CallbackTask extends \pocketmine\scheduler\Task{

	protected $callable;

	protected $args;

	public function __construct(callable $callable, array $args = []){
		$this->callable = $callable;
		$this->args = $args;
	}

	public function getCallable() : callable{
		return $this->callable;
	}

	public function onRun(int $currentTicks){
		$callable = $this->callable;
		$args = $this->args;
		$callable(...$args);
	}

}