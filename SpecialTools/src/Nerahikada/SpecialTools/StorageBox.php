<?php

namespace Nerahikada\SpecialTools;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;

class StorageBox extends Item{

	protected $blockId;

	public function __construct(int $blockId, int $meta = 0, string $name = "Storage Box"){
		$this->blockId = $blockId;
		parent::__construct($blockId, $meta, $name);
	}

	public function getBlock() : Block{
		return BlockFactory::get($this->blockId, $this->meta === -1 ? 0 : $this->meta & 0xf);
	}

	public function getFuelTime() : int{
		return $this->getBlock()->getFuelTime();
	}

	public function getMaxStackSize() : int{
		return 1;
	}

}