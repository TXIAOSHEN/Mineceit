<?php

declare(strict_types=1);

namespace mineceit\data\mysql;

use mineceit\player\ranks\Rank;
use pocketmine\scheduler\AsyncTask;

class AsyncCreateDatabase extends AsyncTask{

	/** @var array */
	private $stream;

	/** @var string */
	private $username;

	/** @var string */
	private $host;

	/** @var string */
	private $password;

	/** @var int */
	private $port;

	/** @var string */
	private $database;

	public function __construct($validKits = []){
		$mysqlStream = new MysqlStream();

		$this->username = $mysqlStream->username;
		$this->host = $mysqlStream->host;
		$this->password = $mysqlStream->password;
		$this->port = $mysqlStream->port;
		$this->database = $mysqlStream->database;

		$playerSettings = new MysqlTable("PlayerSettings");
		$playerSettings->putId(); // 0
		$playerSettings->putString("username"); // 1
		$playerSettings->putString("language"); // 2
		$playerSettings->putBoolean("muted", false); // 3
		$playerSettings->putBoolean("scoreboardsenabled", true); // 4
		$playerSettings->putBoolean("placebreak", false); // 5
		$playerSettings->putBoolean("peonly", false); // 6
		$playerSettings->putBoolean("autorespawn", false); // 7
		$playerSettings->putBoolean("autosprint", false); // 8
		$playerSettings->putBoolean("morecrit", false); // 9
		$playerSettings->putBoolean("lightning", false); // 10
		$playerSettings->putBoolean("blood", false); // 11
		$playerSettings->putBoolean("translate", false); // 12
		$playerSettings->putBoolean("swishsound", true); // 13
		$playerSettings->putBoolean("cpspopup", false); // 14
		$playerSettings->putBoolean("autogg", false); // 15
		$playerSettings->putBoolean("coinnoti", true); // 16
		$playerSettings->putBoolean("expnoti", true); // 17
		$playerSettings->putBoolean("silentstaff", false); // 18
		$playerSettings->putString("tag", 60, ""); // 19
		$playerSettings->putString("cape", 60, ""); // 20
		$playerSettings->putString("stuff", 60, ""); // 21
		$playerSettings->putString("validtags", 3000, "None"); // 22
		$playerSettings->putString("validcapes", 3000, "None"); // 23
		$playerSettings->putString("validstuffs", 3000, "None"); // 24
		$playerSettings->putString("bpclaimed", 3000, "None"); // 25
		$playerSettings->putBoolean("isbuybp", false); // 26
		$playerSettings->putString("disguised", 60, ""); // 27
		$playerSettings->putString("potcolor", 20, "default"); // 28
		$playerSettings->putString("guild", 100, ""); // 29

		$playerStats = new MysqlTable("PlayerStats");
		$playerStats->putId(); // 30
		$playerStats->putString("username"); // 31
		$playerStats->putInt("kills", 0); // 32
		$playerStats->putInt("deaths", 0); // 33
		$playerStats->putInt("coins", 0); // 34
		$playerStats->putInt("shards", 0); // 35
		$playerStats->putInt("exp", 0); // 36

		$vote = new MysqlTable("VoteData");
		$vote->putId(); // 37
		$vote->putString("username"); // 38
		$vote->putInt("time", 0); // 39

		$donate = new MysqlTable("DonateData");
		$donate->putId(); // 40
		$donate->putString("username"); // 41
		$donate->putInt("time", 0); // 42

		$playerRanks = new MysqlTable("PlayerRanks");
		$playerRanks->putId(); // 43
		$playerRanks->putString("username"); // 44
		$playerRanks->putString("rank1", 20, ""); // 45
		$playerRanks->putString("rank2", 20, ""); // 46
		$playerRanks->putString("rank3", 20, ""); // 47
		$playerRanks->putInt("lasttimehosted", -1); // 48

		$elo = new MysqlTable("PlayerElo");
		$elo->putId(); // 49
		$elo->putString("username"); // 50
		foreach($validKits as $kit){
			$elo->putInt($kit, 1000);
		}

		$ranks = new MysqlTable("RanksData");
		$ranks->putId();
		$ranks->putString("localname"); //0
		$ranks->putString("name"); //1
		$ranks->putString("format"); //2
		$ranks->putString("permission", 60, Rank::PERMISSION_NONE); //3
		$ranks->putBoolean("isdefault", false); //4

		$mysqlStream->createTable($playerSettings);
		$mysqlStream->createTable($playerStats);
		$mysqlStream->createTable($vote);
		$mysqlStream->createTable($donate);
		$mysqlStream->createTable($playerRanks);
		$mysqlStream->createTable($elo);
		$mysqlStream->createTable($ranks);

		$this->stream = $mysqlStream->getStream();
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public function onRun(){

		$mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

		if($mysql->connect_error){
			var_dump("Unable to connect to db [CREATE DATABASE]");
			// TODO
			return;
		}


		$stream = (array) $this->stream;

		foreach($stream as $query){

			$querySuccess = $mysql->query($query);

			if($querySuccess === false){
				var_dump("Failed [CREATE DATABASE]: $query\n$mysql->error");
			}
		}

		$mysql->close();
	}
}
