<?php

namespace Nerahikada\Rank;

class Owner extends Base{

	public function isDisplayMessage() : bool{
		return true;
	}

	public function getCape() : ?string{
		$file = 'c0';
		$path = \Phar::running(true) !== "" ? \Phar::running(true).'/'.$file : 'plugins/Rank/'.$file;
		return file_get_contents($path);
	}

}