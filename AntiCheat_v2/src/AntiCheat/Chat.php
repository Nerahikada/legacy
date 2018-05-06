<?php

namespace AntiCheat;

use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\CallbackTask;
use pocketmine\Server;

class Chat implements Listener{

	public function getScheduler(){
		return Server::getInstance()->getScheduler();
	}

	public function logInfo($msg){
		Server::getInstance()->getLogger()->info($msg);
	}
	

	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$message = $event->getMessage();

		// Tell check
		$args = explode(' ', $message);
		if(count($args) >= 3 && !empty($args[1]) && !empty($args[2])){
			$isTell = false;
			$tellCommands = ['tell', 'w', 'msg'];
			$command = array_shift($args);
			foreach($tellCommands as $tellCommand){
				if(strpos($command, '/'.$tellCommand) === 0){
					$isTell = true;
					break;
				}
			}

			if($isTell){
				$target = Server::getInstance()->getPlayer(array_shift($args));
				if($target !== null && $player !== $target){
					$this->logInfo('['.$player->getDisplayName().' -> '.$target->getName().'] ' . implode(' ', $args));
				}
			}
		}


		// No SPAM
		$player->commandTime = microtime(true);

		if(!isset($player->spamCount)){
			$player->fastCount = 0;
			$player->spamCount = 0;
		}

		if(microtime(true) - $player->commandTime < 1.5){
			$player->fastCount++;
			$this->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, 'holdOn'], [$player]), 20 * 2);
		}

		if($player->fastCount >= 3){
			$event->setCancelled();
			$player->sendMessage("§l§4> ちょっと待って！ §r§cチャットの頻度が速すぎます！");
			$this->logInfo('[SPAM] '.$player->getDisplayName().'> '.$message);

			$player->spamCount++;
			$this->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, 'spamCoolDown'], [$player]), 20 * 30);

			if($player->spamCount >= 3){
				$this->getScheduler()->scheduleDelayedTask(new CallbackTask([$player, 'kick'], ['§l§4スパムが検出されました', false]), 1);
			}
		}

	}

	public function holdOn($player){
		$player->fastCount--;
	}

	public function spamCoolDown($player){
		$player->spamCount--;
	}

}