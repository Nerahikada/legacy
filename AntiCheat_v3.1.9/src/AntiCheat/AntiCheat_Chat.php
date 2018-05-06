<?php

namespace AntiCheat;

use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\Listener;

class AntiCheat_Chat extends Base implements Listener{

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
				$target = $this->getServer()->getPlayer(array_shift($args));
				if($target !== null && $player !== $target){
					$this->getLogger()->info('['.$player->getDisplayName().' -> '.$target->getName().'] ' . implode(' ', $args));
				}
			}
		}


		// No SPAM
		$player->commandTime = microtime(true);

		if(!isset($player->spamCount)){
			$player->fastCount = 0;
			$player->spamCount = 0;
		}

		if(microtime(true) - $player->commandTime < 4){
			$player->fastCount++;
			$this->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, 'holdOn'], [$player]), 20 * 4);
		}

		if($player->fastCount >= 5){
			$event->setCancelled();
			$player->sendMessage('§l§4> ちょっと待って！ §r§cチャットの投稿速度が速すぎます！');
			$this->getLogger()->info('[SPAM] '.$player->getDisplayName().'> '.$message);

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