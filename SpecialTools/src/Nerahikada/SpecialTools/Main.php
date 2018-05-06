<?php

namespace Nerahikada\SpecialTools;

use pocketmine\block\Block;
use pocketmine\block\BlockToolType;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\Level;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\TakeItemEntityPacket;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

	public function onLoad(){
		SpecialTools::init();
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}





	public function MineAll_BlockBreakEvent(BlockBreakEvent $event){
		if($event->isCancelled()) return;
		$item = $event->getItem();
		$block = $event->getBlock();
		$id = $block->getId();
		if(SpecialTools::getSpecialId($item) === SpecialTools::MINE_ALL && $block->getToolType() === BlockToolType::TYPE_PICKAXE){
			if($id === Block::GOLD_ORE || $id === Block::IRON_ORE || $id === Block::COAL_ORE || $id === Block::LAPIS_ORE || $id === Block::DIAMOND_ORE || $id === Block::REDSTONE_ORE || $id === Block::GLOWING_REDSTONE_ORE || $id === Block::EMERALD_ORE || $id === Block::NETHER_QUARTZ_ORE){
				$this->MineAll_CheckBlock($block->x, $block->y, $block->z, $block, $item);
			}else{
				$level = $block->getLevel();
				$temp = new Vector3;
				$range = 1;
				for($x = $block->x - $range; $x <= $block->x + $range; ++$x){
					for($y = $block->y - $range; $y <= $block->y + $range; ++$y){
						for($z = $block->z - $range; $z <= $block->z + $range; ++$z){
							$temp->setComponents($x, $y, $z);
							$target = $level->getBlock($temp);
							if(!$temp->equals($block) && $block->getId() === $target->getId()){
								$this->useBreakOn($level, $temp, $item);
							}
						}
					}
				}
			}
		}
	}

	public function MineAll_CheckBlock(int $x, int $y, int $z, Block $block, Item $item){
		$level = $block->getLevel();
		$temp = new Vector3;
		for($xx = $x - 1; $xx <= $x + 1; ++$xx){
			for($yy = $y - 1; $yy <= $y + 1; ++$yy){
				for($zz = $z - 1; $zz <= $z + 1; ++$zz){
					$temp->setComponents($xx, $yy, $zz);
					$target = $level->getBlock($temp);
					$blockId = $block->getId();
					$targetId = $target->getId();
					$option = (($blockId === Block::REDSTONE_ORE || $blockId === Block::GLOWING_REDSTONE_ORE) && ($targetId === Block::REDSTONE_ORE || $targetId === Block::GLOWING_REDSTONE_ORE));
					if(!$temp->equals($block) && ($blockId === $targetId || $option)){
						$this->MineAll_BreakBlock($temp, $block, $item);
					}
				}
			}
		}
	}

	public function MineAll_BreakBlock(Vector3 $vector, Block $block, Item $item){
		if($item->getDamage() <= $item->getMaxDurability()){
			$level = $block->getLevel();
			$target = $level->getBlock($vector);
			if(!$this->useBreakOn($level, $vector, $item)) return false;
			$this->MineAll_CheckBlock($vector->x, $vector->y, $vector->z, $block, $item);
		}
	}





	public function CutAll_BlockBreakEvent(BlockBreakEvent $event){
		if($event->isCancelled()) return;
		$item = $event->getItem();
		$block = $event->getBlock();
		$id = $block->getId();
		if(SpecialTools::getSpecialId($item) === SpecialTools::CUT_ALL && $block->getToolType() === BlockToolType::TYPE_AXE){
			if($id === Block::LOG || $id === Block::LOG2){
				$this->CutAll_CheckBlock($block->x, $block->y, $block->z, $block, $item);
			}else{
				$level = $block->getLevel();
				$temp = new Vector3;
				$range = 1;
				for($x = $block->x - $range; $x <= $block->x + $range; ++$x){
					for($y = $block->y - $range; $y <= $block->y + $range; ++$y){
						for($z = $block->z - $range; $z <= $block->z + $range; ++$z){
							$temp->setComponents($x, $y, $z);
							$target = $level->getBlock($temp);
							if(!$temp->equals($block) && $block->getId() === $target->getId()){
								$this->useBreakOn($level, $temp, $item);
							}
						}
					}
				}
			}
		}
	}

	public function CutAll_CheckBlock(int $x, int $y, int $z, Block $block, Item $item){
		$level = $block->getLevel();
		$temp = new Vector3;
		for($xx = $x - 1; $xx <= $x + 1; ++$xx){
			for($yy = $y - 1; $yy <= $y + 1; ++$yy){
				for($zz = $z - 1; $zz <= $z + 1; ++$zz){
					$temp->setComponents($xx, $yy, $zz);
					$target = $level->getBlock($temp);
					if(!$temp->equals($block) && $block->getId() === $target->getId() && $block->getVariant() === $target->getVariant()){
						$this->CutAll_BreakBlock($temp, $block, $item);
					}
				}
			}
		}
	}

	public function CutAll_BreakBlock(Vector3 $vector, Block $block, Item $item){
		if($item->getDamage() <= $item->getMaxDurability()){
			$level = $block->getLevel();
			$target = $level->getBlock($vector);
			if(!$this->useBreakOn($level, $vector, $item)) return false;
			$this->CutAll_CheckBlock($vector->x, $vector->y, $vector->z, $block, $item);
		}
	}





	public function DigAll_BlockBreakEvent(BlockBreakEvent $event){
		if($event->isCancelled()) return;
		$item = $event->getItem();
		$block = $event->getBlock();
		if(SpecialTools::getSpecialId($item) === SpecialTools::DIG_ALL && $block->getToolType() === BlockToolType::TYPE_SHOVEL){
			$level = $block->getLevel();
			$temp = new Vector3;
			$range = 1;
			for($x = $block->x - $range; $x <= $block->x + $range; ++$x){
				for($y = $block->y - $range; $y <= $block->y + $range; ++$y){
					for($z = $block->z - $range; $z <= $block->z + $range; ++$z){
						$temp->setComponents($x, $y, $z);
						$target = $level->getBlock($temp);
						$blockId = $block->getId();
						$targetId = $target->getId();

						$option = (($blockId === 2 || $blockId === 3) && ($targetId === 2 || $targetId === 3));

						if(!$temp->equals($block) && ($blockId === $targetId || $option)){
							$this->useBreakOn($level, $temp, $item);
						}
					}
				}
			}
		}
	}





	public function StorageBox_BlockPlaceEvent(BlockPlaceEvent $event){
		$item = $event->getItem();
		if(SpecialTools::getSpecialId($item) === SpecialTools::STORAGE_BOX){
			$event->setCancelled();
			$player = $event->getPlayer();
			$special = $item->getNamedTagEntry(SpecialTools::TAG_SPECIAL);
			$id = $special->getInt("itemId", false);
			if($id === 0){
				$player->sendMessage("格納したいアイテムを捨ててください");
				$player->chooseStorageBox = $item;
				$player->heldItemIndex = $player->getInventory()->getHeldItemIndex();
			}else{
				$meta = $special->getInt("meta");
				$count = $special->getInt("count");
				$temp = Item::get($id, $meta);
				$temp = $count < $temp->getMaxStackSize() ? $count : $temp->getMaxStackSize();
				$count -= $temp;
				$add = Item::get($id, $meta, $temp);
				if($player->getInventory()->canAddItem($add)){
					$special->setInt("count", $count);
					if($count <= 0) $special->setInt("itemId", 0);
					$item->setNamedTagEntry($special);
					$player->getInventory()->addItem($add);
				}
			}
			$this->StorageBox_Update($item);
			$player->getInventory()->setItemInHand($item);
		}
	}

	public function StorageBox_PlayerDropItemEvent(PlayerDropItemEvent $event){
		$player = $event->getPlayer();
		if(isset($player->chooseStorageBox)){
			$event->setCancelled();
			$item = $event->getItem();
			if(SpecialTools::isSpecialTool($item)){
				$player->sendMessage("§c特殊なアイテムは指定できません");
				return;
			}
			$box = $player->chooseStorageBox;
			unset($player->chooseStorageBox);
			$special = $box->getNamedTagEntry(SpecialTools::TAG_SPECIAL);
			$id = $item->getId();
			$meta = $item->getDamage();
			$special->setInt("itemId", $id);
			$special->setInt("meta", $meta);
			$special->setInt("count", 0);
			$box->setNamedTagEntry($special);
			$player->sendMessage("アイテムを ".$this->StorageBox_GetDisplayItem($item)." に設定しました");

			$inventory = $player->getInventory();
			foreach($inventory->getContents() as $key => $content){
				if($content->getId() === $id && $content->getDamage() === $meta){
					$special->setInt("count", $special->getInt("count") + $content->getCount());
					$inventory->setItem($key, Item::get(Item::AIR));
				}
			}

			$this->StorageBox_Update($box);
			$player->getInventory()->setItem($player->heldItemIndex, $box);
		}
	}

	public function StorageBox_InventoryPickupItemEvent(InventoryPickupItemEvent $event){
		if($event->isCancelled()) return;
		$item = $event->getItem()->getItem();
		$inventory = $event->getInventory();
		foreach($inventory->getContents() as $key => $content){
			if(SpecialTools::getSpecialId($content) === SpecialTools::STORAGE_BOX){
				$special = $content->getNamedTagEntry(SpecialTools::TAG_SPECIAL);
				$itemId = $special->getInt("itemId", false);
				if($itemId !== 0 && $itemId === $item->getId() && $special->getInt("meta") === $item->getDamage()){
					$event->setCancelled();

					//タイミングの調整が難しいので…
					$player = null;
					foreach($this->getServer()->getOnlinePlayers() as $p){
						if($p->getInventory() === $inventory){
							$player = $p;
							break;
						}
					}
					if($player === null) return;

					switch($item->getId()){
						case Item::WOOD:
							$player->awardAchievement("mineWood");
							break;
						case Item::DIAMOND:
							$player->awardAchievement("diamond");
							break;
					}

					$itemEntity = $event->getItem();

					$pk = new TakeItemEntityPacket;
					$pk->eid = $player->getId();
					$pk->target = $itemEntity->getId();
					$this->getServer()->broadcastPacket($itemEntity->getViewers(), $pk);

					$itemEntity->flagForDespawn();

					$special->setInt("count", $special->getInt("count", false) + $item->getCount());
					$content->setNamedTagEntry($special);
					$this->StorageBox_Update($content);
					$inventory->setItem($key, $content);
					return;
				}
			}
		}
	}

	public function StorageBox_Update(Item $item){
		$special = $item->getNamedTagEntry(SpecialTools::TAG_SPECIAL);
		$itemId = $special->getInt("itemId", false);
		$uniqName = $special->getString("uniqName", false);
		if($itemId === 0){
			$item->setCustomName($uniqName);
		}else{
			$temp = Item::get($special->getInt("itemId"), $special->getInt("meta"));
			$item->setCustomName($uniqName."\n".$this->StorageBox_GetDisplayItem($temp)."\n".$special->getInt("count"));
		}
	}

	public function StorageBox_GetDisplayItem(Item $item){
		return "{$item->getName()}§7({$item->getId()}:{$item->getDamage()})§f";
	}








	public function useBreakOn(Level $level, Vector3 $vector, Item &$item = null) : bool{
		$target = $level->getBlock($vector);
		$affectedBlocks = $target->getAffectedBlocks();

		if($item === null){
			$item = ItemFactory::get(Item::AIR, 0, 0);
		}

		$drops = [];
		$xpDrop = 0;

		$drops = array_merge(...array_map(function(Block $block) use ($item) : array{ return $block->getDrops($item); }, $affectedBlocks));
		$xpDrop = array_sum(array_map(function(Block $block) use ($item) : int{ return $block->getXpDropForTool($item); }, $affectedBlocks));

		$spawnProtection = false;
		if(($distance = $this->getServer()->getSpawnRadius()) > -1){
			$t = new Vector2($target->x, $target->z);

			$spawnLocation = $level->getSpawnLocation();
			$s = new Vector2($spawnLocation->x, $spawnLocation->z);
			if($t->distance($s) <= $distance){
				$spawnProtection = true;
			}
		}

		if(!$target->isBreakable($item) || $spawnProtection){
			return false;
		}

		foreach($affectedBlocks as $t){
			//$level->addParticle(new DestroyBlockParticle($t->add(0.5, 0.5, 0.5), $t));

			$t->onBreak($item);

			$tile = $level->getTile($t);
			if($tile !== null){
				if($tile instanceof Container){
					if($tile instanceof Chest){
						$tile->unpair();
					}

					$tile->getInventory()->dropContents($level, $t);
				}

				$tile->close();
			}
		}

		$item->useOn($target);

		if(!empty($drops)){
			$dropPos = $target->add(0.5, 0.5, 0.5);
			foreach($drops as $drop){
				if(!$drop->isNull()){
					$level->dropItem($dropPos, $drop);
				}
			}
		}

		if($xpDrop > 0){
			$level->dropExperience($target->add(0.5, 0.5, 0.5), $xpDrop);
		}

		return true;
	}


}