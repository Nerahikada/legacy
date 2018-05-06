<?php

namespace Nerahikada;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class Face extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();

		$skin = substr($player->getSkinData(), ($pos = (64 * 8 * 4)) - 4, $pos);
		$image = imagecreatetruecolor(8, 8);
		for($y = 0; $y < 8; ++$y){
			for($x = 1; $x < 9; ++$x){
				$key = ((64 * $y) + 8 + $x) * 4;
				$r = ord($skin{$key});
				$g = ord($skin{$key + 1});
				$b = ord($skin{$key + 2});
				/*$a = ord($skin{$key + 3});*/
				$color = imagecolorallocate/*alpha*/($image, $r, $g, $b/*, $a*/);
				imageline($image, $x-1, $y, $x-1, $y, $color);
			}
		}

		//被せ物
		$skin = substr($player->getSkinData(), ($pos = (64 * 8 * 4)) + 128, $pos);
		for($y = 0; $y < 8; ++$y){
			for($x = 0; $x < 8; ++$x){
				$key = ((64 * $y) + 8 + $x) * 4;
				$r = ord($skin{$key});
				$g = ord($skin{$key + 1});
				$b = ord($skin{$key + 2});
				$a = ord($skin{$key + 3});
				$a = floor(abs($a - 255) / 2);
				$color = imagecolorallocatealpha($image, $r, $g, $b, $a);
				imageline($image, $x, $y, $x, $y, $color);
			}
		}

		if(!file_exists('face')) mkdir('face');
		imagepng($image, 'face/'.strtolower($player->getName()).'.png');
		imagedestroy($image);
	}

}