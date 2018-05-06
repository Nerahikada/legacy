<?php

namespace AntiCheat;

use pocketmine\Server;

class Base{

	public function getServer(){
		return Server::getInstance();
	}

	public function getLogger(){
		return $this->getServer()->getLogger();
	}

	public function getScheduler(){
		return $this->getServer()->getScheduler();
	}

	public function delayedTask(callable $c, array $args, int $tick){
		$this->getScheduler()->scheduleDelayedTask(new CallbackTask($c, $args), $tick);
	}


	public function checkTick($player){
		$tick = $this->getServer()->getTick();
		if($player->AntiCheatTick === $tick){
			++$player->AntiCheatTickCount;
			if($player->AntiCheatTickCount >= 3){
				return false;
			}
		}else{
			$player->AntiCheatTick = 0;
		}
		return true;
	}


	public function ban($player, $reason, $late = false){
		if(!$late){
			$this->delayedTask([$this, 'ban'], [$player, $reason, true], 1);
			return;
		}
		$this->getLogger()->info('§4'.$player->getName().': '.$reason);
		$player->kick('§l§4チートの使用が検出されました');
		/*
		$this->getServer()->getNameBans()->addBan(
			$player->getName(),
			$reason,
			(new \DateTime())->modify('+30 days'),
			'AntiCheat'
		);
		*/
	}

}