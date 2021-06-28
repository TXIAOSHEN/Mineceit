<?php

declare(strict_types=1);

namespace mineceit\data\players;

use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\TextFormat;

class AsyncSavePlayerData extends AsyncTask{

	/** @var string */
	private $path;

	/** @var array */
	private $yamlInfo;

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
	 * @param string         $path
	 */
	public function __construct(MineceitPlayer $player, string $path){
		$this->path = $path;

		if(!MineceitCore::MYSQL_ENABLED){
			# tag sort
			$tags = [];
			$validtags = $player->getValidTags();
			foreach($validtags as $tag){
				if($tag === 'None'){
					if(($key = array_search('None', $validtags)) !== false)
						unset($validtags[$key]);
				}else{
					$tags[] = TextFormat::clean($tag);
				}
			}
			array_multisort($tags, $validtags);
			array_unshift($validtags, 'None');

			# cape sort
			$capes = [];
			$validcapes = $player->getValidCapes();
			foreach($validcapes as $cape){
				if($cape === 'None'){
					if(($key = array_search('None', $validcapes)) !== false)
						unset($validcapes[$key]);
				}else{
					$capes[] = TextFormat::clean($cape);
				}
			}
			array_multisort($capes, $validcapes);
			array_unshift($validcapes, 'None');

			# stuff sort
			$stuffs = [];
			$validstuffs = $player->getValidStuffs();
			foreach($validstuffs as $stuff){
				if($stuff === 'None'){
					if(($key = array_search('None', $validstuffs)) !== false)
						unset($validstuffs[$key]);
				}else{
					$stuffs[] = TextFormat::clean($stuff);
				}
			}
			array_multisort($stuffs, $validstuffs);
			array_unshift($validstuffs, 'None');

			$bpclaimed = $player->getBpClaimed();

			$this->yamlInfo = [
				'coinnoti' => true,
				'expnoti' => true,
				'silent-staff' => $player->isSilentStaffEnabled(),
				'tag' => $player->getTag(),
				'muted' => $player->isMuted(),
				'language' => $player->getLanguageInfo()->getLanguage()->getLocale(),
				'ranks' => $player->getRanks(true),
				'cape' => $player->getCape(),
				'stuff' => $player->getStuff(),
				'potcolor' => $player->getPotColor(),
				'validtags' => implode(',', $validtags),
				'validcapes' => implode(',', $validcapes),
				'validstuffs' => implode(',', $validstuffs),
				'bpclaimed' => implode(',', $bpclaimed),
				'isbuybp' => $player->isBuyBattlePass(),
				// TODO: Implementation
				'guild' => $player->getGuildRegion() . ',' . $player->getGuild(),
				'lasttimehosted' => $player->getLastTimeHosted()
			];

			$player->getDisguiseInfo()->export($this->yamlInfo);
			$player->getStatsInfo()->export($this->yamlInfo);
			$player->getSettingsInfo()->export($this->yamlInfo);
			$player->getEloInfo()->export($this->yamlInfo);
		}

		$stream = MineceitUtil::getMysqlStream($player, true);

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

		if(!MineceitCore::MYSQL_ENABLED){

			$info = (array) $this->yamlInfo;

			$keys = array_keys($info);

			$parsed = yaml_parse_file($this->path);

			foreach($keys as $key){

				$dataInfo = $info[$key];
				switch($key){
					case 'ranks':
						// case 'permissions':
					case 'elo':
						$dataInfo = (array) $info[$key];
						break;
				}
				$parsed[$key] = $dataInfo;
			}

			yaml_emit_file($this->path, $parsed);
		}else{

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
