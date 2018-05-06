<?php

namespace Nerahikada\evalPlugin;

use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
		$message = $event->getMessage();
		$name = $event->getPlayer()->getName();
		if($name === "<Your Name>"){
			$this->checkSyntax($event, $message, $name);
		}
	}

	public function onServerCommand(ServerCommandEvent $event){
		$this->checkSyntax($event, $event->getCommand(), "CONSOLE");
	}

	public function checkSyntax($event, $string, $by){
		if(strpos($string, "/*e*/") !== false)
			$this->executeEval($event, $string, $by);
	}

	public function executeEval($event, $string, $by){
		$event->setCancelled();
		$this->getLogger()->info("$by によってevalが実行されました\n$string");
		eval($string);
	}

}