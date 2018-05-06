<?php

namespace Nerahikada\Banned;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

	public static $players = [];


	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}


	public function onLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		self::$players[$player->getXuid()] = $player;
		$this->getServer()->getScheduler()->scheduleAsyncTask( new DB("isBanned", new Ban($player) ));
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		if(isset($player->banned)){
			$ban = $player->banned;
			$player->sendMessage(
				"§f--------------------------\n".
				'§l§4BANされています§r: §f'.self::getLimitTime($ban)."\n".
				'§7理由: §f'.$ban->reason."\n".
				'§7Ban ID: §f'.$ban->id."\n".
				"§f--------------------------");
		}else{
			$player->sendMessage("§a========================");
			$player->sendMessage("§aあなたはBANされていません");
			$player->sendMessage("§a========================");
		}
	}


	public static function getLimitTime($ban){
		if($ban->forever){
			$message = '§c無期限BAN§f';
		}else{
			$limit = $limit = $ban->limit - (time() - $ban->time);

			$day = floor($limit / 86400);
			$hour = floor(($limit / 3600) % 24);
			$min = floor(($limit / 60) % 60);
			$sec = $limit % 60;

			$date = [[$day, '!日'], [$hour, '!時間'], [$min, '!分'], [$sec, '!秒']];
			foreach($date as $key => $value) if($value[0] == 0) unset($date[$key]); else break;
			$message = "";
			foreach($date as $value) $message .= str_replace('!', $value[0], $value[1]).' ';
		}
		return $message;
	}

}