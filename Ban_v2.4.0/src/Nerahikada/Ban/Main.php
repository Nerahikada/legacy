<?php

namespace Nerahikada\Ban;

use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\server\ServerCommandEvent;
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
			$event->setJoinMessage("");
			self::move($player, $player->banned);
		}else{
			$this->getServer()->getScheduler()->scheduleAsyncTask( new DB("onJoin", new Ban($event->getPlayer()) ));
		}
	}



	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
		$this->command($event, $event->getPlayer(), $event->getMessage());
	}

	public function onServerCommand(ServerCommandEvent $event){
		$this->command($event, $event->getSender(), '/'.$event->getCommand());
	}

	public function command($event, $sender, $command){
		if(!$sender->isOp()) return;

		$args = array_map("stripslashes", str_getcsv($command, " "));
		$label = array_shift($args);

		if($label === "/ban"){
			$event->setCancelled();

			if(count($args) < 3){ // name & reason & limit
				$sender->sendMessage("§cパラメーターが足りません");
				return;
			}

			$ban = new Ban;
			$ban->name = array_shift($args);
			$ban->reason = array_shift($args);
			$time = strtolower(array_shift($args));
			if($time === "f" || $time === "forever"){
				$ban->forever = true;
			}else if(is_numeric($time)){
				$ban->limit = (int) $time;
			}else{
				$check = preg_match('/[^0-9\+\-\*\/ ]/', $time);
				if($check === 1){
					$sender->sendMessage("§c使用できない文字が含まれています");
					return;
				}
				try{
					eval('$total = '.$time.';');
					$ban->limit = (int) $total;
				}catch(Error $e){
					$sender->sendMessage("§c計算に失敗しました");
					return;
				}
			}
			$ban->by = $sender->getName();

			$this->getServer()->getScheduler()->scheduleAsyncTask(new DB("ban", $ban));
		}else if($label === "/banlist"){
			$event->setCancelled();
			$this->getServer()->getScheduler()->scheduleAsyncTask(new DB("banlist", $sender->getName()));
		}
	}



	public static function getLimitTime($ban){
		if($ban->forever){
			$message = '§c無期限BAN§f';
		}else{
			$limit = $ban->limit - (time() - $ban->time);

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

	public static function move($player, $ban){
		$player->transfer('uncharted.ml', 50000);
		$player->kick(
			'§l§4BANされています§r: §f'.self::getLimitTime($ban)."\n".
			'§7理由: §f'.$ban->reason."\n".
			'§7Ban ID: §f'.$ban->id
		, false);
	}

}