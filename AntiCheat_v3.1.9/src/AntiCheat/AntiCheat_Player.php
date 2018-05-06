<?php

namespace AntiCheat;

use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Listener;

class AntiCheat_Player extends Base implements Listener{

	public function onLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		$player->AntiCheatTick = $this->getServer()->getTick();
		$player->AntiCheatTickCount = 0;
	}

	public function onReceive(DataPacketReceiveEvent $event){
		$pk = $event->getPacket();
		if($pk::NETWORK_ID === 0x01){
			if(strpos($pk->serverAddress, 'mcpeproxy.tk') !== false){
				$player = $event->getPlayer();
				$this->getServer()->getLogger()->info("§4PROXY: §b".$pk->username);
				$player->close('', '§l§4DO NOT USE §bMCPE Proxy§4!!!');
				$event->setCancelled();
			}
		}
	}

}