<?php

namespace vs;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;

class Arena{

	const EMPTY = 0;
	const WAITING = 1;
	const FULL = 2;

	const PREVS = 5;
	const VS = 60 * 5;

	public $id;
	public $sign;
	public $levelName = "";
	public $players = [];
	public $viewers = [];
	public $time = self::PREVS;
	public $playing = false;


	public function __construct(int $id, Sign $sign){
		$this->id = $id;
		$this->sign = $sign;

		$sign->setLine(0, Main::SIGN);

		$this->updateSign();
		$this->reloadLevel();

		Utils::repeatingTask([$this, "mainThread"], [], 20);
	}


	public function reloadLevel($name = null){
		$server = Server::getInstance();

		if($this->levelName !== ""){
			$level = $server->getLevelByName("copy/{$this->levelName}.{$this->id}");
			$server->unloadLevel($level);
		}

		$this->levelName = ($name !== null && isset(ArenaLevel::INFO[$name])) ? $name : ArenaLevel::getRandom();

		Utils::deleteDirectory("worlds/copy/{$this->levelName}.{$this->id}");
		Utils::copyDirectory("worlds/orig/{$this->levelName}", "worlds/copy/{$this->levelName}.{$this->id}");
		$server->loadLevel("copy/{$this->levelName}.{$this->id}");

		$level = $server->getLevelByName("copy/{$this->levelName}.{$this->id}");
		$level->checkTime();
		$level->setTime(6000);
		$level->checkTime();
		$level->stopTime();
		$level->checkTime();

		$this->updateSign();
	}

	public function setInventory_Waiting(Player $player){
		if(!$player->loggedIn) return;
		$inventory = $player->getInventory();
		$inventory->clearAll();
		$inventory->setItem(8, Item::get(Item::BED, 14)->setCustomName("§c退出する"));
	}

	public function join(Player $player){
		if(isset($player->arena)){
			$player->sendMessage("§c既に参加しています！");
			return;
		}
		if(count($this->players) === self::FULL){
			$player->sendMessage("§c満員です！");
			return;
		}

		$player->setGamemode(Player::SURVIVAL);

		$player->arena = $this->id;
		$player->sendMessage("§2a".($this->id + 1)." §aにエントリーしました");

		$this->setInventory_Waiting($player);

		for($i = 0; $i <= 1; $i++){
			if(!isset($this->players[$i])){
				$key = $i;
				break;
			}
		}
		$this->players[$key] = $player;

		$player->setImmobile(true);
		NPC::remove($player);
		$player->teleport(...ArenaLevel::getPos($this->levelName, $this->id, $key));

		$this->updateSign();
	}

	public function leave(Player $player){
		if(!isset($player->arena)) return;
		unset($player->arena);
		//$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();

		foreach($this->players as $key => $p){
			if($p === $player){
				unset($this->players[$key]);
			}
		}

		foreach($this->players as $p){
			$player->removeAllEffects();
			$this->setInventory_Waiting($p);
		}

		Main::getInstance()->hub($player);

		$this->updateSign();
	}

	public function quit(Player $player){
		$this->playing ? $this->gameEnd($player) : $this->leave($player);
	}

	public function updateSign(){
		$this->sign->setLine(2, "§b".$this->levelName);
		$count = count($this->players);
		if($count === self::EMPTY) $status = "§aEmpty";
		elseif($count === self::WAITING) $status = "§eWaiting";
		elseif($count === self::FULL) $status = "§cFull";
		$this->sign->setLine(3, $status);
	}

	public function mainThread(){
		if(!$this->playing){
			$count = count($this->players);
			if($count === self::EMPTY) return;

			elseif($count === self::WAITING){
				$this->broadcastTip("§6§l» §r§fWaiting... §6§l«§r");
				$this->time = self::PREVS;
			}elseif($count === self::FULL){
				if($this->time === self::PREVS){
					foreach($this->players as $player){
						$inventory = $player->getArmorInventory();
						$inventory->clearAll();

						$armors = [
							Item::IRON_HELMET,
							Item::IRON_CHESTPLATE,
							Item::IRON_LEGGINGS,
							Item::IRON_BOOTS
						];

						if($player->edition === 0)
							$armors[1] = Item::CHAINMAIL_CHESTPLATE;

						$inventory->setHelmet(Item::get($armors[0]));
						$inventory->setChestplate(Item::get($armors[1]));
						$inventory->setLeggings(Item::get($armors[2]));
						$inventory->setBoots(Item::get($armors[3]));


						self::setVsInventory($player);
					}

					$this->broadcastMessage("§a========================");
					$this->broadcastMessage(" §l§e".Main::NAME);
					$this->broadcastMessage("   ".$this->players[0]->getDisplayName());
					$this->broadcastMessage("       §7vs");
					$this->broadcastMessage("   ".$this->players[1]->getDisplayName());
					$this->broadcastMessage("§a========================");
					$this->broadcastMessage("   §fMap: §a".$this->levelName);
					$this->broadcastMessage("   §fCreater: §a".ArenaLevel::getCreater($this->levelName));
					$this->broadcastMessage("§a========================");
				}elseif($this->time === 0){
					foreach($this->players as $player){
						$player->setImmobile(false);
						$player->setHealth(20);
						$player->setFood(20);
					}
					$this->broadcastLevelEventPacket(LevelEventPacket::EVENT_SOUND_ANVIL_FALL);

					$this->broadcastTip("§6§l» §r§fFIGHT! §6§l«§r");
					$this->time = self::VS;
					$this->playing = true;
					return;
				}

				$this->broadcastLevelEventPacket(LevelEventPacket::EVENT_SOUND_CLICK);

				$this->broadcastTip("§6§l» §r§fStart in §a".$this->time."§f... §6§l«§r");
				$this->time--;
			}
		}else{
			if($this->time === 0){
				$this->gameEnd();
			}


			$min = floor($this->time / 60);
			$sec = $this->time % 60;
			if($sec < 10) $sec = '0'.$sec;
			$this->broadcastTip("§6§l» §r§fTime left: §a".$min.":".$sec." §6§l«§r");
			$this->time--;
		}
	}

	public function gameEnd(Player $loser = null){
		Utils::delayedTask([$this, "gameEnd2"], [$loser], 1);
	}

	public function gameEnd2(Player $loser = null){
		$this->playing = false;
		$this->time = self::PREVS;

		$this->broadcastMessage("§a========================");
		$this->broadcastMessage("§l§aゲーム終了！");

		if($loser !== null){
			foreach($this->players as $key => $player)
				if($loser !== $player) $winner = $player;
			if(!isset($winner)){
				Server::getInstance()->getLogger()->error("エラーが発生しました");
				$this->broadcastMessage("§l§4エラーが発生しました");
				return;
			}
			Utils::asyncDB("update", $winner->getXuid(), ["win", "total"], ["win+1", "win+lose+draw"]);
			Utils::asyncDB("update", $loser->getXuid(), ["lose", "total"], ["lose+1", "win+lose+draw"]);

			$winner->addTitle("§l§6VICTORY!");
			$loser->addTitle("§l§4YOU DIED!");

			$this->broadcastMessage("  §eWinner: §f" . $winner->getDisplayName());
			$this->broadcastMessage("  §cLoser: §f" . $loser->getDisplayName());
		}else{
			foreach($this->players as $player){
				Utils::asyncDB("update", $player->getXuid(), ["draw", "total"], ["draw+1", "win+lose+draw"]);
			}

			$this->broadcastMessage(" §b引き分け");
		}

		$this->broadcastMessage("§a========================");

		$this->endView();

		foreach($this->players as $player){
			if(!$player->loggedIn) continue;
			unset($player->arena);
			$player->getInventory()->clearAll();
			$player->getArmorInventory()->clearAll();
			$player->removeAllEffects();
			$player->extinguish();
			$player->setHealth(20);
			$player->setFood(20);
			Main::getInstance()->hub($player);
		}

		$this->players = [];
		$this->reloadLevel();
		$this->updateSign();
	}


	public function broadcastMessage(string $message){
		foreach(array_merge($this->players, $this->viewers) as $player)
			$player->sendMessage($message);
	}

	public function broadcastTip(string $message){
		foreach(array_merge($this->players, $this->viewers) as $player)
			$player->sendTip($message);
	}

	public function broadcastLevelEventPacket(int $evid){
		foreach(array_merge($this->players, $this->viewers) as $player){
			$pk = new LevelEventPacket;
			$pk->evid = $evid;
			$pk->position = $player;
			$pk->data = 0;
			$player->dataPacket($pk);
		}
	}





	public function view(Player $player){
		$this->viewers[$player->getName()] = $player;
		$player->viewArena = $this->id;

		$player->setGamemode(Player::SPECTATOR);

		$inventory = $player->getInventory();
		$inventory->clearAll();
		$inventory->setItem(8, Item::get(Item::BED, 14)->setCustomName("§c退出する"));

		$player->addTitle("", "§7あなたは観戦モードです");

		//bad code
		$player->sendMessage("§a========================");
		$player->sendMessage(" §l§e".Main::NAME);
		$p1 = isset($this->players[0]) ? $this->players[0]->getDisplayName() : "エントリー待ち";
		$player->sendMessage("   ".$p1);
		$player->sendMessage("       §7vs");
		$p2 = isset($this->players[1]) ? $this->players[1]->getDisplayName() : "エントリー待ち";
		$player->sendMessage("   ".$p2);
		$player->sendMessage("§a========================");
		$player->sendMessage("   §fMap: §a".$this->levelName);
		$player->sendMessage("   §fCreater: §a".ArenaLevel::getCreater($this->levelName));
		$player->sendMessage("§a========================");

		NPC::remove($player);

		$player->teleport(...ArenaLevel::getView($this->levelName, $this->id));
	}

	public function leaveView(Player $player){
		unset($player->viewArena);
		unset($this->viewers[$player->getName()]);
		Main::getInstance()->hub($player);
	}

	public function endView(){
		foreach($this->viewers as $player){
			if($player->loggedIn){
				$this->leaveView($player);
			}
		}
		$this->viewers = [];
	}


	public static function setVsInventory(Player $player){
		$inventory = $player->getInventory();
		$inventory->clearAll();

		$items = $player->data["inventory"];
		if($items === ""){
			$items = [
				Item::get(Item::IRON_SWORD),
				Item::get(Item::BOW),
				Item::get(Item::GOLDEN_APPLE, 0, 6),
				Item::get(Item::BUCKET, 8),
				Item::get(Item::BUCKET, 10),
				Item::get(Item::IRON_PICKAXE),
				Item::get(Item::STONE, 0, 64),
				Item::get(Item::IRON_AXE),
				Item::get(Item::PLANKS, 0, 64),
				Item::get(Item::ARROW, 0, 32)
			];
		}else{
			foreach($items as $key => $value)
				$items[$key] = Item::get($value["id"], $value["damage"] ?? 0, $value["count"] ?? 1);
		}

		foreach($items as $key => $item) $inventory->setItem($key, $item);
	}

}