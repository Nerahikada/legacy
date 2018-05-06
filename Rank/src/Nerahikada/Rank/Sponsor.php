<?php

namespace Nerahikada\Rank;

class Sponsor extends Base{

	public function isDisplayMessage() : bool{
		return true;
	}

	public function getCape() : ?string{
		$file = 'c1';
		$path = \Phar::running(true) !== "" ? \Phar::running(true).'/'.$file : 'plugins/Rank/'.$file;
		return file_get_contents($path);
	}

}