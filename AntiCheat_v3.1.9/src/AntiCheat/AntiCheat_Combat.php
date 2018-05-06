<?php

namespace AntiCheat;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;
use pocketmine\utils\UUID;
use pocketmine\Player;

class AntiCheat_Combat extends Base implements Listener{

	public function __construct(){
		$this->temporalVector = new Vector3;

		$pk = new AddPlayerPacket;
		$pk->uuid = UUID::fromRandom();
		$pk->username = "ACMan";
		$pk->entityRuntimeId = Entity::$entityCount++;
		$pk->position = null;
		$pk->item = Item::get(0);
		$pk->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 1 << Entity::DATA_FLAG_INVISIBLE],
			Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 5]
		];
		$this->addPlayerPacket = $pk;

		$pk = new SetEntityDataPacket;
		$pk->entityRuntimeId = $this->addPlayerPacket->entityRuntimeId;
		$pk->metadata = $this->addPlayerPacket->metadata;
		$pk->metadata[Entity::DATA_SCALE] = [Entity::DATA_TYPE_FLOAT, 0.5];
		$this->setEntityDataPacket = $pk;

		$pk = new MovePlayerPacket;
		$pk->entityRuntimeId = $this->addPlayerPacket->entityRuntimeId;
		$pk->position = null;
		$pk->pitch = 0;
		$pk->yaw = 0;
		$pk->headYaw = 0;
		$pk->mode = 2;
		$this->movePlayerPacket = $pk;
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$player->aimCount = 0;
		$player->hitCount = 0;
		$player->acVector = $player->asVector3();
		$player->bypass_Hitbox = false;
		/*
		$player->clickTick = $this->getServer()->getTick();
		$player->clickCount = 0;
		*/
		$this->update($player, $player);
		$pk = clone $this->addPlayerPacket;
		$pk->position = $player->acVector;
		$player->dataPacket($pk);
	}

	public function onMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		if(!$this->checkTick($player)) return;

		$after = $event->getTo();
		if($player->bypass_Hitbox){
			$y = -sin(deg2rad($after->pitch)) * 2 + $after->y;
			$xz = cos(deg2rad($after->pitch));
			$x = -$xz * sin(deg2rad($after->yaw)) * 2 + $after->x;
			$z = $xz * cos(deg2rad($after->yaw)) * 2 + $after->z;
			$this->temporalVector->setComponents($x, $y, $z);
			if($player->acVector->distanceSquared($this->temporalVector) < 1.1299){
				$player->aimCount++;
				if($player->aimCount >= 3) $this->ban($player, 'Aimbot');
				$this->delayedTask(function($player){$player->aimCount--;}, [$player], 12);
			}
		}

		$this->update($player, $after);
		$pk = $this->movePlayerPacket;
		$pk->position = $player->acVector;
		$pk->isEncoded = false;
		$player->dataPacket($pk);
	}

	public function update($player, $location){
		$x = sin(deg2rad($location->yaw)) * 2 + $location->x;
		$y = $location->y + ($player->pitch < 0 ? -1 : 2);
		$z = -cos(deg2rad($location->yaw)) * 2 + $location->z;
		$player->acVector->setComponents($x, $y, $z);
	}


	public function onReceive(DataPacketReceiveEvent $event){
		$pk = $event->getPacket();
		if($pk::NETWORK_ID === 0x1e){
			if($pk->transactionType === 3){
				if($pk->trData->entityRuntimeId === $this->addPlayerPacket->entityRuntimeId){
					$player = $event->getPlayer();
					if(!$this->checkTick($player)) return;

					if(!$player->bypass_Hitbox){
						$pk = $this->setEntityDataPacket;
						$pk->isEncoded = false;
						$player->dataPacket($pk);
						$player->bypass_Hitbox = true;
						return;
					}
					$player->hitCount++;
					$this->delayedTask(function($player){$player->hitCount--;}, [$player], 20 * 20);
					if($player->hitCount >= 10) $this->ban($event->getPlayer(), 'Hitbox');
				}
			}
		}
		/*
		}else if($pk::NETWORK_ID === 0x18 && $pk->sound === 42){
			$player = $event->getPlayer();
			$tick = $this->getServer()->getTick();
			if($player->clickTick + 1 > $tick) $event->setCancelled();
			else $player->clickTick = $tick;
		}
		*/
	}



	public function onDamage(EntityDamageEvent $event){
		if($event->isCancelled()) return;
		if($event->getCause() === 1){
			$damager = $event->getDamager();
			$entity = $event->getEntity();
			if($damager instanceof Player && $entity instanceof Player){
				$reach = ($damager->x - $entity->x)**2 + ($damager->y - $entity->y)**2 + ($damager->z - $entity->z)**2;
				if($reach > 10.5) $event->setCancelled();
			}
		}
	}

}