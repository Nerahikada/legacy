<?php

namespace Nerahikada;

use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class LoginSystem extends PluginBase implements Listener{

	public function onEnable(){
		$this->DB = new \mysqli('localhost', 'user', 'password', 'database');

		$this->Server = \pocketmine\Server::getInstance();
		$this->Server->getPluginManager()->registerEvents($this, $this);
	}

	public function onPreLogin(PlayerPreLoginEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		//ClientIdCheck
		$cid = $player->getClientId();
		$cnt = strlen($cid);
		if($cid == 0 || $cid == 9223372036854775807 || $cnt < 9 || $cnt > 20 || !is_int($cid)){
			$this->getLogger()->warning("ClientID Cheker: $name> $cid");
			//$player->kick("ClientIDが不正な値のため、ログインできません。", false);
			//$event->setCancelled();
		}
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		$result = $this->DB->query("SELECT * FROM loginsystem WHERE name = '{$name}'");
		if($result->num_rows === 0){
			$player->isLogin = false;
			$player->step = "register-type-pass";
			$this->Server->getScheduler()->scheduleDelayedTask(new CallbackTask([$player, "sendMessage"], ["§dRegister§f>> §6パスワード登録§fをする必要があります。\n§dRegister§f>> パスワードを§6そのまま§f入力してください。\n§dRegister§f>> §l§c警告！ §e他人に推測されやすい簡単なパスワードを使用しないでください！\n - アカウントを乗っ取られても当サーバーは一切の責任を問いません！"]), 1);
		}else{
			$player->isLogin = true;
			$row = $result->fetch_assoc();
			$player->pass = $row["pass"];
			if($player->getAddress() != $row["ip"] || $player->getClientId() != $row["cid"]){
				$player->isLogin = false;
				$player->step = "login-type-pass";
				$this->Server->getScheduler()->scheduleDelayedTask(new CallbackTask([$player, "sendMessage"], ["§dLogin§f>> パスワードを§6入力§fしてください。"]), 1);
			}
		}
		$result->free();
	}

	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		$msg = $event->getMessage();
		if(!$player->isLogin){
			$event->setCancelled();
			//もしかしたら最初に / を使いたい人がいるかもしれない。
			//MCPEの仕様上無理だった。
			if($msg[0] === "/"){
				$player->sendMessage("§cError§f>> コマンドは使用できません。");
				return;
			}
			$ip = $player->getAddress();
			$cid = $player->getClientId();
			switch($player->step){
				case "register-type-pass":
					$player->firstPass = $msg;
					$player->sendMessage("§dRegister§f>> 確認のため、もう一度パスワードを入力してください。");
					$player->step = "register-re-type-pass";
					break;

				case "register-re-type-pass":
					if($player->firstPass === $msg){
						$pass = $this->DB->real_escape_string($msg);
						$this->DB->query("INSERT INTO loginsystem VALUES('$name', '$ip', '$cid', '$pass')");
						$player->sendMessage("§dRegister§f>> §a登録が完了しました！");
						$player->pass = $msg;
						$player->isLogin = true;
					}else{
						$player->sendMessage("§dRegister§f>> §cパスワードが一致しませんでした。 §fもう一度やり直してください。");
						$player->step = "register-type-pass";
					}
					break;


				case "login-type-pass":
					if($player->pass == $msg){
						$player->sendMessage("§dLogin§f>> §aログインに成功しました。");
						$player->isLogin = true;
						$this->DB->query("UPDATE loginsystem SET ip = '$ip', cid = '$cid' WHERE name = '$name'");
					}else{
						$player->sendMessage("§dLogin§f>> §cパスワードが違います。");
					}
					break;
			}
		}else{
			if($player->pass == $msg) $event->setCancelled();
		}
	}

	public function onMove(PlayerMoveEvent $event){
		if(!$event->getPlayer()->isLogin) $event->setCancelled();
	}

}