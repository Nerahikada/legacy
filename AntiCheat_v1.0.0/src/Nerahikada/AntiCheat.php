<?php

namespace Nerahikada;

use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\cheat\PlayerIllegalMoveEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class AntiCheat extends PluginBase implements Listener{

	public function onEnable(){
		$this->Server = Server::getInstance();
		$this->Server->getPluginManager()->registerEvents($this, $this);
		$this->pluginLogger = $this->getLogger();
	}

	public function onIllegalMove(PlayerIllegalMoveEvent $event){
		$player = $event->getPlayer();
		if(!isset($player->imc)) $player->imc = 0;
		$player->imc++;
		$this->Server->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, 'minusIMC'], [$player]), 20 * 30);
	}

	public function minusIMC($player){
		$player->imc--;
	}

	public function onMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		if(isset($player->imc) && $player->imc > 3) return;
		$Y = 0.2;
		$pos = new Vector3($player->x, $player->y - $Y, $player->z);
		$level = $player->getLevel();
		$block = $level->getBlock($pos);
		$bn = $block->getName();
		if(
			$player->isCreative() ||
			!isset($player->beforePos) ||
			$player->beforePos->y > $player->y ||
			$block->isSolid() ||
			$bn === "Water" || $bn === "Still Water" ||
			$bn === "Ladder" || $bn === "Vines" || $bn === "Snow Layer"
		){
			$pos->y += $Y;
			$player->beforePos = $pos;
			$player->moveY = 0;
			return;
		}
		$pos->y += $Y;
		$move = $player->y - $player->beforePos->y;
		if($move > 2) return;
		$player->moveY += $move;
		$player->beforePos = $pos;
		if($player->moveY > 2.5){
			for($x = -1; $x <= 1; $x++){
				for($z = -1; $z <= 1; $z++){
					if($x === 0 && $z === 0) continue;
					$temp = clone $pos;
					$temp->x += $x;
					$temp->z += $z;
					$bn = $level->getBlock($temp)->getName();
					if($bn === "Water" || $bn === "Still Water"){
						$pos->y += 0.1;
						$player->beforePos = $pos;
						$player->moveY = 0;
						return;
					}
				}
			}
			$this->Server->getScheduler()->scheduleDelayedTask(new CallbackTask([$player, 'kick'], ["§l§4飛行の使用が検出されました。", false]), 1);
		}
	}


	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
		if($event->isCancelled()) return;
		$player = $event->getPlayer();
		if(!isset($player->chatTime)){
			$player->chatTime = microtime(true);
			$player->chatCount = 0;
		}else{
			if((microtime(true) - $player->chatTime) < 1){
				//$event->setCancelled();
				$this->pluginLogger->info("§c".$player->getName().": ".$event->getMessage());
				//$player->sendMessage("§cチャットの連続投稿は出来ません。");
				$player->chatTime = microtime(true);
				$player->chatCount++;
				if($player->chatCount >= 3){
					$player->kick("§l§4スパム行為は禁止です");
					return;
				}
				$this->Server->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, 'minusChatCount'], [$player]), 20 * 3);
			}
			$player->chatTime = microtime(true);
		}
	}

	public function minusChatCount($player){
		$player->chatCount--;
	}

}