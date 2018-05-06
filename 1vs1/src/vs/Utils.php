<?php

namespace vs;

use pocketmine\tile\Sign;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use pocketmine\Server;

class Utils{

	public static function deleteDirectory(string $dir){
		if(!file_exists($dir)) return;

		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(
				$dir, \FilesystemIterator::SKIP_DOTS
			), \RecursiveIteratorIterator::CHILD_FIRST);
		foreach($files as $file){
			$path = $file->getPathname();
			$file->isDir() ? rmdir($path) : unlink($path);
		}
		rmdir($dir);
	}

	public static function copyDirectory(string $dir, string $new){
		if(!is_dir($new)) mkdir($new);
		if(is_dir($dir)){
			if($dh = opendir($dir)){
				while(($file = readdir($dh)) !== false){
					if($file === '.' || $file === '..') continue;
					if(is_dir($dir.'/'.$file)) self::copyDirectory($dir.'/'.$file, $new.'/'.$file);
					else copy($dir.'/'.$file, $new.'/'.$file);
				}
				closedir($dh);
			}
		}
	}

	// nullが渡される可能性がある
	public static function isPrefixSign($tile): bool{
		return ($tile instanceof Sign && $tile->getLine(0) === "PREFIX");
	}

	// nullが渡される可能性がある
	public static function isArenaSign($tile): bool{
		return ($tile instanceof Sign && $tile->getLine(0) === Main::SIGN);
	}

	public static function getArenaId(Sign $sign): int{
		return (int) trim(TextFormat::clean($sign->getLine(1)), "a") - 1;
	}

	public static function getArenaLevel(Sign $sign): string{
		return TextFormat::clean($sign->getLine(2));
	}

	public static function delayedTask(callable $c, array $args, int $tick){
		Server::getInstance()->getScheduler()->scheduleDelayedTask(new CallbackTask($c, $args), $tick);
	}

	public static function repeatingTask(callable $c, array $args, int $tick){
		Server::getInstance()->getScheduler()->scheduleRepeatingTask(new CallbackTask($c, $args), $tick);
	}

	public static function asyncDB(...$param){
		Server::getInstance()->getScheduler()->scheduleAsyncTask(new DB(...$param));
	}


	/**
	 * 0: WIN10, 1: PE
	 *
	 * @param string $input
	 * @return int
	 */
	public static function getEdition(string $input): int{
		return (int) $input !== 1;
	}

}