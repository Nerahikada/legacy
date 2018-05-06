<?php

namespace Nerahikada\Rank;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Skin;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\Listener;
use pocketmine\lang\TranslationContainer;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

	public static $players = [];

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		Rank::init();
	}


	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!$sender->isOp()) return true;

		$sub = array_shift($args);
		if($sub === "set"){
			if(count($args) < 2){
				$sender->sendMessage("§cパラメーターが足りません");
				return true;
			}
			$name = array_shift($args);
			$rank = (int) array_shift($args);
			$this->getServer()->getScheduler()->scheduleAsyncTask(new DB("setRank", $name, $rank, $sender->getName()));
		}else if($sub === "list"){
			$this->getServer()->getScheduler()->scheduleAsyncTask(new DB("displayRankList", $sender->getName()));
		}
		return true;
	}



	public function onLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		$xuid = $player->getXuid();
		self::$players[$xuid] = $player;
		$this->getServer()->getScheduler()->scheduleAsyncTask(new DB("getRank", $xuid));
	}

	public function onJoin(PlayerJoinEvent $event){
		if(empty($event->getJoinMessage())) return;

		$player = $event->getPlayer();
		$rank = $player->rank;

		$message = new TranslationContainer("§e%multiplayer.player.joined", [$player->getDisplayName()."§e"]);
		if($rank->isDisplayMessage()){
			$event->setJoinMessage($message);
		}else{
			$event->setJoinMessage("");
			$this->getServer()->getLogger()->info($this->getServer()->getLanguage()->translate($message));
		}

		$cape = $rank->getCape();
		if($cape !== null){
			$skin = $player->getSkin();
			$newSkin = new Skin($skin->getSkinId(), $skin->getSkinData(), base64_decode($cape));
			$player->setSkin($newSkin);
			$player->sendSkin($this->getServer()->getOnlinePlayers());
		}
	}

	public function onQuit(PlayerQuitEvent $event){
		if(empty($event->getQuitMessage())) return;

		$player = $event->getPlayer();
		$message = new TranslationContainer("§e%multiplayer.player.left", [$player->getDisplayName()."§e"]);
		if($player->rank->isDisplayMessage()){
			$event->setQuitMessage($message);
		}else{
			$event->setQuitMessage("");
			$this->getServer()->getLogger()->info($this->getServer()->getLanguage()->translate($message));
		}
	}

}