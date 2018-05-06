<?php

namespace Nerahikada\Rank;

class Base{

	protected $id;
	protected $tag;

	public function __construct(int $id, string $tag = null){
		$this->id = $id;
		$this->tag = $tag;
	}

	public function getId() : int{
		return $this->id;
	}

	public function getTag() : ?string{
		return $this->tag;
	}

	public function isDisplayMessage() : bool{
		return false;
	}

	public function getCape() : ?string{
		return null;
	}

}