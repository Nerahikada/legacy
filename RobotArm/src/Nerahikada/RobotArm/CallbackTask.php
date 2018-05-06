<?php

declare(strict_types=1);

namespace Nerahikada\RobotArm;

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
		$c = $this->callable;
		$args = $this->args;
		$c(...$args);
	}

}