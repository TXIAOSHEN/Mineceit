<?php

declare(strict_types=1);

namespace mineceit\data\players;

use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;

class AsyncSaveDonateVoteData extends AsyncTask{

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
	private $mysqlStream;

	/**
	 * AsyncSavePlayerData constructor.
	 *
	 * @param MineceitPlayer $player
	 * @param bool           $donate
	 */
	public function __construct(MineceitPlayer $player, bool $donate = true){

		$stream = MineceitUtil::getMysqlDonateVoteStream($player, $donate);

		$this->host = $stream->host;

		$this->username = $stream->username;

		$this->password = $stream->password;

		$this->database = $stream->database;

		$this->port = $stream->port;

		$this->mysqlStream = $stream->getStream();
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public function onRun(){

		if(MineceitCore::MYSQL_ENABLED){

			$stream = (array) $this->mysqlStream;

			$mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

			if($mysql->connect_error){
				var_dump("Unable to connect");
				// TODO
				return;
			}

			foreach($stream as $query){

				$querySuccess = $mysql->query($query);

				if($querySuccess === false){
					var_dump("FAILED [SAVE PLAYER]: $query\n{$mysql->error}");
				}
			}

			$mysql->close();
		}
	}
}
