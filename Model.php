<?php

namespace Gbaski\Core;

use Redis;
use Swoole\Database\PDOProxy;

Class Model{

	public PDOProxy $pdo;
	public Redis $redis;

	public $eventRef;
	public $_ = [];

	public function __construct(){

		global $pool;

		$this->pdo = $pool->pdo->get();
		$this->redis = $pool->redis->get();
		
	}

	

	public function  __call($name, $arguments){

		if(!method_exists($this, $name)){

			return $this->default();
		}
	
		return $this->{$name}($arguments);
	}

	function __destruct() {

		global $pool;

		$pool->pdo->put($this->pdo);
		$pool->redis->put($this->redis);

	}

}
