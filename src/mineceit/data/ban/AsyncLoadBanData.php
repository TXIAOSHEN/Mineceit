<?php

declare(strict_types=1);

namespace mineceit\data\ban;

use mineceit\MineceitCore;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncLoadBanData extends AsyncTask{
	/** @var string */
	private $username;

	/** @var string -> The ip of the db */
	private $host;

	/** @var string */
	private $password;

	/** @var int */
	private $port;

	/** @var string */
	private $database;

	/**
	 * AsyncBanData constructor.
	 */
	public function __construct(){
		$data = MineceitCore::getMysqlData();

		$this->username = strval($data['username']);

		$this->host = strval($data['ip']);

		$this->password = strval($data['password']);

		$this->port = intval($data['port']);

		$this->database = strval($data['database']) . 'BanList';
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public function onRun(){
		if(MineceitCore::MYSQL_ENABLED){
			$mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

			if($mysql->connect_error){
				var_dump("Unable to connect");
				// TODO
				return;
			}

			$cmd = 'SELECT * FROM BanList';

			$result = $mysql->query($cmd);

			if($result === true || $result instanceof \mysqli_result){

				if($result instanceof \mysqli_result){

					$result = $result->fetch_all();
				}
			}else{
				var_dump("FAILED [LOAD BAN DATA]: " . $mysql->error);
			}

			$mysql->close();

			$this->setResult($result);
		}
	}

	/**
	 * @param Server $server
	 */
	public function onCompletion(Server $server){

		$core = $server->getPluginManager()->getPlugin('Mineceit');

		$result = $this->getResult();

		if($core instanceof MineceitCore && $core->isEnabled()){

			if($result !== null){
				MineceitCore::getBanHandler()->setBanList($result);
			}

			$server->setConfigBool("white-list", false);
		}
	}
}
