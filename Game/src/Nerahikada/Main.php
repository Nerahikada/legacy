<?php

namespace Nerahikada;

use pocketmine\entity\Effect;
use pocketmine\event\block\ItemFrameDropItemEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

	/* Management */
	public $server;
	public $scheduler;
	/* Level */
	public $lobby;
	public $world;
	/* Game */
	const TIME_WAIT = 60 * 1;
	const TIME_GAME = 60 * 6;
	public $players = [];
	public $playing = false;
	public $gameCount = 0;


	public function onEnable(){
		$this->server = $this->getServer();
		$this->scheduler = $this->server->getScheduler();
		$this->logger = $this->getLogger();

		$this->sign = new Sign($this);

		$this->server->getPluginManager()->registerEvents($this, $this);

		$this->server->loadLevel("Waitinglobby2");
		$this->server->loadLevel("hub");
		$this->lobby = new Position(368.5, 38, -872.5, $this->server->getLevelByName("Waitinglobby2"));
		$this->world = new Position(1.5, 21, 0.5, $this->server->getLevelByName("hub"));

		$this->db = new \mysqli('localhost', 'user', 'password', 'database');

		$this->game_wait();
	}




	public function game_wait($time = self::TIME_WAIT){
		$count = count($this->players);
		if(!empty($count)){
			$need = 2;
			if($count < $need){
				$this->server->broadcastTip("人数が揃うまでお待ちください。 あと§l§6".($need - $count)."§r§f人", $this->players);
				$time = self::TIME_WAIT;
			}else{
				$this->server->broadcastTip("ゲーム開始まで: §l§6".$time."§r§f秒", $this->players);
				if($time <= 0){
					$this->scheduler->scheduleDelayedTask(new CallbackTask([$this, "game_start"], [$time]), 1);
					return;
				}else if($time <= 5){
					$packet = new LevelEventPacket;
					$packet->evid = LevelEventPacket::EVENT_SOUND_CLICK;
					$packet->data = 0;
					foreach($this->players as $player){
						$pk = clone $packet;
						$pk->x = $player->x;
						$pk->y = $player->y;
						$pk->z = $player->z;
						$player->dataPacket($pk);
					}
				}
				$time--;
			}
		}
		$this->scheduler->scheduleDelayedTask(new CallbackTask([$this, "game_wait"], [$time]), 20);
	}


	public function game_start(){
		$this->logger->info("§a>> §fゲームが開始されました。");
		$this->playing = true;
		$this->gameCount++;
		$this->sign->sendAll(Sign::CLOSE);
		foreach($this->players as $player){
			// ステータス非表示
			$player->status->despawn();
			// 他のプレイヤーを非表示に
			foreach($this->players as $player2) $player->hidePlayer($player2);
			// テレポート
			$player->teleport($this->world);
			// Title
			new Title($this, $player, "§6GAME START!");
			//　しっぽ
			$player->tail = new Tail($this, $player);
			$player->catchTail = 0;
			// データベース
			$this->add("game", $player);
		}
		$this->game_count();
	}


	public function game_count($time = self::TIME_GAME){
		$this->server->broadcastTip("ゲーム終了まで: §l§6".$time."§r§f秒", $this->players);
		if($time <= 0 || count($this->players) <= 1){
			$this->scheduler->scheduleDelayedTask(new CallbackTask([$this, "game_reset"], []), 1);
			return;
		}

		// GAME EVENT
		if($time === self::TIME_GAME - 30){
			// しっぽ召喚
			foreach($this->players as $player) $player->tail->spawn();
			$this->broadcastMessage("§a>> §fプレイヤーとしっぽが出現しました！", $this->players);
		}else if($time === 60){
			foreach($this->players as $player) $player->tail->showNameTag();
			//$this->broadcastMessage("§a>> §f残り60秒になったのでネームタグが見えるようになりました！", $this->players);
		}


		$time--;
		$this->scheduler->scheduleDelayedTask(new CallbackTask([$this, "game_count"], [$time]), 20);
	}


	public function game_reset(){
		if(!$this->playing) return;
		$this->playing = false;

		$this->broadcastMessage("§a>> §fゲーム終了！", $this->players);
		$tails = [];
		foreach($this->players as $p) if(!$p->tail->removed) $tails[] = $p->tail;
		if(count($tails) === 0){
			$this->broadcastMessage("§c>> §fゲームが強制終了しました。");
			$force = true;
		}else if(count($tails) > 1){
			$this->broadcastMessage("§6>> §f引き分け！", $this->players);
			foreach($tails as $tail){
				$this->add("draw", $tail->player);
				$this->add("money", $tail->player);
			}
		}else{
			$this->broadcastMessage("§6>> §f勝者: §a".$tails[0]->player->getName(), $this->players);
			$this->add("win", $tails[0]->player);
			$this->add("money", $tails[0]->player, mt_rand(8, 10));
		}

		if(!isset($force)){
			foreach($this->players as $player) $count[] = ["count" => $player->catchTail, "player" => $player];
			array_multisort(array_column($count, 'count'), SORT_DESC, SORT_NUMERIC, $count);
			if($count[0]["count"] > 1){
				$this->broadcastMessage("§6>> §f今回のゲームで一番しっぽを取った人は §6".$count[0]["player"]->getName()."§r §fさんです！", $this->players);
				$this->broadcastMessage("§6>> §f取った回数: ".$count[0]["count"], $this->players);
				$this->add("money", $tails[0]->player, mt_rand(15, 20));
			}
		}


		foreach($this->players as $player){
			$player->tail->remove();
			unset($player->tail);
			foreach($this->players as $player2) $player->showPlayer($player2);
			$player->removeAllEffects();
			$player->teleport($this->lobby);
			$player->status->update();
			$player->status->spawn();
		}
		$this->sign->sendAll(Sign::OPEN);
		$this->players = [];

		$this->game_wait();
	}





	public function onInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		if($player->getGamemode() !== 1) $event->setCancelled();
		if($this->sign->isSign($event->getBlock())){
			$name = $player->getName();
			if(!$this->playing && !isset($this->players[$name])){
				$this->sign->send($player, Sign::JOINED);
				$this->players[$name] = $player;
				$player->sendMessage("§a>> §fゲームにエントリーしました。");
			}
		}
	}



	public function onMove(PlayerMoveEvent $event){
		if(!$this->playing) return;
		$player = $event->getPlayer();
		if(!isset($player->tail)) return;
		$player->tail->move();
	}

	public function onSneak(PlayerToggleSneakEvent $event){
		if(!$this->playing) return;
		$player = $event->getPlayer();
		if(!isset($player->tail)) return;
		$this->scheduler->scheduleDelayedTask(new CallbackTask([$player->tail, "move"], []), 1);
	}



	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		if($packet instanceof InteractPacket && $packet->action === InteractPacket::ACTION_LEFT_CLICK){
			$player = $event->getPlayer();
			if(isset($player->tail) && !$player->tail->removed){
				foreach($this->players as $target){
					$tail = $target->tail;
					if($tail->eid === $packet->target && !$tail->removed){
						// Killaura CHECK!!!
						if($target === $player){
							$player->kick("§l§4Killauraが検出されました。", false);
							return;
						}
						// Oops!
						if($player->getDeviceOS() === 7 && mt_rand(0, 5) !== 0) return;
						// x,z の距離がほぼ0に近い = 上から
						$d2 = ($player->x - $tail->pos->x)**2 + ($player->z - $tail->pos->z)**2;
						if($d2 < 0.5) return;

						if($tail->preAttack($player, $d2)){
							$this->broadcastMessage("§4>> §6".$player->getName()."§fが§6".$target->getName()."§fのしっぽを取りました！", $this->players);
							$target->addTitle("§l§4GAME OVER!", "観戦モードになりました", 10, 40, 10);
							foreach($this->players as $p) $p->hidePlayer($target);
							$effect = Effect::getEffect(1);
							$effect->setDuration(PHP_INT_MAX);
							$effect->setAmplifier(0);
							$effect->setVisible(false);
							$target->addEffect($effect);
							$this->add("catch", $player);
							$player->catchTail++;
							$this->add("money", $player, mt_rand(5, 10));
							$this->add("lose", $target);
						}else{
							echo "Cheat Checker BETA: ".$player->getName()."\n";
							//$player->cheatCount++;
							//if($player->cheatCount > 5) $player->kick("§l§4チートの使用が検出されました。", false);
						}
					}
				}
				$this->checkGame();
			}
		}
	}



	public function checkGame(){
		$tails = [];
		foreach($this->players as $player) if(!$player->tail->removed) $tails[] = $player->tail;
		if(count($tails) <= 1) $this->scheduler->scheduleDelayedTask(new CallbackTask([$this, "game_reset"], []), 1);
	}








	public function onLogin(PlayerLoginEvent $event){
		$event->getPlayer()->teleport($this->lobby);
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();

		$this->alwaysMorning();

		if($this->playing) $sign = Sign::CLOSE;
		else $sign = Sign::OPEN;
		$this->sign->send($player, $sign);

		$player->sendTip("");
		$player->sendPopup("");
		$player->removeTitles();

		$this->scheduler->scheduleDelayedTask(new CallbackTask([$player, "addTitle"], ["§l§9Sorrow §7Server", "", 10, 40, 10]), 20);

		$name = $player->getName();
		$result = $this->db->query("SELECT * FROM data WHERE name = '$name'");
		if($result->num_rows === 0) $this->db->query("INSERT INTO data VALUES('$name', 0, 0, 0, 0, 0, 0)");

		$player->status = new Status($this, $player);

		$player->cheatCount = 0;
	}

	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		$player->despawnFromAll();
		$name = $player->getName();
		if(isset($this->players[$name])){
			unset($this->players[$name]);
			if(isset($player->tail) && !$player->tail->removed){
				$player->tail->remove();
				$this->add("lose", $player);
				$this->checkGame();
			}
		}
	}


	public function onDamage(EntityDamageEvent $event){
		$event->setCancelled();
	}

	public function onLeavesDecay(LeavesDecayEvent $event){
		$event->setCancelled();
	}

	public function onItemFrameDropItem(ItemFrameDropItemEvent $event){
		if($event->getPlayer()->getGamemode() !== 1) $event->setCancelled();
	}



	public function alwaysMorning(){
		foreach($this->server->getLevels() as $level){
			$level->checkTime();
			$level->setTime(6000);
			$level->checkTime();
			$level->stopTime();
			$level->checkTime();
		}
	}




	public function get($column, $player){
		$name = $player->getName();
		$result = $this->db->query("SELECT * FROM data WHERE name = '$name'");
		if($result->num_rows === 0) return false;
		$row = $result->fetch_assoc();
		if($column === "all") return $row;
		return $row[$column];
	}


	public function add($column, $player, $value = 1, $n = true){
		$name = $player->getName();
		$now = $this->get($column, $player);
		if($now === false) return false;
		if(($now + $value) < 0) $this->db->query("UPDATE data SET $column = 0 WHERE name = '$name'");
		else $this->db->query("UPDATE data SET $column = $column + $value WHERE name = '$name'");
		if($column === "money" && $n) $player->sendMessage("§a>> §f".$value." Sr. を入手しました。");
	}




	public function broadcastMessage($message, $players){
		$this->logger->info($message);
		$this->server->broadcastMessage($message, $players);
	}


}