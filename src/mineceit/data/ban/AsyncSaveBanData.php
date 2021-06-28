<?php

declare(strict_types=1);

namespace mineceit\data\ban;

use mineceit\MineceitCore;
use pocketmine\scheduler\AsyncTask;

class AsyncSaveBanData extends AsyncTask{
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

	/** @var array */
	private $ban;

	/** @var bool */
	private $add;

	/**
	 * AsyncBanData constructor.
	 *
	 * @param array $ban
	 * @param bool  $add
	 */
	public function __construct(array $ban, bool $add = true){
		$data = MineceitCore::getMysqlData();

		$this->username = strval($data['username']);

		$this->host = strval($data['ip']);

		$this->password = strval($data['password']);

		$this->port = intval($data['port']);

		$this->database = strval($data['database']) . 'BanList';

		$this->ban = $ban;

		$this->add = $add;
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public function onRun(){
		$mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

		if($mysql->connect_error){
			var_dump("Unable to connect");
			// TODO
			return;
		}

		if($this->add){
			$username = $this->ban[1];
			$reason = $this->ban[2];
			$duration = $this->ban[3];

			$cmd = "INSERT INTO BanList VALUES ('0', '$username', '$reason', '$duration')";
		}else{
			$username = $this->ban[1];

			$cmd = "DELETE FROM BanList WHERE username='$username'";
		}

		$querySuccess = $mysql->query($cmd);

		if($querySuccess === false){
			var_dump("FAILED [SAVE BAN]: $cmd\n{$mysql->error}");
		}

		$mysql->close();
	}
}
