<?php

namespace vs;

use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;

class ModalFormInfo{

	/** @var ModalFormRequestPacket */
	public $packet;
	/** @var array */
	public $formData;

	public function __construct(string $title = "", string $content = ""){
		$this->packet = new ModalFormRequestPacket;
		$this->packet->formId = 1;
		$this->formData = [
			"type" => "form",
			"title" => $title,
			"content" => $content,
			"buttons" => []
		];
	}

	public function setTitle(string $title){
		$this->formData["title"] = $title;
	}

	public function setContent(string $content){
		$this->formData["content"] = $content;
	}

	public function send(Player $player){
		$pk = $this->packet;
		$pk->formData = json_encode($this->formData);
		$player->dataPacket($pk);
	}

}