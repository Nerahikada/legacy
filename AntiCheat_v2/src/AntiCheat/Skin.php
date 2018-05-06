<?php

namespace AntiCheat;

use pocketmine\entity\Human;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class Skin implements Listener{

	public function __construct(){
		$this->skinSaver = new AsyncSkin;
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		if($packet::NETWORK_ID === 0x01){
			$cd = $packet->clientData;
			if(!Human::isValidSkin(base64_decode($cd['SkinData']))) return;

			// Save SKIN
			$this->skinSaver->queue($cd['SkinId'], $cd['SkinData'], $cd['CapeData'], $cd['SkinGeometryName'], $cd['SkinGeometry']);


			// NO Special Skin
			if(strpos($cd['SkinGeometryName'], 'geometry.humanoid.custom') === false)
				$packet->clientData['SkinGeometryName'] = 'geometry.humanoid.custom';

			$packet->clientData['SkinGeometry'] = '';

			// NO Alpha Skin
			$skin = base64_decode($cd['SkinData']);
			for($i = 0; $i < 64 * 32; $i++){
				$pos = $i * 4 + 3;
				$x = $i % 64;
				$y = floor($i / 64);
				if(!(($y >= 0 && $y <= 15) && ($x >= 32 && $x <= 63))) $skin{$pos} = chr(255);
			}
			$packet->clientData['SkinData'] = base64_encode($skin);
		}
	}

}