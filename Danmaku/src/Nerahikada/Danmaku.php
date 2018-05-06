<?php

namespace Nerahikada;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class Danmaku extends PluginBase implements Listener{

	public $enemies = [];

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onJoin(PlayerJoinEvent $event){
		if(!isset($this->enemy)){
			$v = $event->getPlayer()->asVector3();
			$v->y += 1.5;
			//$this->enemy = new Enemy($v);
			$this->enemy = new Enemy2($v);
		}
		//$this->enemies[] = new Enemy($event->getPlayer()->asVector3());
	}

}