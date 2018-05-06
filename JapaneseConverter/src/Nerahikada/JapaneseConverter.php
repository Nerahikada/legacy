<?php

namespace Nerahikada;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\plugin\PluginBase;

class JapaneseConverter extends PluginBase implements Listener{

	const EXCEPTION = '@';
	const GOOGLE = 'http://www.google.com/transliterate?langpair=ja-Hira%7Cja&text=';

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onReceive(DataPacketReceiveEvent $event){
    	$packet = $event->getPacket();
    	if($packet::NETWORK_ID === ProtocolInfo::LOGIN_PACKET){
    		$player = $event->getPlayer();
			$player->currentInputMode = $packet->clientData["CurrentInputMode"];
		}
    }

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$player->jaConvert = ($player->currentInputMode === 1) ? true : false;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
		$sender->jaConvert = !$sender->jaConvert;
		$message = $sender->jaConvert ? "ON" : "OFF";
		$sender->sendMessage("[§6JapaneseConverter§f] §a変換機能が".$message."になりました。");
		return true;
	}

	public function onChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		if($player->jaConvert){
			$message = $event->getMessage();
			if(strpos($message, self::EXCEPTION) === 0){
				$message = ltrim($message, self::EXCEPTION);
				if(trim($message) === "") $event->setCancelled();
				$event->setMessage($message);
			}else{
				if(strlen($message) === mb_strlen($message)){
					$result = $this->convert($message);
					$event->setMessage($result . " §7(" . $event->getMessage(). ")");
				}
			}
		}
	}

	public function convert($str){
		$hiragana = $this->toHiragana($str);
		$result = $this->googleConvert($hiragana);
		if(empty($result)) $result = $hiragana;
		return $result;
	}

	public function toHiragana($str){
		$r = array(
			'hax','bow','lol','pvp','youtuber','youtube','hello','discord',
			'hacker','hack','reach','cheater','cheat','nice','block','kick','spammer','spam','mappu','map','tappu','tap','lag',
			'...',
			'bb','cc','dd','ff','gg','hh','jj','kk','ll','mm','nn','pp','qq','rr','ss','tt','vv','ww','xx','yy','zz',
			'ka','ki','ku','ke','ko',
			'ga','gi','gu','ge','go',
			'kya','kyi','kyu','kye','kyo',
			'gya','gyi','gyu','gye','gyo',
			'sha','shi','shu','she','sho',
			'tsu','sa','shi','su','se','so',
			'za','zi','zu','ze','zo',
			'sya','syi','syu','sye','syo',
			'ja','ji','ju','je','jo',
			'jya','jyi','jyu','jye','jyo',
			'zya','zyi','zyu','zye','zyo',
			'xtu','ltu','ta','ti','tu','te','to',
			'dya','dyi','dyu','dye','dyo',
			'dha','dhi','dhu','dhe','dho',
			'da','di','du','de','do',
			'cha','chi','chu','che','cho',
			'tya','tyi','tyu','tye','tyo',
			'na','ni','nu','ne','no',
			'nya','nyi','nyu','nye','nyo',
			'tha','thi','thu','the','tho',
			'ha','hi','hu','he','ho',
			'ba','bi','bu','be','bo',
			'hya','hyi','hyu','hye','hyo',
			'bya','byi','byu','bye','byo',
			'pa','pi','pu','pe','po',
			'pya','pyi','pyu','pye','pyo',
			'ma','mi','mu','me','mo',
			'mya','myi','myu','mye','myo',
			'rya','ryi','ryu','rye','ryo',
			'ya','yi','yu','ye','yo',
			'ra','ri','ru','re','ro',
			'wa','wi','wu','we','wo',
			'si','ti','tu',
			'xa','xi','xu','xe','xo',
			'la','li','lu','le','lo',
			'va','vi','vu','ve','vo',
			'fa','fi','fu','fe','fo',
			'qa','qi','qu','qe','qo',
			'a','i','u','e','o','n','-',',','.'
		);

		$k = array(
			'ｈａｘ','Ｂｏｗ','ｌｏｌ','ＰｖＰ','ＹｏｕＴｕｂｅｒ','ＹｏｕＴｕｂｅ','Ｈｅｌｌｏ','Ｄｉｓｃｏｒｄ',
			'ハッカー','ハック','リーチ','チーター','チート','ナイス','ブロック','キック','スパマー','スパム','マップ','マップ','タップ','タップ','ラグ',
			'…',
			'っb','っc','っd','っf','っg','っh','っj','っk','っl','っm','ん','っp','っq','っr','っs','っt','っv','っw','っx','っy','っz',
			'か','き','く','け','こ',
			'が','ぎ','ぐ','げ','ご',
			'きゃ','きぃ','きゅ','きぇ','きょ',
			'ぎゃ','ぎぃ','ぎゅ','ぎぇ','ぎょ',
			'しゃ','し','しゅ','しぇ','しょ',
			'つ','さ','し','す','せ','そ',
			'ざ','じ','ず','ぜ','ぞ',
			'しゃ','しぃ','しゅ','しぇ','しょ',
			'じゃ','じ','じゅ','じぇ','じょ',
			'じゃ','じぃ','じゅ','じぇ','じょ',
			'じゃ','じぃ','じゅ','じぇ','じょ',
			'っ','っ','た','ち','つ','て','と',
			'ぢゃ','ぢぃ','ぢゅ','ぢぇ','ぢょ',
			'でゃ','でぃ','でゅ','でぇ','でぃ',
			'だ','ぢ','づ','で','ど',
			'ちゃ','ち','ちゅ','ちぇ','ちょ',
			'ちゃ','ちぃ','ちゅ','ちぇ','ちょ',
			'な','に','ぬ','ね','の',
			'にゃ','にぃ','にゅ','にぇ','にょ',
			'てゃ','てぃ','てゅ','てぇ','てょ',
			'は','ひ','ふ','へ','ほ',
			'ば','び','ぶ','べ','ぼ',
			'ひゃ','ひぃ','ひゅ','ひぇ','ひょ',
			'びゃ','びょ','びゅ','びぇ','びょ',
			'ぱ','ぴ','ぷ','ぺ','ぽ',
			'ぴゃ','ぴぃ','ぴゅ','ぴぇ','ぴょ',
			'ま','み','む','め','も',
			'みゃ','みぃ','みゅ','みぇ','みょ',
			'りゃ','りぃ','りゅ','りぇ','りょ',
			'や','い','ゆ','いぇ','よ',
			'ら','り','る','れ','ろ',
			'わ','うぃ','う','うぇ','を',
			'し','ち','つ',
			'ぁ','ぃ','ぅ','ぇ','ぉ',
			'ぁ','ぃ','ぅ','ぇ','ぉ',
			'ヴぁ','ヴぃ','ヴ','ヴぇ','ヴぉ',
			'ふぁ','ふぃ','ふ','ふぇ','ふぉ',
			'くぁ','くぃ','く','くぇ','くぉ',
			'あ','い','う','え','お','ん','ー','、','。'
		);

		return str_replace(['っw','っg'], ['ww','gg'], str_ireplace($r, $k, $str));
	}

	public function googleConvert($str){
		$text = urlencode(mb_convert_encoding($str, 'UTF-8', 'auto'));
		$url = self::GOOGLE . $text;

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$data = curl_exec($curl);
		curl_close($curl);

		$decode = json_decode($data, true);
		$result = "";
		foreach($decode as $candidates){
			$result .= $candidates[1][0];
		}

		return $result;
	}

}