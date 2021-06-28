<?php

declare(strict_types=1);

namespace mineceit\data\players;

use mineceit\data\mysql\MysqlStream;
use mineceit\game\FormUtil;
use mineceit\MineceitCore;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncLoadPlayerData extends AsyncTask{

	/** @var string */
	private $path;

	/** @var string */
	private $playerName;

	/** @var string[]|array */
	private $validRanks;

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
	private $queryStream;

	/**
	 * AsyncCreatePlayerData constructor.
	 *
	 * @param MineceitPlayer $player
	 * @param string         $path
	 * @param array          $validRanks
	 * @param MysqlStream    $stream
	 */
	public function __construct(MineceitPlayer $player, string $path, array $validRanks, MysqlStream $stream){

		$this->playerName = $player->getName();

		$this->path = $path;

		$this->validRanks = $validRanks;

		$this->queryStream = $stream->getStream();

		$this->username = $stream->username;

		$this->host = $stream->host;

		$this->database = $stream->database;

		$this->port = $stream->port;

		$this->password = $stream->password;
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public function onRun(){

		$languageForm = false;

		$validRanks = (array) $this->validRanks;

		$playerData = [
			'kills' => 0,
			'deaths' => 0,
			'language' => Language::ENGLISH_US,
			'muted' => false,
			'ranks' => [],
			'validtags' => "None",
			'validcapes' => "None",
			'validstuffs' => "None",
			'bpclaimed' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
			'scoreboards-enabled' => true,
			'place-break' => false,
			'pe-only' => false,
			'autorespawn' => false,
			'autosprint' => false,
			'autogg' => false,
			'coinnoti' => true,
			'expnoti' => true,
			'silent-staff' => false,
			'morecrit' => false,
			'lightning' => false,
			'blood' => false,
			'cape' => '',
			'stuff' => '',
			'tag' => '',
			'elo' => [
				'combo' => 1000,
				'fist' => 1000,
				'gapple' => 1000,
				'nodebuff' => 1000,
				'sumo' => 1000,
				'soup' => 1000,
				'mlgrush' => 1000,
				'builduhc' => 1000,
				'boxing' => 1000
			],
			'guild' => '',
			'isbuybp' => false,
			'disguised' => '',
			'translate' => false,
			'swish-sound' => true,
			'cps-popup' => false,
			'coins' => 0,
			'shards' => 0,
			'exp' => 0,
			'lasttimehosted' => -1
		];


		if(!MineceitCore::MYSQL_ENABLED){

			$data = $this->loadFromYaml($playerData, $validRanks);

			$languageForm = (bool) $data['language'];
			$playerData = (array) $data['playerData'];
		}else{

			$load = false;

			$mysql = new \mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

			if($mysql->connect_error){
				var_dump("Unable to connect");
				// TODO
				return;
			}

			$stream = (array) $this->queryStream;

			$parsedData = ['elo' => [], 'ranks' => []];

			foreach($stream as $query){

				$querySuccess = $mysql->query($query);

				if($querySuccess === true || $querySuccess instanceof \mysqli_result){

					if($querySuccess instanceof \mysqli_result){

						$fetch = $querySuccess->fetch_all();

						if(isset($fetch[0])){
							$load = true;

							$fetch = $fetch[0];

							$parsedData['language'] = $fetch[2];
							$parsedData['muted'] = boolval($fetch[3]);
							$parsedData['scoreboards-enabled'] = boolval($fetch[4]);
							$parsedData['place-break'] = boolval($fetch[5]);
							$parsedData['pe-only'] = boolval($fetch[6]);
							$parsedData['autorespawn'] = boolval($fetch[7]);
							$parsedData['autosprint'] = boolval($fetch[8]);
							$parsedData['morecrit'] = boolval($fetch[9]);
							$parsedData['lightning'] = boolval($fetch[10]);
							$parsedData['blood'] = boolval($fetch[11]);
							$parsedData['translate'] = boolval($fetch[12]);
							$parsedData['swish-sound'] = boolval($fetch[13]);
							$parsedData['cps-popup'] = boolval($fetch[14]);
							$parsedData['autogg'] = boolval($fetch[15]);
							$parsedData['coinnoti'] = boolval($fetch[16]);
							$parsedData['expnoti'] = boolval($fetch[17]);
							$parsedData['silent-staff'] = boolval($fetch[18]);
							$parsedData['tag'] = strval($fetch[19]);
							$parsedData['cape'] = strval($fetch[20]);
							$parsedData['stuff'] = strval($fetch[21]);
							$parsedData['validtags'] = strval($fetch[22]);
							$parsedData['validcapes'] = strval($fetch[23]);
							$parsedData['validstuffs'] = strval($fetch[24]);
							$parsedData['bpclaimed'] = strval($fetch[25]);
							$parsedData['isbuybp'] = boolval($fetch[26]);
							$parsedData['disguised'] = strval($fetch[27]);
							$parsedData['potcolor'] = strval($fetch[28]);
							$parsedData['guild'] = strval($fetch[29]);
							$parsedData['kills'] = intval($fetch[32]);
							$parsedData['deaths'] = intval($fetch[33]);
							$parsedData['coins'] = intval($fetch[34]);
							$parsedData['shards'] = intval($fetch[35]);
							$parsedData['exp'] = intval($fetch[36]);
							$parsedData['vote'] = intval($fetch[39]);
							$parsedData['donate'] = intval($fetch[42]);
							if($fetch[44] !== ""){
								$parsedData['ranks'][0] = strval($fetch[45]);
							}
							if($fetch[45] !== ""){
								$parsedData['ranks'][1] = strval($fetch[46]);
							}
							if($fetch[46] !== ""){
								$parsedData['ranks'][2] = strval($fetch[47]);
							}
							$parsedData['lasttimehosted'] = intval($fetch[48]);
							$parsedData['elo']['combo'] = intval($fetch[51]);
							$parsedData['elo']['gapple'] = intval($fetch[52]);
							$parsedData['elo']['fist'] = intval($fetch[53]);
							$parsedData['elo']['nodebuff'] = intval($fetch[54]);
							$parsedData['elo']['soup'] = intval($fetch[55]);
							$parsedData['elo']['sumo'] = intval($fetch[56]);
							$parsedData['elo']['builduhc'] = intval($fetch[57]);
							$parsedData['elo']['boxing'] = intval($fetch[58]);
							$parsedData['elo']['mlgrush'] = intval($fetch[59]);
						}
					}
				}else{
					$load = false;
					var_dump("FAILED [LOAD PLAYER DATA]: " . $mysql->error);
				}
			}

			$mysql->close();

			$keys = array_keys($playerData);
			$parsed = $parsedData;

			if($load){

				foreach($keys as $key){
					$value = $playerData[$key];
					if(!isset($parsed[$key])){
						$parsed[$key] = $value;
					}else{
						switch($key){
							case 'ranks':
								$parsedRanks = $parsed['ranks'];
								$parsedRankKeys = array_keys($parsedRanks);
								foreach($parsedRankKeys as $rankKey){
									$rank = $parsedRanks[$rankKey];
									if(!in_array($rank, $validRanks)){
										unset($parsedRanks[$rankKey]);
									}
								}
								$parsed['ranks'] = $parsedRanks;
								break;
						}
					}
				}

				$playerData = $parsed;
			}
		}

		$this->setResult(['data' => $playerData, 'showLang' => $languageForm, 'player' => $this->playerName]);
	}


	/**
	 * @param array $playerData
	 * @param array $validRanks
	 *
	 * @return array
	 *
	 * Function that loads the data from the yaml file.
	 */
	private function loadFromYaml(array $playerData, array $validRanks) : array{

		$languageForm = false;

		if(!file_exists($this->path)){

			$file = fopen($this->path, 'wb');
			fclose($file);
			$languageForm = true;
		}else{

			$keys = array_keys($playerData);
			$parsed = yaml_parse_file($this->path);

			foreach($keys as $key){
				$value = $playerData[$key];
				if(!isset($parsed[$key])){
					$parsed[$key] = $value;
				}else{
					switch($key){
						case 'ranks':
							$parsedRanks = $parsed['ranks'];
							$parsedRankKeys = array_keys($parsedRanks);
							foreach($parsedRankKeys as $rankKey){
								$rank = $parsedRanks[$rankKey];
								if(!in_array($rank, $validRanks)){
									unset($parsedRanks[$rankKey]);
								}
							}
							$parsed['ranks'] = $parsedRanks;
							break;
					}
				}
			}

			$playerData = $parsed;
		}

		yaml_emit_file($this->path, $playerData);

		return ['language' => $languageForm, 'playerData' => $playerData];
	}

	/**
	 * @param Server $server
	 */
	public function onCompletion(Server $server){

		$core = $server->getPluginManager()->getPlugin('Mineceit');

		$result = $this->getResult();

		if($core instanceof MineceitCore && $core->isEnabled() && $result !== null){

			$playerName = (string) $result['player'];
			$showLang = (bool) $result['showLang'];
			$data = (array) $result['data'];

			$player = $server->getPlayer($playerName);

			if($player !== null && $player->isOnline() && $player instanceof MineceitPlayer){

				if($showLang){
					$locale = $player->getLocale();
					$playerHandler = MineceitCore::getPlayerHandler();
					if($playerHandler->getLanguage($locale) === null){
						$locale = $playerHandler->getLanguage()->getLocale();
					}
					$form = FormUtil::getLanguageForm($locale);
					$player->sendFormWindow($form, ["locale" => $locale]);
				}

				$player->loadData($data);
			}
		}
	}
}
