<?php

namespace vs;

use pocketmine\block\Carpet;
use pocketmine\block\EndRod;
use pocketmine\block\Flowable;
use pocketmine\block\Skull;
//use pocketmine\block\WaterLily;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Sign;
use pocketmine\Player;

class Main extends PluginBase implements Listener{

	protected static $instance = null;

	public static function getInstance(): Main{
		return self::$instance;
	}

	protected static $lobby = [];

	public static function getLobby(): array{
		return self::$lobby;
	}



	const DB_TABLE = "build_uhc";
	const NAME = "Build UHC";
	const SIGN = "§f[ ".self::NAME." ]";
	const UPDATE_VERSION = 13;

	public $arenas = [];
	public $signRegistered = false;

	public $duel = [];


	public function onLoad(){
		Utils::copyDirectory("worlds/orig/lobby", "worlds/lobby");
	}

	public function onEnable(){
		self::$instance = $this;

		NPC::create();

		Ranking::init();
		$db = new DB("", "糞設計");
		$db->update($db->connect());
		$db->onCompletion($this->getServer());

		if(!file_exists("worlds/copy")) mkdir("worlds/copy");

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}



	public function registerSigns(){
		if($this->signRegistered) return;
		$tiles = $this->getServer()->getDefaultLevel()->getTiles();
		foreach($tiles as $tile){
			if(Utils::isPrefixSign($tile)){
				$this->registerSign($tile);
				$this->signRegistered = true;
			}
		}
	}

	public function registerSign(Sign $sign){
		$id = Utils::getArenaId($sign);
		$this->arenas[$id] = new Arena($id, $sign);
	}



	public function hub(Player $player){
		$player->setGamemode(Player::ADVENTURE);
		$player->setImmobile(false);

		$this->setHubInventory($player);

		$player->teleport(...self::getLobby());

		NPC::add($player);

		Ranking::add($player);
	}

	public function setHubInventory(Player $player){
		$inventory = $player->getInventory();
		$inventory->clearAll();
		$inventory->setItem(2, Item::get(Item::MOB_HEAD, 3)->setCustomName("§aステータス確認"));
		$inventory->setItem(3, Item::get(Item::CHEST)->setCustomName("§dインベントリ編集"));
		$inventory->setItem(4, Item::get(Item::CLOCK)->setCustomName("§b試合を観戦する\n§7観戦したい試合の看板をタップしてください"));
		$inventory->setItem(6, Item::get(Item::BOOK)->setCustomName("アップデート情報"));
	}




	public function onPreLogin(PlayerPreLoginEvent $event){
		if(self::$lobby === [])
			self::$lobby = [new Position(569.5, 52, 948.5, $this->getServer()->getDefaultLevel()), 0];
	}


	public function onLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		$this->hub($player);
	}


	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();

		NPC::add($player); //Login時だと反映されないので
		Ranking::add($player); //Login時だと反映されないので(2)

		// 看板の登録 (1回のみ)
		Utils::delayedTask([$this, "registerSigns"], [], 3);

		// 表示を整える
		$player->resetTitles();
		Utils::delayedTask([$player, "addTitle"], ["§l§6".Main::NAME, "§k|||§r §7Uncharted Server§r §k|||"], 40);
		Utils::delayedTask([$player, "removeTitles"], [], 200);
		$player->sendTip(" ");
		$player->sendPopup(" ");
		// メッセージ
		$player->sendMessage("§k|||§r §7Uncharted Server§r §k|||");
		$player->sendMessage("§fアンチャーテッドサーバー §7へようこそ");

		// ゲームルール
		$pk = new GameRulesChangedPacket;
		$pk->gameRules = [
			'naturalregeneration' => [1, false],
			'dodaylightcycle' => [1, false]
		];
		$player->dataPacket($pk);

		// 時間の固定
		foreach($this->getServer()->getLevels() as $level){
			$level->checkTime();
			$level->setTime(6000);
			$level->checkTime();
			$level->stopTime();
			$level->checkTime();
		}

		Utils::asyncDB("setup", $player->getXuid(), $player->getName(), $player->edition);
	}

	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if(isset($player->arena)){
			$this->arenas[$player->arena]->quit($player);
		}
	}



	public function onInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();

		if($player->isCreative(true)) return;

		if(!$this->inArena($player)){
			$event->setCancelled();
		}

		$item = $event->getItem();
		$itemId = $item->getId();
		if(!$this->isPlaying($player)){
			if($itemId === Item::BED && $item->hasCustomName()){
				$this->arenas[$player->arena]->leave($player);
				return;
			}else if($itemId === Item::MOB_HEAD){
				$tick = $this->getServer()->getTick();
				if(!isset($player->interactTick))
					$player->interactTick = $tick - 20 * 5 + 2;
				if($player->interactTick < $tick - 20 * 5){
					Utils::asyncDB("get", $player->getXuid(), $player->edition, "showStatus");
					$player->interactTick = $tick;
				}
				return;
			}else if($itemId === Item::BOOK){
				$this->showUpdate($player);
				return;
			}else if($itemId === Item::CHEST){
				Arena::setVsInventory($player);
				$player->sendMessage("§aインベントリ編集モードに入りました");
				$player->sendMessage("§l§cコマンドを実行しないと保存されません！");
				$player->sendMessage("§2/save  §fインベントリの保存");
				$player->sendMessage("§2/quit  §f編集モードから抜け出す");
				$player->sendMessage("§2/reset  §fインベントリのリセット");
				return;
			}
		}

		$tile = $player->getLevel()->getTile($event->getBlock());
		if(Utils::isArenaSign($tile)){
			$arenaId = Utils::getArenaId($tile);
			if(!isset($this->arenas[$arenaId]) || isset($player->arena)){
				return;
			}
			if($itemId === Item::CLOCK){
				$this->arenas[$arenaId]->view($player);
			}else{
				$this->arenas[$arenaId]->join($player);
			}
		}
	}



	public function onReceive(DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		if($packet::NETWORK_ID === ProtocolInfo::INVENTORY_TRANSACTION_PACKET){
			if($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
				if($packet->trData->entityRuntimeId === NPC::getEid()){
					$this->autoEntry($player);
				}
			}else if($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM){
				if(!$player->isSpectator() || !isset($player->viewArena)) return;  //not viewing
				$id = $player->getInventory()->getItemInHand()->getId();
				if($id === Item::BED){
					$this->arenas[$player->viewArena]->leaveView($player);
				}
			}
		}else if($packet::NETWORK_ID === ProtocolInfo::LOGIN_PACKET && $packet->protocol === ProtocolInfo::CURRENT_PROTOCOL){
			$player->edition = Utils::getEdition($packet->clientData["CurrentInputMode"]);
		}else if($packet::NETWORK_ID === ProtocolInfo::ITEM_FRAME_DROP_ITEM_PACKET){
			$event->setCancelled();
		}else if($packet::NETWORK_ID === ProtocolInfo::LEVEL_SOUND_EVENT_PACKET){
			if($player->isSpectator() && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)
				$event->setCancelled();
		}else if($packet::NETWORK_ID === ProtocolInfo::MODAL_FORM_RESPONSE_PACKET){
			if(isset($this->duel[$packet->formId])){
				$from = $this->duel[$packet->formId];
				unset($this->duel[$packet->formId]);
				if($this->inArena($from)){
					$player->sendMessage("§c送り主がエントリーしているため、無効化されました");
					return;
				}
				if($this->inArena($player)){
					$player->sendMessage("§cエントリーしているため、無効化されました");
					return;
				}
				$data = json_decode($packet->formData, true);
				if($data !== true){
					$from->sendMessage("{$player->getName()} さんが申し込みを拒否しました");
					return;
				}

				foreach($this->arenas as $arena){
					if(count($arena->players) === Arena::EMPTY){
						$arena->join($player);
						$arena->join($from);
						break;
					}
				}
			}
		}
	}


	public function autoEntry(Player $player){
		for($i = 0; $i < count($this->arenas); $i++){
			$arenas[$i] = count($this->arenas[$i]->players);
		}
		arsort($arenas);
		foreach($arenas as $id => $count){
			if($count !== Arena::FULL && !isset($player->arena)){
				$arena = $this->arenas[$id];
				if($count === Arena::WAITING){
					$p = null;
					foreach($arena->players as $temp) $p = $temp;
					if($player->edition !== $p->edition) continue;
					$difference = abs($player->data["ratio"] - $p->data["ratio"]);
					if($p->edition === 0) $difference -= 2;
					if($difference > 2) continue;
				}
				$this->arenas[$id]->join($player);
				break;
			}
		}
	}




	public function onDamage(EntityDamageEvent $event){
		$entity = $event->getEntity();
		if($entity instanceof Player){
			if(!$this->isPlaying($entity)){
				$event->setCancelled();
				$cause = $event->getCause();
				if($cause === EntityDamageEvent::CAUSE_FIRE || $cause === EntityDamageEvent::CAUSE_FIRE_TICK) $entity->extinguish();
			}else{
				if($event instanceof EntityDamageByChildEntityEvent && $event->getChild() instanceof Arrow){
					$damager = $event->getDamager();
					$pk = new PlaySoundPacket;
					$pk->soundName = "random.orb";
					$pk->x = $damager->x;
					$pk->y = $damager->y;
					$pk->z = $damager->z;
					$pk->volume = 1;
					$pk->pitch = 0.5;
					$damager->dataPacket($pk);
					$pk->pitch = 1;
					$pk->isEncoded = false;
					$damager->dataPacket($pk);
				}
			}
		}
	}

	public function onDeath(PlayerDeathEvent $event){
		$player = $event->getPlayer();
		if($this->isPlaying($player)){
			$event->setDeathMessage("");
			$player->setHealth(20);
			$event->setDrops([]);
			$this->arenas[$player->arena]->gameEnd($player);
		}
	}

	public function onExhaust(PlayerExhaustEvent $event){
		$event->setCancelled();
	}

	public function onRegain(EntityRegainHealthEvent $event){
		if($event->getRegainReason() === EntityRegainHealthEvent::CAUSE_SATURATION)
			$event->setCancelled();
	}

	public function onShoot(EntityShootBowEvent $event){
		$entity = $event->getEntity();
		$tick = $this->getServer()->getTick();
		if(!isset($entity->shootTick)) $entity->shootTick = $tick - 22;
		if($entity->shootTick >= $tick - 20){
			$event->setCancelled();
			return;
		}
		$entity->shootTick = $tick;
		$event->getProjectile()->force = $event->getForce();
	}

	public function onLaunch(ProjectileLaunchEvent $event){
		$entity = $event->getEntity();
		if(isset($entity->force))
			$entity->setMotion($entity->getMotion()->divide($entity->force)->multiply($entity->force * 1.5));
	}


	// 燃える
	public function onCombust(EntityCombustByEntityEvent $event){
		// Projectile only (?)
		$event->setCancelled();
	}


	// 食べる
	public function onConsume(PlayerItemConsumeEvent $event){
		if(!$this->inArena($event->getPlayer())) $event->setCancelled();
	}




	public function onBreak(BlockBreakEvent $event){
		$block = $event->getBlock();
		$level = $event->getBlock()->getLevel();
		$key = (string) $block->asVector3();
		if(isset($level->placedBlock[$key])){
			unset($level->placedBlock[$key]);
		}else{
			$player = $event->getPlayer();
			if(!$player->isCreative(true)){
				if($this->isPlaying($player) && $block instanceof Flowable && !$block instanceof Skull && !$block instanceof EndRod && !$block instanceof Carpet) return;
				$event->setCancelled();
				if(!$block instanceof Flowable || $block instanceof Skull || $block instanceof EndRod || $block instanceof Carpet)
					Utils::delayedTask([$player, "teleport"], [$player->asVector3(), $player->yaw, $player->pitch], 1);
			}
		}
	}

	public function onPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		if($player->isCreative(true)) return;
		$block = $event->getBlock();
		$level = $event->getBlock()->getLevel();
		if(!$this->isPlaying($player)){
			$event->setCancelled();
			return;
		}
		$key = (string) $block->asVector3();
		$level->placedBlock[$key] = true;
	}

	public function onBucketEmpty(PlayerBucketEmptyEvent $event){
		$block = $event->getBlockClicked()->getSide($event->getBlockFace());
		if($block->isSolid()) $event->setCancelled();
	}


	public function onDropItem(PlayerDropItemEvent $event){
		$player = $event->getPlayer();
		if(!$this->isPlaying($player)){
			$event->setCancelled();
		}
	}



	public function inArena(Player $player){
		return isset($player->arena);
	}

	public function isPlaying(Player $player){
		return isset($player->arena) && $this->arenas[$player->arena]->playing;
	}





	public function updatePlayerData($result, $callable){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if($player->getXuid() === $result["xuid"]){
				$player->data = $result;
				$callable = [$this, $callable];
				if(is_callable($callable)) $callable($player);
				break;
			}
		}
	}

	public function showStatus($player){
		$data = $player->data;
		$t = $data["totalRanking"];
		$i = $data["editionRanking"];
		$n = $this->getEditionName($player->edition);

		$content = "";
		$status = ["win", "lose", "draw", "total", "ratio"];
		foreach($status as $stat){
			$d = $stat === "ratio" ? round($data[$stat], 3) : $data[$stat];
			$content .= "§a".ucfirst($stat).": §f" . $d . "\n";
			$content .= " §7- §b(全体で§l".$t[$stat]."§r§b位、 ".$n."の中で§l".$i[$stat]."§r§b位)\n";
		}
		$content = rtrim($content, "\n");

		$form = new ModalFormInfo("§lステータス");
		$form->setContent($content);
		$form->send($player);
	}

	public function getEditionName(int $edition){
		return $edition === 0 ? "PC" : "PE";
	}



	public function showUpdateFlag($player){
		$player->showUpdateFlag = true;
	}

	public function onMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		if(isset($player->showUpdateFlag) && $player->showUpdateFlag){
			$player->showUpdateFlag = false;
			$this->showUpdate($player);
		}
	}

	public function showUpdate($player){
		$form = new ModalFormInfo("§lアップデート情報");
		$form->setContent(
			"§f・ /duel 名前  で好きな相手と対戦できるようになりました。\n\n".
			"§l快適なプレイの為、ルール等を定めさせていただきました。§r\n".
			"・引きこもること(通称:イモリ等)は禁止ではありませんが、なるべくしないようにお願い致します。\n".
			"ですが、悪質と判断された場合はご報告をお願いいたします。\n".
			"また、相手がイモっていた際の暴言もお控えください。\n".
			"・自動エントリーの仕様変更を行いました。\n".
			"PCとPE、極端に強さが違う相手とはマッチングしなくなりました。\n".
			"ですが、誰ともマッチングしない場合、お手数ですが、看板からエントリーをお願いいたします。\n".
			"\n".
			"§cYT§f申請フォーム: https://goo.gl/TbZQkp\n   §l§4(このフォーム以外では受け付けません！)§r\n".
			"§6Lobi: §fhttps://goo.gl/1Zz2BU\n".
			"§1Discord: §fhttps://discord.gg/GfBpCDd\n".
			"§bTwitter: §f@Nerahikada"
		);
		$form->send($player);
	}





	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!$sender instanceof Player) return true;

		if($label === "duel"){
			if($this->inArena($sender)){
				$sender->sendMessage("§cエントリーしているため、申し込みできません");
				return true;
			}
			$name = trim(implode(" ", $args), '"');
			$target = $this->getServer()->getPlayer($name);
			if($target === null){
				$sender->sendMessage("§c$name はオフラインです");
				return true;
			}
			if($target->getXuid() === $sender->getXuid()){
				$sender->sendMessage("§c自分自身に送ることはできません");
				return true;
			}
			$tick = $this->getServer()->getTick();
			if(!isset($sender->duelTick)) $sender->duelTick = 0;
			if($tick - $sender->duelTick < 20 * 10){
				$sender->sendMessage("§cもうしばらく間をあけてから実行してください");
				return true;
			}
			$targetName = $target->getName();
			if($this->inArena($target)){
				$sender->sendMessage("§c$targetName さんはエントリーしているため、申し込みできません");
				return true;
			}

			$sender->sendMessage("§a$targetName さんへ対戦の申し込みを行いました");
			$sender->duelTick = $tick;
			$sender->sentDuel = true;

			$pk = new ModalFormRequestPacket;
			$pk->formId = mt_rand();
			$this->duel[$pk->formId] = $sender;
			$pk->formData = json_encode([
				"type" => "modal",
				"title" => "対戦の申し込みが届きました",
				"content" => "{$sender->getName()} さんから対戦の申し込みが届きました\n参加しますか？",
				"button1" => "はい",
				"button2" => "いいえ"
			]);
			$target->dataPacket($pk);
			return true;
		}

		// Edit Inventory
		$sword = Item::get(Item::IRON_SWORD);
		$helmet = Item::get(Item::IRON_HELMET);
		if(!$sender->getInventory()->contains($sword) || $this->isPlaying($sender)){
			$sender->sendMessage("§c編集モードではないため、実行できません");
			return true;
		}

		if($label === "quit"){
			$this->setHubInventory($sender);
		}else if($label === "save"){
			Utils::asyncDB("update", $sender->getXuid(), "inventory", json_encode($sender->getInventory()->getContents()));
			$sender->sendMessage("§aインベントリを保存しました");
			$this->setHubInventory($sender);
		}else if($label === "reset"){
			Utils::asyncDB("update", $sender->getXuid(), "inventory", "");
			$sender->sendMessage("§aインベントリをリセットしました");
		}

		return true;
	}

	// Bridge
	public function setVsInventory(Player $player){
		Arena::setVsInventory($player);
	}

}