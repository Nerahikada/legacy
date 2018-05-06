<?php

namespace AntiCheat;

use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\event\Listener;
use pocketmine\Player;

class AntiCheat_Move extends Base implements Listener{

	public function onLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		$player->teleporting = false;
		$player->ignoreMove = false;
		$player->ignoreMove2 = false;
		$player->immobileCount = 0;
	}

	public function onTeleport(EntityTeleportEvent $event){
		$entity = $event->getEntity();
		if($entity instanceof Player){
			$player = $entity;
			$player->teleporting = true;
		}
	}

	public function onMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		if(!$this->checkTick($player)) return;

		if($player->teleporting){
			$player->teleporting = false;
			$player->ignoreMove = true;
			return;
		}

		if($player->ignoreMove){
			$player->ignoreMove = false;
			$player->ignoreMove2 = true;
			return;
		}

		//wtf
		if($player->ignoreMove2){
			$player->ignoreMove2 = false;
			return;
		}

		if($player->isImmobile() && !$event->getFrom()->asVector3()->equals($event->getTo())){
			$player->immobileCount++;
			if($player->immobileCount >= 2) $this->ban($player, 'immobile');
			$this->delayedTask(function($player){$player->immobileCount--;}, [$player], 40);
		}
	}



	// IT'S NOT PERFECT!!!
	public function onFly(PlayerToggleFlightEvent $event){
		$player = $event->getPlayer();
		if(!$player->getAllowFlight()) $this->ban($player, 'Flying(Event)');
	}

}