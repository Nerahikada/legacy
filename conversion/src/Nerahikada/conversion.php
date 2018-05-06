<?php

namespace Nerahikada;

class conversion extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{

	public $cc = 0;

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->blocks = [
			33 => 5, //ピストン
			84 => 0,
			95 => 241,
			125 => 157,
			126 => 158,
			157 => 126,
			158 => 125,
			166 => 95,
			176 => 0,
			177 => 0,
			188 => 85,
			189	=> 85,
			190	=> 85,
			191	=> 85,
			192	=> 85,
			198 => 208,
			//199 => 0,
			208 => 198,
			// テラコッタだっけ？ そこら辺には対応してない
			210 => 188,//...
			211 => 189,//...2
			212 => 207,
			218 => 251,
		];
		$this->metas = [
			33 => 0,
			188 => 1,
			189	=> 2,
			190	=> 3,
			191	=> 5,
			192	=> 4,
		];

		$this->changed = [];
	}

	public function onChunkLoad(\pocketmine\event\level\ChunkLoadEvent $event){
		$this->cc++;
		echo "ChunkCount: ".$this->cc."\n";
		$chunk = $event->getChunk();
		foreach($chunk->getEntities() as $entity){
			if(!$entity instanceof \pocketmine\Player) $entity->close();
		}
		// 既に変更済みなのかを検出 ----------------------------------------------
		if(isset($this->changed[$chunk->getX()][$chunk->getZ()])) return;
		$this->changed[$chunk->getX()][$chunk->getZ()] = true;
		// --------------------------------------------------------------------
		for($x = 0; $x < 16; $x++){
			for($z = 0; $z < 16; $z++){
				for($y = 0; $y <= 256; $y++){
					$id = $chunk->getBlockId($x, $y, $z);
					if(isset($this->blocks[$id])){
						if(isset($this->metas[$id])){
							$meta = $this->metas[$id];
						}else{
							$meta = $chunk->getBlockData($x, $y, $z);
						}
						$chunk->setBlock($x, $y, $z, $this->blocks[$id], $meta);
					}
					//or
					if($id === 44 || $id === 43){
						switch($chunk->getBlockData($x, $y, $z)){
							case 6: $meta = 7; break;
							case 7: $meta = 6; break;
							case 14: $meta = 15; break;
							case 15: $meta = 14; break;
							default: $meta = false; break;
						}
						if($meta !== false) $chunk->setBlock($x, $y, $z, $id, $meta);
					}else if($id === 96){
						switch($chunk->getBlockData($x, $y, $z)){
							case 0: $meta = 3; break;
							case 2: $meta = 1; break;
							case 1: $meta = 2; break;
							case 3: $meta = 0; break;
							case 4: $meta = 11; break;
							case 7: $meta = 8; break;
							case 5: $meta = 10; break;
							case 6: $meta = 9; break;
							case 8: $meta = 7; break;
							case 10: $meta = 5; break;
							case 9: $meta = 6; break;
							case 11: $meta = 4; break;
							case 12: $meta = 15; break;
							case 14: $meta = 13; break;
							case 13: $meta = 14; break;
							case 15: $meta = 12; break;
							default: $meta = false; break;
						}
						if($meta !== false) $chunk->setBlock($x, $y, $z, $id, $meta);
					}else if($id === 77 || $id === 143){
						switch($chunk->getBlockData($x, $y, $z)){
							case 4: $meta = 2; break;
							case 5: $meta = 1; break;
							case 1: $meta = 5; break;
							case 2: $meta = 4; break;
							default: $meta = false; break;
						}
						if($meta !== false) $chunk->setBlock($x, $y, $z, $id, $meta);
					}
				}
			}
		}
	}

}