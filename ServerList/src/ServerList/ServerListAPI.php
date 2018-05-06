<?php

namespace ServerList;

use pocketmine\utils\MainLogger;

class ServerListAPI extends \Thread{

	protected $logger;
	protected $shutdown = false;

	protected $timer = 30 * 20;
	protected $maxPlayers;

	protected $stream;

	protected $token = 'Your token'; //EDIT
	protected $accessToken = '';


	public function __construct($maxPlayers){
		$this->logger = MainLogger::getLogger();
		$this->maxPlayers = $maxPlayers;
		$this->stream = new \Threaded;

		$this->start();

		$this->login();
	}

	public function login(){
		$data = [
			'max'  => $this->maxPlayers,
			'now'  => 0,
			'type' => 'start',
			'server_token' => $this->token
		];
		$ret = $this->queue('update', $data, true);

		$data = [
			'state' => 1,
			'access_token' => $this->accessToken
		];
		$this->queue('push', $data);
	}

	public function logout(){
		$data = [
			'type' => 'stop',
			'access_token' => $this->accessToken
		];
		$this->queue('update', $data, true);
		$this->sendData();

		$this->shutdown = true;
	}

	public function update($type, $count){
		$data = [
			'max'  => $this->maxPlayers,
			'now'  => $count,
			'type' => $type,
			'access_token' => $this->accessToken
		];
		$this->queue('update', $data);
	}

	protected function queue($endpoint, $data, $log = false){
		$this->stream[] = json_encode([$endpoint, $data, $log]);
	}

	protected function sendData(){
		while($this->stream->count() > 0){
			$chunk = $this->stream->shift();
			$chunk = json_decode($chunk, true);
			$curl = curl_init('http://api.pmmp.jp.net/'.$chunk[0]);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $chunk[1]);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_TIMEOUT, 5);
			curl_setopt($curl, CURLOPT_USERPWD, 'Plugin:hXBsxY_P7_');
			curl_setopt($curl, CURLOPT_USERAGENT, 'pmmp');
			$data = curl_exec($curl);
			$data = json_decode($data, true);
			if(!empty($data['token'])) $this->accessToken = str_replace('\'', '', $data['token']);
			if(!empty($data['msg']) && $chunk[2]) $this->logger->info('[ServerList] '.$data['msg']);
		}
	}

	public function run(){
		while($this->shutdown === false){
			// Time Update
			if($this->timer >= 600){
				$data = [
					'max'  => $this->maxPlayers,
					'type' => 'time',
					'access_token' => $this->accessToken
				];
				$this->queue('update', $data);
				$this->timer = 0;
			}
			$this->timer++;

			$this->sendData();
			usleep(1000000 / 20);
		}
		$this->sendData();
	}

}