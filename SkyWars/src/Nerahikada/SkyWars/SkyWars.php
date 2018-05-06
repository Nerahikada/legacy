<?php

namespace Nerahikada\SkyWars;

use pocketmine\block\Block;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\scheduler\CallbackTask;
use pocketmine\Player;

class SkyWars{

	const ENTRY_MAX = 12;

	public $plugin;
	public $id;
	public $pe;
	public $displayId;
	public $playing = false;
	public $players = [];
	public $maps = [];
	public $mapName;

	const WAIT_TIME = 30;
	const GAME_TIME = 60 * 15;
	public $time;

	public $killer = [];


	const CHEST = [
		[7, Item::LEATHER_CAP], [7, Item::LEATHER_CHESTPLATE], [7, Item::LEATHER_LEGGINGS], [7, Item::LEATHER_BOOTS],
		[3, Item::GOLDEN_HELMET], [3, Item::GOLDEN_CHESTPLATE], [3, Item::GOLDEN_LEGGINGS], [3, Item::GOLDEN_BOOTS],
		[2, Item::CHAINMAIL_HELMET], [2, Item::CHAINMAIL_CHESTPLATE], [2, Item::CHAINMAIL_LEGGINGS], [2, Item::CHAINMAIL_BOOTS],
		[1, Item::IRON_HELMET], [1, Item::IRON_CHESTPLATE], [1, Item::IRON_LEGGINGS], [1, Item::IRON_BOOTS],
		[18, Item::STONE_SWORD], [11, [Item::STONE_SWORD, [Enchantment::SHARPNESS, 1]]], [4, Item::IRON_SWORD],
		[12, Item::STONE_PICKAXE], [5, Item::IRON_PICKAXE], [5, Item::IRON_AXE],
		[16, [Item::EGG, [8, 12, 16]]], [16, [Item::SNOWBALL, [8, 12, 16]]],
		[2, Item::BOW], [2, [Item::ARROW, 10]],
		[33, [Block::STONE, [8, 12, 16, 24]]], [33, [Block::PLANKS, [8, 12, 16, 24]]],
		[33, [Block::STONE, [8, 12, 16, 24]]], [33, [Block::PLANKS, [8, 12, 16, 24]]],
		/*[3, Item::FISHING_ROD],*/ [25, [Item::COOKED_BEEF, [8, 16]]]
	];
	public $CHEST_MAX = 0;

	const MID = [
		[20, Item::IRON_HELMET], [20, Item::IRON_CHESTPLATE], [20, Item::IRON_LEGGINGS], [20, Item::IRON_BOOTS],
		[6, [Item::IRON_CHESTPLATE, [Enchantment::PROJECTILE_PROTECTION, 3]]], [6, [Item::IRON_LEGGINGS, [Enchantment::PROTECTION, 4]]],
		[3, Item::DIAMOND_HELMET], [3, Item::DIAMOND_CHESTPLATE], [3, Item::DIAMOND_LEGGINGS], [3, Item::DIAMOND_BOOTS],
		[1, [Item::DIAMOND_BOOTS, [Enchantment::FIRE_PROTECTION, 5]]], [1, [Item::DIAMOND_HELMET, [Enchantment::PROTECTION, 2]]],
		[24, Item::IRON_SWORD], [13, [Item::IRON_SWORD, [Enchantment::FIRE_ASPECT, 1]]], [8, Item::DIAMOND_SWORD],
		[15, Item::IRON_PICKAXE], [15, Item::IRON_AXE],
		[5, [Item::DIAMOND_PICKAXE, [Enchantment::EFFICIENCY, 1]]], [5, [Item::DIAMOND_AXE, [Enchantment::EFFICIENCY, 1]]],
		[10, [Item::BOW, [Enchantment::POWER, 1]]], [10, [Item::ARROW, [16, 64]]],
		[10, [Item::GOLDEN_APPLE, [4, 8]]], [10, [Item::STICK, [Enchantment::KNOCKBACK, 1]]], [10, [Item::SNOWBALL, 16]],
		[10, [Block::LOG, 64]], [10, Item::FLINT_AND_STEEL]
	];
	public $MID_MAX = 0;


	public function __construct($plugin, $id, $pe = false, $dId = null){
		$this->plugin = $plugin;
		$this->id = $id;
		$this->pe = $pe;
		if(!$pe) $this->displayId = $id + 1;
		else $this->displayId = 'PE '.$dId;

		// Maps
		$mapName = ['Shire', 'Steampunk', 'HalloweenShire', 'Tundra'];
		foreach($mapName as $name){
			$this->dir_copy('worlds/'.$name, 'worlds/'.$name.'.'.$id);
			$this->plugin->getServer()->loadLevel($name.'.'.$id);
			$this->maps[$name] = $this->plugin->getServer()->getLevelByName($name.'.'.$id);
		}
		$this->randomMap();

		// Chest
		foreach(self::CHEST as $chest) $this->CHEST_MAX += $chest[0];
		foreach(self::MID as $mid) $this->MID_MAX += $mid[0];

		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, 'mainThread'], []), 20);
	}

	public function dir_copy($dir, $new){
		if(!is_dir($new)) mkdir($new);
		if(is_dir($dir)){
			if($dh = opendir($dir)){
				while(($file = readdir($dh)) !== false){
					if($file === '.' || $file === '..') continue;
					if(is_dir($dir.'/'.$file)) $this->dir_copy($dir.'/'.$file, $new.'/'.$file);
					else copy($dir.'/'.$file, $new.'/'.$file);
				}
				closedir($dh);
			}
		}
		return true;
	}

	public function getServer(){
		return $this->plugin->getServer();
	}

	public function broadcastMessage($msg, $console = true){
		$this->getServer()->broadcastMessage($msg, $this->players);
		if($console){
			$msg = str_replace("\n", '', $msg);
			$this->getServer()->getLogger()->info('§aSkyWars '.$this->displayId.' §8> §f'.$msg);
		}
	}

	public function broadcastTip($msg){
		$this->getServer()->broadcastTip($msg, $this->players);
	}

	public function broadcastTitle($title, $subtitle = ''){
		foreach($this->players as $player) $player->addTitle($title, $subtitle);
	}


	public function mainThread(){
		if(!$this->playing){
			$count = count($this->players);
			if($count <= 1){
				$this->time = self::WAIT_TIME;
				if($count === 0) return;
			}

			// START
			if($this->time === 0){
				$this->fillChest();
				foreach($this->players as $key => $player){
					$player->setFood(1);
					$player->setFood(20);
					$player->getInventory()->clearAll();
					$this->aroundGlass($key, false);
					$player->setGamemode(Player::SURVIVAL);
					$this->killer[$player->getName()] = 0;
				}
				$this->broadcastMessage('§l§a=====================');
				$this->broadcastMessage('§l§fSkyWars'."\n\n", false);
				$this->broadcastMessage('§l§e島にあるアイテムをを集めて、他のプレイヤーを倒せ！', false);
				$this->broadcastMessage('§l§e特別なアイテムを入手するために、中央の島へ行け！'."\n\n", false);
				$this->broadcastMessage('§l§a=====================', false);
				$this->playing = true;
				$this->time = self::GAME_TIME;
				unset($this->winner);
				return;
			}

			// Display Count
			$this->broadcastTip('§eゲーム開始まで §l§6'.$this->time.'§r §e秒');
			if($count === 1) return;
			if($this->time % 10 === 0) $this->broadcastMessage('§eゲーム開始まで §l§6'.$this->time.'§r §e秒', false);
			if($this->time <= 10){
				$pk = new LevelEventPacket();
				$pk->evid = LevelEventPacket::EVENT_SOUND_CLICK;
				$pk->data = 0;
				foreach($this->players as $player){
					$pk->position = $player;
					$player->dataPacket(clone $pk);
				}
				if($this->time <= 5){
					$this->broadcastMessage('§eゲーム開始まで §l§c'.$this->time.'§r §e秒', false);
					$this->broadcastTitle('§l§c'.$this->time, '§e開始まで…');
				}
			}
		}else{
			if($this->time >= 0){
				$left = 0;
				foreach($this->players as $player) if($player->isSurvival(true)) $left++;
				$min = floor($this->time / 60);
				$sec = $this->time % 60;
				if($sec < 10) $sec = '0'.$sec;
				$s = '                                   ';
				foreach($this->players as $player){
					$player->sendTip(
						$s.'§l§eSKYWARS§r'."\n".
						$s.'§fMap: §a'.$this->mapName."\n".
						$s.'§fTime left: §a'.$min.':'.$sec."\n".
						$s.'§fPlayers left: §a'.$left."\n".
						$s.'§fKills: §a'.$this->killer[$player->getName()]
					);
				}

				if($left <= 1){
					$this->time = -1;
					foreach($this->players as $player) if($player->isSurvival(true)) $winner = $player;
					if(isset($this->winner)) $winner = $this->winner;
					unset($this->winner);
					if(isset($winner)){
						$this->broadcastMessage('§l§a=====================');
						$this->broadcastMessage('§l§fSkyWars'."\n\n", false);
						$this->broadcastMessage('§eWinner §7- §f'.$winner->getName()."\n\n");
						arsort($this->killer);
						$rank = 0;
						foreach($this->killer as $name => $count){
							if($count <= 0 || $rank === 3) break;
							$rank++;
							if($rank === 1) $c = 'e1st Killer';
							else if($rank === 2) $c = '62nd Killer';
							else if($rank === 3) $c = 'c3rd Killer';
							$this->broadcastMessage('§'.$c.'§r §7- §f'.$name.' §7- §f'.$count);
						}
						if($rank !== 0) $this->broadcastMessage("\n", false);
						$this->broadcastMessage('§l§a=====================');
					}
				}
			}else{
				if($this->time === -6){
					foreach($this->players as $player) $this->quit($player, false);
					$this->getServer()->unloadLevel($this->maps[$this->mapName]);
					$this->getServer()->loadLevel($this->mapName.'.'.$this->id);
					$level = $this->getServer()->getLevelByName($this->mapName.'.'.$this->id);
					$level->checkTime();
					$level->setTime(6000);
					$level->checkTime();
					$level->stopTime();
					$level->checkTime();
					$this->maps[$this->mapName] = $level;
					$this->randomMap();
					$this->playing = false;
					$this->time = self::WAIT_TIME;
					$this->players = [];
					$this->killer = [];
				}
			}


		}
		$this->time--;
	}


	public function fillChest(){
		foreach($this->plugin->chestPos[$this->mapName] as $key => $poses){
			if($key === 12){
				$chest = self::MID;
				$MAX = $this->MID_MAX;
			}else{
				$chest = self::CHEST;
				$MAX = $this->CHEST_MAX;
			}
			//echo "\n";
			//echo 'Land '.$key.':'."\n";
			foreach($poses as $pos){
				$ci = $this->maps[$this->mapName]->getTile($pos)->getInventory();
				$ci->clearAll();
				shuffle($chest);
				for($i = 0; $i < mt_rand(3, 6); $i++){
					// RANDOM ??? ----------------------
					$max = $MAX;
					$hit = mt_rand(0, $max - 1);
					foreach($chest as $rand){
						$max -= $rand[0];
						if($max <= $hit) break;
					}
					// ---------------------------------
					$item = $this->getItem($rand[1]);
					//echo '    '.$item->getName()."\n";
					$ci->setItem(mt_rand(0, 27-1), $item);
				}
			}
		}
	}

	// コピペだから変数名ぐちゃぐちゃ
	public function getItem($rand){
		if(is_int($rand))
			$item = Item::get($rand);
		else{
			$id = $rand[0];
			if(is_int($rand[1])) $item = Item::get($id, 0, $rand[1]);
			else{
				$enchantment = false;
				foreach($rand[1] as $tmp){
					if($tmp % 4 !== 0 || $tmp === 0){
						$enchantment = true;
						break;
					}
				}
				if($enchantment){
					$item = Item::get($rand[0]);
					$enchantment = Enchantment::getEnchantment($rand[1][0]);
					$enchantment->setLevel($rand[1][1]);
					$item->addEnchantment($enchantment);
				}else{
					$count = $rand[1][mt_rand(0, count($rand[1]) - 1)];
					$item = Item::get($rand[0], 0, $count);
				}
			}
		}
		return $item;
	}


	public function randomMap(){
		$this->mapName = array_rand($this->maps);
	}

	// id (entryId)
	// add-true / remove-false
	public function aroundGlass($id, $add){
		$block = $add ? Block::GLASS : Block::AIR;
		$pos = $this->plugin->glassPos[$this->mapName][$id];
		$level = $this->maps[$this->mapName];

		$level->setBlockIdAt($pos[0], $pos[1], $pos[2], $block);
		$level->setBlockIdAt($pos[0], $pos[1] + 4, $pos[2], $block);

		for($i = 1; $i <= 3; $i++){
			for($j = -1; $j <= 1; $j++){
				for($k = -1; $k <= 1; $k++){
					$jk = $j + $k;
					if($jk !== 1 && $jk !== -1) continue;
					$level->setBlockIdAt($pos[0] + $j, $pos[1] + $i, $pos[2] + $k, $block);
				}
			}
		}
	}


	public function canJoin($player){
		if($this->pe && $player->control === 1) $reson = 'PE専用の';
		if($this->playing) $reson = 'プレイ中の';
		if(count($this->players) === self::ENTRY_MAX) $reson = 'エントリー人数が制限まで達した';
		if(isset($player->entryId)) $reson = '既にエントリーしている';

		// だが…
		if(!isset($reson) && $player->isSpectator() && isset($player->skywarsId)) $reson = true;

		if(isset($reson)) return $reson; else return true;
	}

	public function join($player){
		// 例外(?)
		if($player->isSpectator() && isset($player->skywarsId))
			$this->plugin->SkyWars[$player->skywarsId]->quit($player);

		$reson = $this->canJoin($player);
		if($reson !== true){
			$player->sendMessage('§c'.$reson.'ため、エントリー出来ませんでした');
			return;
		}

		for($i = 0; $i < self::ENTRY_MAX; $i++){
			if(!isset($this->players[$i])){
				$id = $i;
				break;
			}
		}
		$player->skywarsId = $this->id;
		$player->entryId = $id;
		$this->players[$id] = $player;
		$player->sendMessage('§aSkyWars '.$this->displayId.' にエントリーしました');
		$this->broadcastMessage('§e'.$player->getName().'が参加しました §b('.count($this->players).'/'.self::ENTRY_MAX.')');

		$this->plugin->setInventory($player, Main::INVENTORY_RETURN);

		$this->aroundGlass($id, true);
		$this->glassTp($player);
	}

	public function glassTp($player){
		$pos = $this->plugin->glassPos[$this->mapName][$player->entryId];
		$pos = new Position($pos[0] + 0.5, $pos[1] + 2, $pos[2] + 0.5, $this->maps[$this->mapName]);
		$this->teleport($player, $pos);
	}

	public function quit($player, $message = true, $rejoin = false){
		if($message && !$player->isSpectator()){
			$this->aroundGlass($player->entryId, false);

			$player->sendMessage('§cゲームから抜けました');
			unset($this->players[$player->entryId]);
			$this->broadcastMessage('§e'.$player->getName().'が退出しました'.' §b('.count($this->players).'/'.self::ENTRY_MAX.')');
		}

		$this->plugin->setInventory($player, Main::INVENTORY_LOBBY);

		$player->setGamemode(Player::ADVENTURE);
		$player->setHealth(20);
		$player->setFood(1);
		$player->setFood(20);
		$player->removeAllEffects();

		// issue... 応急処置
		if(isset($player->entryId)) unset($this->players[$player->entryId]);
		unset($player->skywarsId);
		unset($player->entryId);
		unset($player->lastD);

		if($rejoin) $this->plugin->quickEntry($player);
		else $this->teleport($player, $this->plugin->lobby);
	}


	public function teleport($player, $pos){
		$this->plugin->teleport($player, $pos);
	}

}