<?php

namespace Nerahikada;

use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\NBT;
use pocketmine\tile\Tile;

class Sign{

	const X = 346;
	const Y = 41;
	const Z = -878;

	const TITLE = "§l< しっぽ取りゲーム >";
	const SUBTITLE = ["§l§4未エントリー", "§l§aエントリー済み", "§l§6エントリー締め切り"];
	const OPEN = 0;
	const JOINED = 1;
	const CLOSE = 2;

	public $server;
	public $sign = [];


	public function __construct($plugin){
		$this->server = $plugin->server;
		foreach(self::SUBTITLE as $key => $subtitle){
			$tag = new CompoundTag("", [
				new StringTag("id", Tile::SIGN),
				new StringTag("Text1", ""),
				new StringTag("Text2", self::TITLE),
				new StringTag("Text3", $subtitle),
				new StringTag("Text4", ""),
				new IntTag("x", self::X),
				new IntTag("y", self::Y),
				new IntTag("z", self::Z)
			]);
			$nbt = new NBT(NBT::LITTLE_ENDIAN);
			$nbt->setData($tag);
			$pk = new BlockEntityDataPacket();
			$pk->x = self::X;
			$pk->y = self::Y;
			$pk->z = self::Z;
			$pk->namedtag = $nbt->write(true);
			$this->sign[$key] = $pk;
		}
	}

	public function send($player, $key){
		$player->dataPacket($this->sign[$key]);
	}

	public function sendS($players, $key){
		$this->server->broadcastPacket($players, $this->sign[$key]);
	}

	public function sendAll($key){
		$this->server->broadcastPacket($this->server->getOnlinePlayers(), $this->sign[$key]);
	}

	public function isSign($block){
		if($block->x === self::X &&
			$block->y === self::Y &&
			$block->z === self::Z) return true;
		return false;
	}

}