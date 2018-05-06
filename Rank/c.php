<?php

$out = "";

$image = imagecreatefrompng('in.png');
for($y = 0; $y < 32; ++$y){
	for($x = 0; $x < 64; ++$x){
		$c = imagecolorsforindex($image, imagecolorat($image, $x, $y));
		$out .= chr($c["red"]) . chr($c["green"]) . chr($c["blue"]) . chr((127 - $c["alpha"]) * 2);
	}
}

file_put_contents('out', base64_encode($out));