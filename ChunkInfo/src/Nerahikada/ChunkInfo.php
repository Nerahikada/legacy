<?php

namespace Nerahikada;

use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class ChunkInfo extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		$chunk = $player->getLevel()->getChunk($player->x >> 4, $player->z >> 4);
		$player->sendTip($chunk->getX()." : ".$chunk->getZ());
	}

}