<?php

namespace AntiCheat;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class AntiCheat extends PluginBase{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents(new Chat, $this);
		$this->getServer()->getPluginManager()->registerEvents(new Skin, $this);
	}

}