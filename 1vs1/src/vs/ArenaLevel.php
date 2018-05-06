<?php

namespace vs;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Server;

class ArenaLevel{

	const INFO = [
		"1c Flat" => [[[2.5,4,2.5],315], [[13.5,4,13.5],135], "Nerahikada", [8,8,8]],
		"4c Flat" => [[[2.5,4,2.5],315], [[29.5,4,29.5],135], "Nerahikada", [16,10,16]],
		"4c Flat 2" => [[[2.5,4,2.5],315], [[29.5,4,29.5],135], "Nerahikada", [16,10,16]],
		"9c Flat" => [[[2.5,4,2.5],315], [[45.5,4,45.5],135], "Nerahikada", [24,10,24]],
		"9c Flat 2" => [[[2.5,4,2.5],315], [[45.5,4,45.5],135], "Nerahikada", [24,10,24]],
		"16c Flat" => [[[2.5,4,2.5],315], [[61.5,4,61.5],135], "Nerahikada", [40,10,40]],
		"16c Flat 2" => [[[2.5,4,2.5],315], [[61.5,4,61.5],135], "Nerahikada", [40,10,40]],
		"Ice Land" => [[[2.5,6,2.5],315], [[45.5,6,45.5],135], "Nerahikada", [24,10,24]],
		"Cemetery" => [[[3,4,3],270], [[27,4,29],90], "OtorisanVardo", [16,10,16]],
		"Library Maze" => [[[2,6,2],315], [[30,6,30],135], "fujiwaraizuho", [16,12,16]],
		"new Ruin" => [[[1.5,5,1.5],315], [[14.5,5,14.5],135], "minoGames728", [8,16,8]],
		"Old Grave" => [[[2.5,4,2.5],315], [[13.5,4,13.5],135], "skydoki1129", [8,8,8]],
		"Nature" => [[[6.5,4,8.5],270], [[23.5,4,8.5],90], "Nerahikada", [16,12,8]],
		"Nether" => [[[2.5,4,2.5],315], [[29.5,4,29.5],135], "skydoki1129", [16,12,16]],
		"SkyWars" => [[[104,33,11],0], [[104,33,37],180], "Nerahikada", [104,40,24]],
		"Ruin of Nera" => [[[6,8,24],270], [[42,8,24],90], "IceShinon", [24,16,24]],
		"Huge Town" => [[[3.5,4,3.6],315], [[60.5,4,60.5],135], "Rq1kp0", [32,32,32]],
		"PvP Arena" => [[[1785.5,9,-1052.5],45], [[1722.5,9,-1010.5],225], "FierceNinja", [[1701.5,12,-1027.5],270]],
		"FFAMAP" => [[[0.5,9,28.5],180], [[0.5,9,-26.5],0], "Truuz, Phizzle", [0.5,11,0.5]],
		"Desert" => [[[1.5,4,1.5],315], [[30.5,4,30.5],135], "hinogame", [16,10,16]],
		"Normal Battle" => [[[24.5,4,3.5],0], [[25.5,4,61.5],180], "hinogame", [25,16,32.5]],
		"MY FAMILY" => [[[1.5,4,1.5],315], [[78.5,4,78.5],135], "tumuty", [42.5,13, 37.5]],
		"Cherry Blossoms" => [[[665.5,4.5,223.5],90], [[591.5,4.5,227.5],270], "SLASH1133", [626.5,9,227.5]],
		"tumuty" => [[[74.5,6,6.5],90], [[53.5,7,42.5],270], "tumuty", [64,11,25]],
		"Bridge" => [[[20,9,72],270], [[76,9,72],90], "astart0", [49,14,72]],
		"Cave" => [[[12.5,5,9.5],315], [[37.5,5,36.5],135], "Nerahikada", [25.5,10,25.5]],
		"minoHouse" => [[[34.5,4,50.5],315], [[61.5,4,77.5],135], "minoGames728", [47,16,63]],
		"Biros" => [[[23.5,4,119.5],225], [[3.5,5,31.5],315], "Minetendo64 (TeamEthosPix)", [10.5,8,65.5]],
		"FFAMAP 2" => [[[-5.5,23,102.5],180], [[-3.5,23,46.5],0], "ausgebildet, Truuz", [-4.5,22,73.5]],
		"End" => [[[1.5,4,1.5],315], [[30.5,4,30.5],135], "SGHU", [16.5,10,17.5]],
		"PvP Forest" => [[[55.5,4,5.5],0], [[55.5,4,42.5],90], "匿名希望", [55,19,24]],
		//"" => [[[],], [[],], "", []],
	];


	public static function getRandom(): string{
		return mt_array_rand(self::INFO);
	}

	public static function getCreater(string $level): string{
		return self::INFO[$level][2];
	}

	public static function getLevel(string $level,int $id): Level{
		return Server::getInstance()->getLevelByName("copy/".$level.".".$id);
	}

	public static function getPos(string $level,int $id,int $key): array{
		$world = self::INFO[$level][$key];
		$world[0][3] = self::getLevel($level,$id);
		$world[0] = new Position(...$world[0]);
		return $world;
	}

	public static function getView(string $level,int $id): array{
		$pos = self::INFO[$level][3];
		if(!is_array($pos[0])) $pos = [$pos];
		$pos[0][3] = self::getLevel($level,$id);
		$pos[0] = new Position(...$pos[0]);
		return $pos;
	}

}


function mt_array_rand(array $array,$num = 1) {
	static $max;
	if (!$max) {
		$max = mt_getrandmax() + 1;
	}
	$num = (int)$num;
	$count = count($array);
	if ($num <= 0 || $count < $num) {
		return null;
	}
	foreach ($array as $key=>$_) {
		if (!$num) {
			break;
		}
		if (mt_rand() / $max < $num / $count) {
			$retval[] = $key;
			--$num;
		}
		--$count;
	}
	return !isset($retval[1]) ? $retval[0] : $retval;
}