<?php

namespace Nerahikada;

use pocketmine\utils\TextFormat;

class Title{

	public function __construct($plugin, $player, $title, $subtitle = ""){
		$this->sendTitle($player, $title, $subtitle, $plugin->scheduler);
	}

	public function sendTitle($player, $title, $subtitle, $scheduler, $tick = 2, $index = 0){
		$msg = mb_substr(TextFormat::clean($title), 0, $index);
		$player->addTitle("§l".$msg, "", 0, 40, 15);
		if($index >= mb_strlen($title)){
			$player->addTitle("§l".$title, $subtitle, 0, 60, 15);
			return;
		}
		$index++;
		$scheduler->scheduleDelayedTask(new CallbackTask([$this, "sendTitle"], [$player, $title, $subtitle, $scheduler, $tick, $index]), $tick);
	}

}