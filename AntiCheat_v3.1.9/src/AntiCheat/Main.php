<?php

namespace AntiCheat;

use pocketmine\plugin\PluginBase;

class Main extends PluginBase{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents(new AntiCheat_Player, $this);
		$this->getServer()->getPluginManager()->registerEvents(new AntiCheat_Chat, $this);
		$this->getServer()->getPluginManager()->registerEvents(new AntiCheat_Combat, $this);
		$this->getServer()->getPluginManager()->registerEvents(new AntiCheat_Move, $this);
	}

}