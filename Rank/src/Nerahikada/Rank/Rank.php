<?php

namespace Nerahikada\Rank;

use pocketmine\Player;

class Rank{

	const BASE = 0;
	const OWNER = 1;
	const SPONSOR = 2;
	const ADMIN = 3;
	const YT1 = 4;		//最高
	const YT2 = 5;
	const YT3 = 6;
	const YT4 = 7;		//最低

	private static $list = null;

	public static function init(){
		self::$list = new \SplFixedArray(8);

		self::registerRank(new Base(self::BASE));
		self::registerRank(new Owner(self::OWNER, "§l§aOwner"));
		self::registerRank(new Sponsor(self::SPONSOR, "§l§bSponsor"));
		self::registerRank(new Admin(self::ADMIN, "§l§cAdmin"));
		self::registerRank(new YT1(self::YT1, "§l§6YT"));
		self::registerRank(new YT2(self::YT2, "§6YT"));
		self::registerRank(new YT3(self::YT3, "§l§7YT"));
		self::registerRank(new YT4(self::YT4, "§7YT"));
	}

	public static function registerRank(Base $rank){
		$id = $rank->getId();
		self::$list[$id] = $rank;
	}

	public static function get(int $id){
		try{
			return self::$list[$id];
		}catch(\RuntimeException $e){
			throw new \InvalidArgumentException("Rank ID $id is invalid or out of bounds");
		}
	}


	public static function setPlayerRank(Player $player, int $id){
		$rank = self::get($id);
		$player->rank = $rank;
		$tag = $rank->getTag();
		if($tag !== null){
			$name = $tag."§r §f".$player->getName();
			$player->setNameTag($name);
			$player->setDisplayName($name);
		}
	}

}