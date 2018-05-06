<?php

namespace Copy;

use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class Copy extends PluginBase implements Listener{

	public function onEnable(){
		$this->Server = Server::getInstance();
		$this->Server->getPluginManager()->registerEvents($this, $this);

		$this->task = [];
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
		if(!$sender->isOp()) return false;
		$name = $sender->getName();
		$cmd = strtolower(array_shift($args));
		switch($cmd){
			case "1":
				$this->status[$name] = 1;
				$sender->sendMessage("Touch Pos1.");
			break;
			case "2":
				$this->status[$name] = 2;
				$sender->sendMessage("Touch Pos2.");
			break;
			case "3":
				$this->status[$name] = 3;
				$sender->sendMessage("Touch Pos3.");
			break;
			case "nc":
				$this->task[] = 0;
				end($this->task);
				$id = key($this->task);
				$sender->sendMessage("Normal Copy...  ID:".$id);
				$sx = min($this->pos[$name][0]["x"], $this->pos[$name][1]["x"]);
				$ex = max($this->pos[$name][0]["x"], $this->pos[$name][1]["x"]);
				$sz = min($this->pos[$name][0]["z"], $this->pos[$name][1]["z"]);
				$ez = max($this->pos[$name][0]["z"], $this->pos[$name][1]["z"]);
				$sy = min($this->pos[$name][0]["y"], $this->pos[$name][1]["y"]);
				$ey = max($this->pos[$name][0]["y"], $this->pos[$name][1]["y"]);
				$num = ($ex-$sx+1)*($ey-$sy+1)*($ez-$sz+1);
				$text = new TextPacket();
				$text->type = TextPacket::TYPE_POPUP;
				$this->normalCopy(Server::getInstance()->getScheduler(), $id, $sender, $sender->getLevel(), $this->pos[$name], [$sx, $ex, $sz, $ez, $sy, $ey, $num], $text);
return true;
				for($x=$sx; $x<=$ex; $x++){
					for($z=$sz; $z<=$ez; $z++){
						for($y=$sy; $y<=$ey; $y++){
							$v = new Vector3($x, $y, $z);
							$block = $level->getBlock($v);
							if($block->getId() !== 0){//ここ
								$v2 = new Vector3($this->pos[$name][2]["x"]+($x-$sx), $this->pos[$name][2]["y"]+($y-$sy), $this->pos[$name][2]["z"]+($z-$sz));
								$level->setBlock($v2, clone $block);
							}//ここ
							$no ++;
							$percent = $no / $num * 100;
							$text->message = "§a".$no."/".$num." - ".$percent."\%";
							$server->broadcastPacket($ps, $text);
						}
					}
				}
				$sender->sendMessage("Complete!");
			break;
			case "rc":
				$sender->sendMessage("Reversal Copy...");
				$sx = min($this->pos[$name][0]["x"], $this->pos[$name][1]["x"]);
				$ex = max($this->pos[$name][0]["x"], $this->pos[$name][1]["x"]);
				$sz = min($this->pos[$name][0]["z"], $this->pos[$name][1]["z"]);
				$ez = max($this->pos[$name][0]["z"], $this->pos[$name][1]["z"]);
				$sy = min($this->pos[$name][0]["y"], $this->pos[$name][1]["y"]);
				$ey = max($this->pos[$name][0]["y"], $this->pos[$name][1]["y"]);
				$num = ($ex-$sx+1)*($ey-$sy+1)*($ez-$sz+1);
				$no = 0;
				$text = new TextPacket();
				$text->type = TextPacket::TYPE_POPUP;
				$ps = $this->Server->getOnlinePlayers();
				$level = $sender->getLevel();
				$server = Server::getInstance();
				for($x=$sx; $x<=$ex; $x++){
					for($z=$sz; $z<=$ez; $z++){
						for($y=$sy; $y<=$ey; $y++){
							$v = new Vector3($x, $y, $z);
							$v2 = new Vector3($this->pos[$name][2]["x"]-($x-$sx), $this->pos[$name][2]["y"]+($y-$sy), $this->pos[$name][2]["z"]-($z-$sz));
							//様々なブロックを回転
							$b = $level->getBlock($v);
							$id = $b->getId();
							if($id !== 0){//ここ
								$meta = $b->getDamage();
								switch($id){
								//階段
								case 67:
								case 53:
								case 134:
								case 135:
								case 136:
								case 163:
								case 164:
								case 108:
								case 128:
								case 180:
								case 109:
								case 114:
								case 156:
									switch($meta){
										case 0:
											$meta = 1;
											break;
										case 1:
											$meta = 0;
											break;
										case 2:
											$meta = 3;
											break;
										case 3:
											$meta = 2;
											break;
										case 4:
											$meta = 5;
											break;
										case 5:
											$meta = 4;
											break;
										case 6:
											$meta = 7;
											break;
										case 7:
											$meta = 6;
											break;
									}
									break;
								//松明、レッドストーントーチ
								case 50:
								case 76:
									switch($meta){
										case 1:
											$meta = 2;
											break;
										case 2:
											$meta = 1;
											break;
										case 3:
											$meta = 4;
											break;
										case 4:
											$meta = 3;
											break;
									}
									break;
								//レバー（上向き）
								case 64:
								switch($meta){
										case 1:
											$meta = 2;
											break;
										case 2:
											$meta = 1;
											break;
										case 3:
											$meta = 4;
											break;
										case 4:
											$meta = 3;
											break;
									}
									break;
								//レバー（下向き)
								case 69:
									switch($meta){
										case 9:
											$meta = 10;
											break;
										case 10:
											$meta = 9;
											break;
										case 11:
											$meta = 12;
											break;
										case 12:
											$meta = 11;
											break;
									}
								break;
								//ハシゴ
								case 65:
									switch($meta){
										case 2:
											$meta = 3;
											break;
										case 3:
											$meta = 2;
											break;
										case 4:
											$meta = 5;
											break;
										case 5:
											$meta = 4;
											break;
									}
								break;
								//蔦
								case 106:
									switch($meta){
										case 1:
											$meta = 4;
											break;
										case 4:
											$meta = 1;
											break;
										case 2:
											$meta = 8;
											break;
										case 8:
											$meta = 2;
											break;
									}
								break;
								//トラップドア（木、鉄）
								case 96:
								case 167:
									switch($meta){
										case 0:
											$meta = 1;
											break;
										case 1:
											$meta = 0;
											break;
										case 2:
											$meta = 3;
											break;
										case 3:
											$meta = 2;
											break;
										case 4:
											$meta = 5;
											break;
										case 5:
											$meta = 4;
											break;
										case 6:
											$meta = 7;
											break;
										case 7:
											$meta = 6;
											break;
										case 8:
											$meta = 9;
											break;
										case 9:
											$meta = 8;
											break;
										case 10:
											$meta = 11;
											break;
										case 11:
											$meta = 10;
											break;
										case 12:
											$meta = 13;
											break;
										case 13:
											$meta = 12;
											break;
										case 14:
											$meta  = 15;
											break;
										case 15:
											$meta = 14;
											break;
									}
								break;
								//額縁
								case 199:
									switch($meta){
										case 0:
											$meta = 1;
											break;
										case 1:
											$meta = 0;
											break;
										case 2:
											$meta = 3;
											break;
										case 3:
											$meta = 2;
											break;
									}
								break;
								}
								$block = Block::get($id, $meta);
								$level->setBlock($v2, $block);
							}//ここ
							$no ++;
							$percent = $no / $num * 100;
							$text->message = "§a".$no."/".$num." - ".$percent."%";
							$server->broadcastPacket($ps, $text);
						}
					}
				}
				$sender->sendMessage("Complete!");
			break;
			default:
				$sender->sendMessage("Fault.");
				return true;
			break;
		}
		return true;
	}

	public function onTouch(PlayerInteractEvent $event){
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$name = $player->getName();
		if(!isset($this->status[$name])) return;
		switch($this->status[$name]){
			case "1":
				$this->status[$name] = 0;
				$this->pos[$name][0]["x"] = $block->x;
				$this->pos[$name][0]["y"] = $block->y;
				$this->pos[$name][0]["z"] = $block->z;
				$player->sendMessage("Pos1 ok.");
			break;
			case "2":
				$this->status[$name] = 0;
				$this->pos[$name][1]["x"] = $block->x;
				$this->pos[$name][1]["y"] = $block->y;
				$this->pos[$name][1]["z"] = $block->z;
				$player->sendMessage("Pos2 ok.");
			break;
			case "3":
				$this->status[$name] = 0;
				$this->pos[$name][2]["x"] = $block->x;
				$this->pos[$name][2]["y"] = $block->y;
				$this->pos[$name][2]["z"] = $block->z;
				$player->sendMessage("Pos3 ok.");
			break;
		}
	}


	public function normalCopy($s, $id, $player, $level, $pos, $data, $pk, $i = 0, $j = 0, $k = 0, $count = 0){
		$sx = $data[0]; $ex = $data[1];
		$sz = $data[2]; $ez = $data[3];
		$sy = $data[4]; $ey = $data[5];
		$num = $data[6];
		for($n = 0; $n < 100; $n++){
			$text = clone $pk;
			$i++;
			$x = $sx + $i;
			$z = $sz + $j;
			$y = $sy + $k;
			if($x > $ex){
				$i = 0;
				$x = $sx + $i;
				$j++;
				$z = $sz + $j;
				if($z > $ez){
					$j = 0;
					$z = $sz + $j;
					$k++;
					$y = $sy + $k;
					if($y > $ey){
						$player->sendMessage("ID:".$id." Complete!");
						unset($this->task[$id]);
						return;
					}
				}
			}
			$block = $level->getBlock(new Vector3($x, $y, $z), false);
			$v2 = new Vector3($pos[2]["x"]+($x-$sx), $pos[2]["y"]+($y-$sy), $pos[2]["z"]+($z-$sz));
			$level->setBlock($v2, clone $block);
			$count++;
			$percent = $count / $num * 100;
			$this->task[$id] = [$count, $num, $percent];
			$msg = "";
			foreach($this->task as $task){
				$msg .= $id.": ".$task[0]."/".$task[1]." - ".$task[2]."\n";
			}
			$text->message = $msg;
			$player->dataPacket($text);
		}
		$s->scheduleDelayedTask(new CallbackTask([$this, "normalCopy"], [$s, $id, $player, $level, $pos, $data, $pk, $i, $j, $k, $count]), 1);
	}

}



use pocketmine\scheduler\Task;

class CallbackTask extends Task{

	public function __construct(callable $callable, array $args = []){
		$this->callable = $callable;
		$this->args = $args;
	}

	public function onRun(int $tick){
		$c = $this->callable;
		$c(...$this->args);
	}

}