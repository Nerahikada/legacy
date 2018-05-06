<?php

namespace vs;

use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\utils\UUID;
use pocketmine\Player;

class NPC{

	/** @var AddPlayerPacket */
	protected static $add;
	/** @var RemoveEntityPacket */
	protected static $remove;
	/** @var PlayerListPacket */
	protected static $list;
	/** @var int */
	protected static $eid;

	public static function create(){
		$pk = new AddPlayerPacket;
		$pk->uuid = ($uuid = UUID::fromRandom());
		$pk->username = "§l§a自動エントリー";
		$pk->entityRuntimeId = ($eid = Entity::$entityCount++);
		$pk->position = new Vector3(569.5, 51, 961.5);
		$pk->yaw = 180;
		$pk->headYaw = 180;
		$pk->item = Item::get(Item::DIAMOND_SWORD);
		$flags = (
			(1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
			(1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
			(1 << Entity::DATA_FLAG_IMMOBILE)
		);
		$pk->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, "§l§a自動エントリー"]
		];
		self::$add = $pk;

		$pk = new RemoveEntityPacket;
		$pk->entityUniqueId = $eid;
		self::$remove = $pk;

		$v = Main::getInstance()->getDescription()->getVersion();
		$skinpath = file_exists('plugins/1vs1/entry') ? 'plugins/1vs1/entry' : \Phar::running(true).'/entry';
		$skin = new Skin(
			"Standard_Custom",
			base64_decode(file_get_contents($skinpath))
		);

		$pk = new PlayerListPacket;
		$pk->entries[] = PlayerListEntry::createAdditionEntry($uuid, $eid, "", "", 0, $skin);
		self::$list = $pk;

		self::$eid = $eid;
	}

	public static function add(Player $player){
		$player->dataPacket(self::$add);
		for($i = 0; $i <= 1; $i++){
			$pk = clone self::$list;
			$pk->type = $i;
			$player->dataPacket($pk);
		}
	}

	public static function remove(Player $player){
		$player->dataPacket(self::$remove);
	}

	public static function getEid(): int{
		return self::$eid;
	}

}