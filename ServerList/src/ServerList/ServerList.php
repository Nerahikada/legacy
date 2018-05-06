<?php

namespace ServerList;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class ServerList extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->ServerListAPI = new ServerListAPI($this->getServer()->getMaxPlayers());
	}

	public function onDisable(){
		$this->ServerListAPI->logout();
	}

	public function onJoin(PlayerJoinEvent $event){
		$this->update('join');
	}

	public function onQuit(PlayerQuitEvent $event){
		if($event->getPlayer()->loggedIn) $this->update('quit');
	}

	public function update($type){
		$count = count($this->getServer()->getOnlinePlayers());
		$count -= ($type === 'quit') ? 1 : 0;
		$this->ServerListAPI->update($type, $count);
	}

}