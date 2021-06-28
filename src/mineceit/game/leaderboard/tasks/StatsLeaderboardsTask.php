<?php

declare(strict_types=1);

namespace mineceit\game\leaderboard\tasks;

use mineceit\data\mysql\MysqlStream;
use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class StatsLeaderboardsTask extends AsyncTask{

	/** @var string */
	private $directory;

	/** @var array */
	private $stats;

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

	/** @var array */
	private $onlinePlayers;

	public function __construct(string $directory, array $stats){
		$this->directory = $directory;
		$this->stats = $stats;

		$stream = new MysqlStream();
		$rows = [];
		foreach($stats as $stat){
			$stream->selectTablesInOrder(["PlayerStats"], [$stat => true, "username" => false]);
			$rows[$stat] = true;
		}

		$rows["username"] = false;
		$stream->selectDividedColumnsOfRows("PlayerStats", "kills", "deaths", true);

		$onlinePlayers = [];
		$players = Server::getInstance()->getOnlinePlayers();

		foreach($players as $player){
			if($player instanceof MineceitPlayer){
				$kills = $player->getStatsInfo()->getKills();
				$deaths = $player->getStatsInfo()->getDeaths();
				$onlinePlayers[$player->getName()] = [
					"kills" => $kills,
					"deaths" => $deaths,
					"kdr" => $kills / ($deaths === 0 ? 1 : $deaths)
				];
			}
		}

		$this->onlinePlayers = $onlinePlayers;
		$this->username = $stream->username;
		$this->database = $stream->database;
		$this->password = $stream->password;
		$this->port = $stream->port;
		$this->host = $stream->host;
		$this->mysqlStream = $stream->getStream();
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public function onRun(){

		$stats = ['kills' => [], 'deaths' => [], 'kdr' => []];

		$players = (array) $this->onlinePlayers;

		if(!MineceitCore::MYSQL_ENABLED){
			if(is_dir($this->directory)){
				$files = scandir($this->directory);
				foreach($files as $file){
					if(strpos($file, '.yml') !== false){
						$name = str_replace('.yml', '', $file);
						$file = $this->directory . '/' . $file;
						$data = yaml_parse_file($file);
						if(isset($players[$name])){
							$data = $players[$name];
						}
						if(isset($data['kills'], $data['deaths'])){
							$kills = (int) $data['kills'];
							$deaths = (int) $data['deaths'];
							$kdr = floatval($kills);
							if($deaths !== 0){
								$kdr = floatval(round($kills / $deaths));
							}
							$stats['kills'][$name] = $kills;
							$stats['deaths'][$name] = $deaths;
							$stats['kdr'][$name] = $kdr;
						}
					}
				}
			}

			foreach($stats as $key => $statLb){
				arsort($statLb);
				$stats[$key] = $statLb;
			}
		}else{

			$stream = (array) $this->mysqlStream;
			$mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

			if($mysql->connect_error){
				var_dump("Unable to connect");
				// TODO
				return;
			}

			$index = 0;
			$keys = array_keys($stats);
			foreach($stream as $query){
				$stat = $keys[$index];
				$querySuccess = $mysql->query($query);
				if($querySuccess instanceof \mysqli_result){
					$result = $querySuccess->fetch_all();
					$length = count($result);
					$count = 0;
					$leaderboardSet = [];
					$statIndex = 0;
					$nameIndex = 1;
					while($count < $length){
						$set = $result[$count];
						$playerStat = intval($set[$statIndex]);
						$playerName = strval($set[$nameIndex]);
						if(isset($players[$playerName])){
							$data = $players[$playerName];
							$playerStat = intval($data[$stat]);
						}
						$leaderboardSet[$playerName] = $playerStat;
						$count++;
					}
					arsort($leaderboardSet);
					$stats[$stat] = $leaderboardSet;
				}
				$index++;
			}
			$mysql->close();
		}

		$this->setResult($stats);
	}

	public function onCompletion(Server $server){
		$core = $server->getPluginManager()->getPlugin('Mineceit');

		if($core instanceof MineceitCore && $core->isEnabled()){
			$leaderboards = MineceitCore::getLeaderboards();
			$result = $this->getResult();
			if($result !== null){
				$leaderboards->setStatsLeaderboards($result);
			}
		}
	}
}
