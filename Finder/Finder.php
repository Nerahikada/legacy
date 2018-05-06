<?php

foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__dir__)) as $fileinfo){
	if($fileinfo->isFile() && $fileinfo->getExtension() === "php"){
		$path = $fileinfo->getPathname();
		if($path !== __FILE__){
			if(strpos(file_get_contents($path), "<<< EDIT THIS >>>") !== false) echo $path."\n";
		}
	}
}

echo "END\n";