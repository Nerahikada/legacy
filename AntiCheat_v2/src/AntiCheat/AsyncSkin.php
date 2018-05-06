<?php

namespace AntiCheat;

class AsyncSkin extends \Thread{

	const SKIN_PATH = '../skin/';

	public $stream;

	public function __construct(){
		$this->stream = new \Threaded;
		$this->start();
	}

	public function queue($skinId, $skinData, $capeData, $geometryName, $geometryData){
		// input: base64 encoded
		$this->stream[] = $skinId.':'.$skinData.':'.$capeData.':'.$geometryName.':'.$geometryData;
	}

	public function saveSkin(){
		while($this->stream->count() > 0){
			$chunk = $this->stream->shift();

			$entries = explode(':', $chunk);
			$skinId = $entries[0];
			$skinData = $entries[1];
			$capeData = $entries[2];
			$geometryName = $entries[3];
			$geometryData = $entries[4];

			$tmp = self::SKIN_PATH.'tmp.';

			$data = $skinId."\n".$geometryName;
			file_put_contents($tmp.'data.txt', $data);

			$this->toPng($skinData, $tmp.'skin');
			if(!empty($capeData)) $this->toPng($capeData, $tmp.'cape');

			$geometry = base64_decode($geometryData);
			file_put_contents($tmp.'geometry.json', $geometry);

			$tmpData = file_get_contents($tmp.'data.txt');
			$tmpSkin = file_get_contents($tmp.'skin.png');
			$tmpCape = empty($capeData) ? $capeData : file_get_contents($tmp.'cape.png');
			$tmpGeometry = file_get_contents($tmp.'geometry.json');

			$count = 0;
			$found = false;
			while(file_exists(self::SKIN_PATH.$count.'.data.txt')){
				$path = self::SKIN_PATH.$count.'.';
				$cape = file_exists($path.'cape.png') ? file_get_contents($path.'cape.png') : $capeData;
				$geometry = file_get_contents($path.'geometry.json');

				if($tmpGeometry == $geometry) file_put_contents($tmp.'geometry.json', 'Same as '.$count);

				if(
					$tmpData == file_get_contents($path.'data.txt') &&
					$tmpSkin == file_get_contents($path.'skin.png') &&
					$tmpCape == $cape &&
					$tmpGeometry == $geometry
				){
					$found = true;
					break;
				}
				$count++;
			}

			if(!$found){
				$path = self::SKIN_PATH.$count.'.';
				rename($tmp.'data.txt', $path.'data.txt');
				rename($tmp.'skin.png', $path.'skin.png');
				if(!empty($capeData)) rename($tmp.'cape.png', $path.'cape.png');
				rename($tmp.'geometry.json', $path.'geometry.json');
			}else{
				unlink($tmp.'data.txt');
				unlink($tmp.'skin.png');
				if(!empty($capeData)) unlink($tmp.'cape.png');
				unlink($tmp.'geometry.json');
			}
		}
	}

	public function toPng($data, $filepath){
		$data = base64_decode($data);
		$len = strlen($data);
		$hight = $len === 64 * 64 * 4 ? 64 : 32;
		$image = imagecreatetruecolor(64, $hight);
		imagealphablending($image, false);
		imagesavealpha($image, true);
		$count = 0;
		for($i = 0; $i < $len; $i += 4){
			$r = ord($data{$i});
			$g = ord($data{$i + 1});
			$b = ord($data{$i + 2});
			$a = ord($data{$i + 3});
			$a = floor(abs($a - 255) / 2);
			$color = imagecolorallocatealpha($image, $r, $g, $b, $a);
			$x = $count % 64;
			$y = floor($count / 64);
			imageline($image, $x, $y, $x, $y, $color);
			$count++;
		}
		imagepng($image, $filepath.'.png');
		imagedestroy($image);
	}

	public function run(){
		if(!file_exists(self::SKIN_PATH)) mkdir(self::SKIN_PATH);

		while(true){
			$this->saveSkin();
			usleep(1000000 / 20);
		}
	}

}