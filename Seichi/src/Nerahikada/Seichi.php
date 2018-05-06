<?php

namespace Nerahikada;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Listener;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\Player;
use pocketmine\Server;

use Nerahikada\SpecialTools\SpecialTools;

class Seichi extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new TipsTask($this), 20 * 60 * 5);
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();

		$player->resetTitles();
		$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$player, "addTitle"], ["§l§6整地", "§k|||§r §7Uncharted Server§r §k|||"]), 40);
		$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$player, "removeTitles"], []), 200);
		$player->sendTip(" ");
		$player->sendPopup(" ");
		$player->sendMessage("§k|||§r §7Uncharted Server§r §k|||");
		$player->sendMessage("§fアンチャーテッドサーバー §7へようこそ");

		$player->namedtag->setByte("nv", $player->namedtag->getByte("nv", false));

		$giveItem = [SpecialTools::MINE_ALL, SpecialTools::CUT_ALL, SpecialTools::DIG_ALL];
		foreach($player->getInventory()->getContents() as $item){
			$id = SpecialTools::getSpecialId($item);
			if($id !== false) unset($giveItem[$id]);
		}
		foreach($giveItem as $give){
			$item = SpecialTools::get($give);
			if($item instanceof Durable) $item->setUnbreakable(true);
			$player->getInventory()->addItem($item);
		}

		if(!$player->hasPlayedBefore()){
			$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$player, "sendMessage"], ["§r§l§6初回ログインボーナス！§r §f>> §aストレージボックスを §21個 §a手に入れました！"]), 40);
			$player->getInventory()->addItem(SpecialTools::get(SpecialTools::STORAGE_BOX));
		}
	}

	public function onHeld(PlayerItemHeldEvent $event){
		if($event->isCancelled()) return;

		$player = $event->getPlayer();
		$item = $event->getItem();

		if(SpecialTools::isSpecialTool($item) && $item instanceof Tool)
			$player->addEffect(new EffectInstance(Effect::getEffect(Effect::HASTE), INT32_MAX, 0, false));
		else $player->removeEffect(Effect::HASTE);

		if($player->namedtag->getByte("nv", false))
			$player->addEffect(new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), INT32_MAX, 0, false));
		else $player->removeEffect(Effect::NIGHT_VISION);
	}

	public function onExhaust(PlayerExhaustEvent $event){
		$event->setCancelled();
	}

	public function onDeath(PlayerDeathEvent $event){
		$event->setDeathMessage("");
		$event->setKeepInventory(true);
	}

	public function onBucketEmpty(PlayerBucketEmptyEvent $event){
		$block = $event->getBlockClicked()->getSide($event->getBlockFace());
		if($block->y > 10){
			$event->setCancelled();
			$event->getPlayer()->sendMessage("§c使用できません");
		}
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!$sender instanceof Player) return true;

		if($label === "sb"){
			$inventory = $sender->getInventory();
			$need = Item::get(Item::CHEST, 0, 8);

	//======  BaseInventory -> contains  =======================================================
			$count = max(1, $need->getCount());
			foreach($inventory->getContents() as $i){
				if($need->equals($i)){
					$count -= $i->getCount();
					if($count <= 0){
						$result = true;
					}
				}
			}
			if(!isset($result)) $result = false;
	//==========================================================================================

			if($result){

	//======  BaseInventory -> removeItem  =====================================================
				$itemSlots[] = clone $need;
				for($i = 0, $size = $inventory->getSize(); $i < $size; ++$i){
					$item = $inventory->getItem($i);
					if($item->isNull()) continue;
					foreach($itemSlots as $index => $slot){
						if($slot->equals($item)){
							$amount = min($item->getCount(), $slot->getCount());
							$slot->setCount($slot->getCount() - $amount);
							$item->setCount($item->getCount() - $amount);
							$inventory->setItem($i, $item);
							if($slot->getCount() <= 0){
								unset($itemSlots[$index]);
							}
						}
					}
					if(count($itemSlots) === 0) break;
				}
	//==========================================================================================

	//======  BaseInventory -> canAddItem  =====================================================
				$result = false;

				$item = SpecialTools::get(SpecialTools::STORAGE_BOX);
				$checkDamage = !$item->hasAnyDamageValue();
				$checkTags = $item->hasCompoundTag();
				for($i = 0, $size = $inventory->getSize(); $i < $size; ++$i){
					$slot = $inventory->getItem($i);
					if($item->equals($slot, $checkDamage, $checkTags)){
						if(($diff = $slot->getMaxStackSize() - $slot->getCount()) > 0){
							$item->setCount($item->getCount() - $diff);
						}
					}elseif($slot->isNull()){
						$item->setCount($item->getCount() - $inventory->getMaxStackSize());
					}

					if($item->getCount() <= 0){
						$result = true;
					}
				}
	//==========================================================================================

				if($result){
					$inventory->addItem(SpecialTools::get(SpecialTools::STORAGE_BOX));
					$sender->sendMessage("§aストレージボックスを手に入れました！");
				}else{
					$sender->sendMessage("§cインベントリに空きがありません！");
				}
			}else{
				$sender->sendMessage("§cチェストが8個必要です！");
			}
		}else if($label === "nv"){
			$nv = (bool) $sender->namedtag->getByte("nv");
			$sender->namedtag->setByte("nv", (int) !$nv);
			$sender->sendMessage("§a暗視を §2".(!$nv ? "ON" : "OFF")." §aにしました");
		}
		
		return true;
	}

	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$message = strtolower($event->getMessage());
		if($message === "/tp"){
			$pk = new ModalFormRequestPacket;
			$pk->formId = 1;
			$data = [
				"type" => "custom_form",
				"title" => "テレポート メニュー",
				"content" => [
					[
						"type" => "dropdown",
						"text" => "テレポート先を選択してください",
						"options" => ["初期スポーン"],
						"defaultOptionIndex" => 0
					]
				]
			];
			foreach($this->getServer()->getOnlinePlayers() as $p){
				if($player->getName() === $p->getName()) continue;
				$data["content"][0]["options"][] = $p->getName();
			}
			$pk->formData = json_encode($data);
			$player->dataPacket($pk);
			$player->dropdown = $data["content"][0]["options"];

			$event->setCancelled();
		}
	}

	public function onReceive(DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		if($packet::NETWORK_ID === ProtocolInfo::MODAL_FORM_RESPONSE_PACKET && $packet->formId === 1 && isset($player->dropdown)){
			$to = json_decode($packet->formData, true)[0];
			if($to === 0){
				$player->teleport($player->getLevel()->getSafeSpawn());
			}else{
				$target = $this->getServer()->getPlayer($player->dropdown[$to]);
				$player->teleport($target, $target->yaw, $target->pitch);
			}
			$player->sendMessage("§2".$player->dropdown[$to]." §aへテレポートしました");

			unset($player->dropdown);
		}
	}

}



class TipsTask extends PluginTask{

	public function __construct(PluginBase $owner){
		parent::__construct($owner);
	}

	public function onRun(int $currentTick){
		$server = Server::getInstance();
		if(count($server->getOnlinePlayers()) === 0) return;
		$tips = [
			"ストレージボックスでアイテムが拾えない時は、インベントリが満タンになってないか確認してみてください",
			"ストレージボックスは /sb コマンドで作成できます (チェストが8個必要です)",
			"ストレージボックスは置くことにより使用できます",
			"リスポーン地点周辺では、アイテムは使用できません",
			"液体(水、マグマ)は y10以下でのみ使用できます",
			"暗視は /nv コマンドで切り替えが可能です",
			"/tp コマンドでテレポートメニューが開きます",
			"ドロップしたアイテムは5秒で消えます",
		];
		$server->broadcastMessage("§bTips>> §f".$tips[mt_rand(0, 7)]);
	}

}