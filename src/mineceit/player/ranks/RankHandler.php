<?php

declare(strict_types=1);

namespace mineceit\player\ranks;

use mineceit\data\mysql\MysqlRow;
use mineceit\data\mysql\MysqlStream;
use mineceit\data\ranks\AsyncLoadRanks;
use mineceit\data\ranks\AsyncSaveRanks;
use mineceit\MineceitCore;
use mineceit\parties\events\types\match\data\MineceitTeam;
use mineceit\parties\events\types\PartyDuel;
use mineceit\parties\events\types\PartyGames;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class RankHandler{

	/* @var Rank[]|array */
	private $ranks;

	/* @var Rank|null */
	private $defaultRank;

	/** @var Server */
	private $server;

	/** @var string */
	private $file;

	public function __construct(MineceitCore $core){
		$this->ranks = [];
		$this->defaultRank = null;

		$this->server = $core->getServer();

		$this->file = $core->getDataFolder() . '/ranks.yml';

		$this->initRanks();
	}

	/**
	 * Initializes the ranks so that they can be loaded.
	 */
	private function initRanks() : void{

		$task = new AsyncLoadRanks($this->file);

		$this->server->getAsyncPool()->submitTask($task);
	}


	/**
	 * Loads the ranks to the server.
	 *
	 * @param array $data
	 */
	public function loadRanks(array $data) : void{

		$ranks = (array) $data['ranks'];

		$defaultRank = (string) $data['default-rank'];

		$keys = array_keys($ranks);

		/** @var Rank[]|array $outputRanks */
		$outputRanks = [];

		foreach($keys as $localName){
			$value = (array) $ranks[$localName];
			$rank = Rank::parseRank($localName, $value);
			if($rank !== null){
				$outputRanks[$localName] = $rank;
			}
		}

		$this->ranks = $outputRanks;

		/** @var Rank|null $outputDefaultRank */
		$this->defaultRank = isset($outputRanks[$defaultRank]) ? $outputRanks[$defaultRank] : null;
	}

	/**
	 * @param string $name
	 *
	 * @return Rank|null
	 */
	public function getRank(string $name) : ?Rank{
		$result = null;
		if(isset($this->ranks[$name]))
			$result = $this->ranks[$name];
		else{
			foreach($this->ranks as $rank){
				$rankName = $rank->getName();
				if($rankName === $name){
					$result = $rank;
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * @param string $name
	 * @param null   $format
	 * @param string $permission
	 *
	 * @return bool
	 */
	public function createRank(string $name, $format = null, string $permission = Rank::PERMISSION_NONE) : bool{

		$created = false;

		if(strlen($name) === 0){
			return false;
		}

		$localName = strtolower($name);

		$format = $format ?? TextFormat::DARK_GRAY . "[" . TextFormat::WHITE . $name . TextFormat::DARK_GRAY . ']';

		$rank = new Rank($localName, $name, $format, $permission);

		if(!isset($this->ranks[$localName])){

			$this->ranks[$localName] = $rank;

			if($this->defaultRank === null){
				$this->defaultRank = $rank;
			}

			$created = true;

			$this->saveRanks();
		}

		return $created;
	}

	/**
	 * Saves the ranks to the database.
	 */
	private function saveRanks() : void{

		$ranks = [];

		$stream = new MysqlStream();

		$defaultRank = $this->defaultRank !== null ? $this->defaultRank->getLocalName() : '';

		$id = 1;

		foreach($this->ranks as $rank){

			$data = $rank->encode();
			$localName = $rank->getLocalName();

			$ranks[$rank->getLocalName()] = $data;

			$row = new MysqlRow("RanksData");
			$row->put("id", $id);
			$row->put("localname", $localName);
			$row->put("name", $data['name']);
			$row->put("format", $data['format']);
			$row->put("permission", $data['permission']);
			$row->put("isdefault", $defaultRank === $localName);

			$stream->insertNUpdate($row);

			$id++;
		}

		$task = new AsyncSaveRanks(['default-rank' => $defaultRank, 'ranks' => $ranks], $this->file, $stream);

		$this->server->getAsyncPool()->submitTask($task);
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function removeRank(string $name) : bool{

		$removed = false;
		$localName = strtolower($name);

		if(isset($this->ranks[$localName])){

			if($this->defaultRank !== null && $this->defaultRank->getLocalName() === $localName){
				$this->defaultRank = null;
			}

			unset($this->ranks[$localName]);

			$removed = true;

			$players = $this->server->getOnlinePlayers();

			foreach($players as $player){
				if($player instanceof MineceitPlayer)
					$player->removeRank($localName);
			}

			$this->saveRanks();
		}
		return $removed;
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return string
	 */
	public function formatRanksForChat(MineceitPlayer $player) : string{
		$disguisedInfo = $player->getDisguiseInfo();
		if($disguisedInfo->isDisguised()){
			return $this->getDefaultRank()->getFormat() . TextFormat::WHITE . ' ' .
				$disguisedInfo->getDisguiseData()->getDisplayName() . TextFormat::GRAY . ': ' . TextFormat::RESET;
		}

		if(count($ranks = $player->getRanks()) > 0){
			return $ranks[0]->getFormat() . TextFormat::WHITE . ' ' .
				$player->getDisplayName() . TextFormat::GRAY . ":" . TextFormat::RESET;
		}

		$format = TextFormat::WHITE . $player->getDisplayName() . TextFormat::GRAY . ":" . TextFormat::RESET;
		$defaultRank = $this->getDefaultRank();
		if($defaultRank !== null){
			$format = $defaultRank->getFormat() . ' ' . $format;
		}
		return $format;
	}

	/**
	 * @return Rank|null
	 */
	public function getDefaultRank() : ?Rank{
		return $this->defaultRank;
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return string
	 */
	public function formatStaffForChat(MineceitPlayer $player) : string{
		return TextFormat::RED . TextFormat::BOLD . "STAFF" . TextFormat::RESET .
			TextFormat::RED . ' ' . $player->getName() . ' ' . TextFormat::WHITE . ":" . TextFormat::RESET;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return string
	 */
	public function formatRanksForTag(MineceitPlayer $player) : string{
		if(($disguiseInfo = $player->getDisguiseInfo())->isDisguised()){
			return $this->getDefaultRank()->getColor() . ' '
				. $disguiseInfo->getDisguiseData()->getDisplayName();
		}

		$name = $player->getDisplayName();
		if($player->getDisguiseInfo()->isDisguised()
			|| (count($ranks = $player->getRanks()) <= 0)){
			$color = $this->getDefaultRank()->getColor();
		}else{
			$color = $ranks[0]->getColor();
		}

		if($player->isInParty()){
			$partyEvent = $player->getPartyEvent();
			if($partyEvent instanceof PartyDuel || $partyEvent instanceof PartyGames){
				$team = $partyEvent->getTeam($player);
				if($team instanceof MineceitTeam){
					$color = $team->getTeamColor();
				}
			}
		}
		$format = $color . ' ' . $name;
		if($player->getTag() !== ''){
			$format = $player->getTag() . "\n" . TextFormat::RESET . $format;
		}
		return $format;
	}

	/**
	 * @param bool $asArray
	 *
	 * @return string|array|string[]
	 */
	public function listRanks(bool $asArray = true){

		if($asArray){
			$ranks = [];
			foreach($this->ranks as $rank)
				$ranks[] = $rank->getName();
			return $ranks;
		}

		$size = count($this->ranks);

		if($size <= 0)
			return 'None';

		$result = '';

		$commaLen = $size - 1;
		$count = 0;

		foreach($this->ranks as $rank){
			$comma = ($count === $commaLen) ? '' : ', ';
			$result .= $rank->getName() . $comma;
			$count++;
		}

		return $result;
	}

	/**
	 * Gets the valid ranks. Used in async tasks to update a deleted rank.
	 *
	 * @return array|string[]
	 */
	public function getValidRanks() : array{
		$result = [];
		foreach($this->ranks as $rank)
			$result[] = $rank->getLocalName();
		return $result;
	}
}
