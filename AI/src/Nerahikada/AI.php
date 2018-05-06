<?php

namespace Nerahikada;

use pocketmine\block\Fence;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\MoveEntityPacket;
use pocketmine\plugin\PluginBase;

class AI extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->AI = new \stdClass;
		$this->AI->eid = Entity::$entityCount++;
		$this->AI->pos = new Vector3(256, 5, 256);
		$this->AI->yaw = 0;
		$this->AI->pitch = 0;
		//test
		$this->AI->speed = 0.1;

		$this->players = [];

		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, 'AI'], []), 1);
	}

	public function AI($target = null){
		if(!isset($this->target)){
			$players = $this->players;

			if(count($players) === 0) return;

			//AIから最も近い場所にいるプレイヤーを求める
			$p1 = $this->AI->pos;
			foreach($players as $player){
				$p2 = $player; //分かりやすくするため
				$temp = sqrt(($p2->x - $p1->x)**2 + ($p2->y - $p1->y)**2 + ($p2->z - $p1->z)**2);
				if(!isset($distance)){
					$distance = $temp;
					$target = $player;
				}else{
					if($temp < $distance){
						$distance = $temp;
						$target = $player;
					}
				}
			}
			unset($temp);
		}else{
			$p1 = $this->AI->pos;
			$target = clone $this->target;
			$p2 = $target;
			$distance = sqrt(($p2->x - $p1->x)**2 + ($p2->y - $p1->y)**2 + ($p2->z - $p1->z)**2);
			unset($this->target);
		}

		//追尾
		$p2 = $target;
		$yaw = atan2($p2->x - $p1->x, $p2->z - $p1->z) * 180 / M_PI * -1;
		$pitch = atan2($p2->y - $p1->y, $distance) * 180 / M_PI * -1;

		$beforePos = clone $this->AI->pos;

		$this->AI->pos->x += -sin($yaw/180*M_PI)*cos($pitch/180*M_PI) * $this->AI->speed;
		/*$this->AI->pos->y += -sin($pitch/180*M_PI) * $this->AI->speed;*/
		$this->AI->pos->z += cos($yaw/180*M_PI)*cos($pitch/180*M_PI) * $this->AI->speed;
		$this->AI->yaw = $yaw;
		$this->AI->pitch = $pitch;

		//がくがく対策
		$twoDistance = sqrt(($p2->x - $p1->x)**2 + ($p2->z - $p1->z)**2);
		if($twoDistance < 0.5) $this->AI->pos = $beforePos;

		//ブロックチェック
		$level = $target->getLevel();
		//埋まってる？
		$temp = clone $this->AI->pos;
		$block = $level->getBlock($temp);
		$temp2 = clone $this->AI->pos;
		$temp2->y += 1;
		$block2 = $level->getBlock($temp2);
		if($block->isSolid() || $block2->isSolid()){
			if($block2->isSolid() || $block instanceof Fence){
				//とりあえずもとに戻しておこう
				$this->AI->pos = $beforePos;
			}else{
				//ジャンプ…
				$this->AI->pos->y++;
			}
		}
		//浮いてる？
		$temp = clone $this->AI->pos;
		for($i = 0; $i < 50; $i++){
			$temp->y -= 0.01;
			if(!$level->getBlock($temp)->isSolid()){
				//ならyを下げよう。
				$this->AI->pos->y -= 0.01;
			}else break;
		}

		$pk = new MoveEntityPacket();
		$pk->entityRuntimeId = $this->AI->eid;
		$pk->x = $this->AI->pos->x;
		$pk->y = $this->AI->pos->y;
		$pk->z = $this->AI->pos->z;
		$pk->yaw = $this->AI->yaw;
		$pk->headYaw = $this->AI->yaw;
		$pk->pitch = $this->AI->pitch;

		$this->getServer()->broadcastPacket($players, $pk);

		//ダメージ
		/*if($distance < 0.5){
			$target->setMotion(new Vector3(-sin($yaw/180*M_PI)*cos($pitch/180*M_PI)*0.5, 0.2, cos($yaw/180*M_PI)*cos($pitch/180*M_PI)*0.5));
			$ev = new EntityDamageEvent($target, 1, 4);
			$target->attack($ev->getFinalDamage(), $ev);
		}*/
	}


	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$this->players[$player->getName()] = $player;

		$pk = new AddEntityPacket();
		$pk->entityRuntimeId = $this->AI->eid;
		$pk->type = 32; //Zombie
		$pk->x = $this->AI->pos->x;
		$pk->y = $this->AI->pos->y;
		$pk->z = $this->AI->pos->z;
		$pk->yaw = $this->AI->yaw;
		$pk->pitch = $this->AI->pitch;
		$player->dataPacket($pk);
	}

	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		unset($this->players[$player->getName()]);
	}


	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
		$msg = $event->getMessage();
		if(strpos($msg, ".sp ") === 0){
			$msg = str_replace(".sp ", "", $msg);
			$msg = $msg * 0.1;
			$this->AI->speed = $msg;
		}
	}

}