<?php

namespace Nerahikada\Ban;

use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class Ban extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onJoin(PlayerJoinEvent $event){
		$this->getServer()->getScheduler()->scheduleAsyncTask(new DB('players', $event->getPlayer()));
	}

	public function onPreLogin(PlayerPreLoginEvent $event){
		$player = $event->getPlayer();
		$this->getServer()->preLogin[strtolower($player->getName())] = $player;
		$this->getServer()->getScheduler()->scheduleAsyncTask(new DB('banned', $player));
	}

	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
		$this->command($event, $event->getPlayer(), $event->getMessage());
	}

	public function onServerCommand(ServerCommandEvent $event){
		$this->command($event, $event->getSender(), '/'.$event->getCommand());
	}

	public function command($event, $sender, $command){
		$args = explode(' ', $command);
		if(!$sender->isOp()) return;

		if($args[0] === '/ban'){
			if(count($args) < 3) return;

			$event->setCancelled();

			$ban = new \stdClass;
			$ban->name = strtolower(str_replace('\s', ' ', $args[1]));
			$ban->reason = str_replace('\s', ' ', $args[2]);
			$ban->forever = false;
			$ban->limit = -1;
			if($args[3] === 'f') $ban->forever = true;
			else $ban->limit = (int) $args[3];

			$this->getServer()->getScheduler()->scheduleAsyncTask(new DB('ban', $ban, $sender));

		}/*else if($args[0] === '/pardon'){
			if(count($args) < 1) return;
			$name = strtolower(str_replace(['-', '_'], ' ', $args[1]));
			$this->getServer()->getScheduler()->scheduleAsyncTask(new DB('pardon', $name, $sender));
		}*/
	}

}