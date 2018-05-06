<?php

namespace Nerahikada;

use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\player\PlayerAchievementAwardedEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class NoExtraFeatures extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onSpread(BlockSpreadEvent $event){
		$event->setCancelled();
	}

	public function onLeavesDecay(LeavesDecayEvent $event){
		$event->setCancelled();
	}

	public function onAA(PlayerAchievementAwardedEvent $event){
		$event->setCancelled();
	}

}