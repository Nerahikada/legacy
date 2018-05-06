<?php

/*
 *  _   _                _     _ _             _       
 * | \ | | ___ _ __ __ _| |__ (_) | ____ _  __| | __ _ 
 * |  \| |/ _ \ '__/ _` | '_ \| | |/ / _` |/ _` |/ _` |
 * | |\  |  __/ | | (_| | | | | |   < (_| | (_| | (_| |
 * |_| \_|\___|_|  \__,_|_| |_|_|_|\_\__,_|\__,_|\__,_|
 *
 *
 * @author ねらひかだ (English: Nerahikada)
 * Twitter: https://twitter.com/Nerahikada
*/


echo "ReWrite Start" . PHP_EOL;
$start = microtime(true);

$before = [
	"BEFORE",
	"Before2"
];
$after = [
	"AFTER",
	"After2"
];

foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__dir__)) as $fileinfo){
	if($fileinfo->isFile() && $fileinfo->getExtension() === "php"){
		$path = $fileinfo->getPathname();
		if($path !== __FILE__){
			$data = str_replace($before, $after, file_get_contents($path), $count);
			if($count != 0){
				echo "Rewrited: ".$path . PHP_EOL;
				file_put_contents($path, $data);
			}
		}
	}
}

echo "ReWrite Finish (" . (microtime(true) - $start) . "sec)" . PHP_EOL;