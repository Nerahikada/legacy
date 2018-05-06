<?php

namespace Nerahikada\SpecialTools;

use pocketmine\block\Block;
use pocketmine\item\Axe;
use pocketmine\item\Item;
use pocketmine\item\Pickaxe;
use pocketmine\item\Shovel;
use pocketmine\item\TieredTool;
use pocketmine\nbt\tag\CompoundTag;

class SpecialTools{

	const TAG_SPECIAL = "special";
	const TAG_ID = "id";

	const MINE_ALL = 0;
	const CUT_ALL = 1;
	const DIG_ALL = 2;
	const STORAGE_BOX = 3;

	/** @var \SplFixedArray */
	private static $list = null;

	public static function init(){
		self::$list = new \SplFixedArray(8);

		self::$list[self::MINE_ALL] = new Pickaxe(Item::DIAMOND_PICKAXE, 0, "§bSpecial Diamond Pickaxe", TieredTool::TIER_DIAMOND);
		self::$list[self::CUT_ALL] = new Axe(Item::DIAMOND_AXE, 0, "§bSpecial Diamond Axe", TieredTool::TIER_DIAMOND);
		self::$list[self::DIG_ALL] = new Shovel(Item::DIAMOND_SHOVEL, 0, "§bSpecial Diamond Shovel", TieredTool::TIER_DIAMOND);
		self::$list[self::STORAGE_BOX] = new StorageBox(Block::CHEST, 0, "Storage Box");
	}

	public static function get(int $id, int $meta = 0, int $count = 1, $tags = "") : Item{
		if(!is_string($tags) and !($tags instanceof CompoundTag)){
			throw new \TypeError("`tags` argument must be a string or CompoundTag instance, " . (is_object($tags) ? "instance of " . get_class($tags) : gettype($tags)) . " given");
		}

		try{
			$listed = self::$list[$id];
			if($listed !== null){
				$item = clone $listed;
			}
		}catch(\RuntimeException $e){
			throw new \InvalidArgumentException("SpecialTools ID $id is invalid or out of bounds");
		}

		$item->setDamage($meta);
		$item->setCount($count);
		$item->setCompoundTag($tags);

		$special = $item->getNamedTagEntry(self::TAG_SPECIAL);
		if(!$special instanceof CompoundTag){
			$special = new CompoundTag(self::TAG_SPECIAL);
		}
		$special->setInt(self::TAG_ID, $id);

		if($id === self::STORAGE_BOX){
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJLKMNOPQRSTUVWXYZ0123456789';
			$str = ''; $str2 = '';
			for($i = 0; $i < 3; ++$i) $str .= $chars[mt_rand(0, 61)];
			for($i = 0; $i < 3; ++$i) $str2 .= $chars[mt_rand(0, 61)];
			$name = '§0§k'.$str.'§r§f§o  Storage Box  §0§k'.$str2.'§r§f§o';

			$special->setString("uniqName", $name);
			$item->setCustomName($name);
		}

		$item->setNamedTagEntry($special);

		$item->setCustomName($item->getName());
		return $item;
	}


	public static function isSpecialTool(Item $item) : bool{
		$special = $item->getNamedTagEntry(self::TAG_SPECIAL);
		return $special instanceof CompoundTag;
	}

	public static function getSpecialId(Item $item){
		$special = $item->getNamedTagEntry(self::TAG_SPECIAL);
		if($special instanceof CompoundTag){
			return $special->getInt(self::TAG_ID);
		}
		return false;
	}

}