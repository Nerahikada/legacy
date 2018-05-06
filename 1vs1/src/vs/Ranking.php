<?php

namespace vs;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;
use pocketmine\utils\UUID;
use pocketmine\Player;

class Ranking{

	const LIMIT = 10;

	const TITLE = [
		0 => [
			"win" => "§l§aWin §fRanking",
			"ratio" => "§l§aRatio §fRanking"
		],
		1 => [
			"win" => "§l§aWin §fRanking§r  §7PC",
			"ratio" => "§l§aRatio §fRanking§r  §7PC"
		],
		2 => [
			"win" => "§l§aWin §fRanking§r  §7PE",
			"ratio" => "§l§aRatio §fRanking§r  §7PE"
		],
		
	];

	/** @var AddPlayerPacket[] */
	public static $add;
	/** @var RemoveEntityPacket[] */
	public static $remove;
	/** @var SetEntityDataPacket[] */
	public static $update;

	/** @var Player[] */
	public static $show = [];


	public static function init(){
		// total, win10, pe
		$settings = [
			0 => [
				"win" => new Vector3(560.5, 54.5, 926.5),
				"ratio" => new Vector3(578.5, 54.5, 926.5)
			],
			1 => [
				"win" => new Vector3(591.5, 54.5, 939.5),
				"ratio" => new Vector3(591.5, 54.5, 957.5)
			],
			2 => [
				"win" => new Vector3(547.5, 54.5, 957.5),
				"ratio" => new Vector3(547.5, 54.5, 939.5)
			]
		];

		foreach($settings as $edition => $setting){
			foreach($setting as $ranking => $vector3){
				for($i = 0; $i <= self::LIMIT; ++$i){
					$pk = new AddPlayerPacket;
					$pk->uuid = UUID::fromRandom();
					$pk->username = "";
					$pk->entityRuntimeId = ($eid = Entity::$entityCount++);
					$pk->position = $setting[$ranking]->add(0, - $i * 0.4);
					$pk->item = Item::get(0);
					$flags = (
						(1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
						(1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
						(1 << Entity::DATA_FLAG_IMMOBILE)
					);
					$metadata = [
						Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
						Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, ""],
						Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0]
					];
					$pk->metadata = $metadata;
					self::$add[$edition][$ranking][] = $pk;

					$pk = new RemoveEntityPacket;
					$pk->entityUniqueId = $eid;
					self::$remove[$edition][$ranking][] = $pk;

					$pk = new SetEntityDataPacket;
					$pk->entityRuntimeId = $eid;
					$pk->metadata = $metadata;
					self::$update[$edition][$ranking][] = $pk;
				}
			}
		}
	}


	public static function update(array $data){
		foreach($data as $edition => $value){
			foreach($value as $rankingKey => $ranking){
				$title = self::TITLE[$edition][$rankingKey];
				self::$add[$edition][$rankingKey][0]->username = $title;
				/*
				self::$add[$edition][$rankingKey][0]->metadata[Entity::DATA_NAMETAG][1] = $title;
				self::$update[$edition][$rankingKey][0]->metadata[Entity::DATA_NAMETAG][1] = $title;
				*/
				foreach($ranking as $key => $r){
					++$key;
					$data = $r[$rankingKey];
					if($rankingKey === "ratio"){
						$data = (string) round($data, 3);
						$data .= " §7({$r["win"]}/{$r["lose"]})";
					}
					$content = "§e$key. §f{$r["name"]} §7- §e$data";
					self::$add[$edition][$rankingKey][$key]->username = $content;
					/*
					self::$add[$edition][$rankingKey][$key]->metadata[Entity::DATA_NAMETAG][1] = $content;
					self::$update[$edition][$rankingKey][$key]->metadata[Entity::DATA_NAMETAG][1] = $content;
					*/
				}
			}
		}

		self::sendUpdate();
	}

	public static function add(Player $player){
		self::$show[$player->getXuid()] = $player;
		foreach(self::$add as $array){
			foreach($array as $array2){
				foreach($array2 as $pk){
					$pk->isEncoded = false;
					$player->dataPacket($pk);
				}
			}
		}
	}

	public static function sendUpdate(){
		//foreach(self::$update as $array){
		foreach(self::$add as $array){
			foreach($array as $array2){
				foreach($array2 as $pk){
					foreach(self::$show as $player){
						$pk->isEncoded = false;
						$player->dataPacket($pk);
					}
				}
			}
		}
	}

	public static function remove(Player $player){
		unset(self::$show[$player->getXuid()]);
		foreach(self::$remove as $array){
			foreach($array as $array2){
				foreach($array2 as $pk){
					$player->dataPacket($pk);
				}
			}
		}
	}
	

}
