<?php

declare(strict_types=1);

namespace mineceit\player;

use mineceit\data\mysql\MysqlRow;
use mineceit\data\mysql\MysqlStream;
use mineceit\data\players\AsyncLoadPlayerData;
use mineceit\data\players\AsyncSavePlayerData;
use mineceit\data\players\AsyncUpdatePlayerData;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\info\alias\AliasManager;
use mineceit\player\info\alias\tasks\AsyncSearchAliases;
use mineceit\player\info\ClientInfo;
use mineceit\player\info\device\DeviceInfo;
use mineceit\player\info\ips\IPManager;
use mineceit\player\language\Language;
use mineceit\player\language\LanguageHandler;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PlayerHandler{

	/* @var LanguageHandler */
	private static $languages;
	/* @var string */
	private $path;
	/** @var array */
	private $closedInventoryIDs;

	/** @var Server */
	private $server;

	/** @var MineceitCore */
	private $core;

	/** @var IPManager */
	private $ipManager;

	/** @var AliasManager */
	private $aliasManager;

	/** @var DeviceInfo */
	private $deviceInfo;

	/** @var MineceitPlayer[]|array */
	private $kickedOnJoin;

	public function __construct(MineceitCore $core){
		$this->closedInventoryIDs = [];
		$this->kickedOnJoin = [];

		$this->path = $core->getDataFolder() . 'player/';
		self::$languages = new LanguageHandler($core);
		$this->aliasManager = new AliasManager($core);
		$this->ipManager = new IPManager($core);
		$this->deviceInfo = new DeviceInfo($core);
		$this->server = $core->getServer();

		$this->core = $core;

		$this->initFolder();
	}

	/**
	 * Initializes the folder.
	 */
	private function initFolder() : void{
		if(!is_dir($this->path))
			mkdir($this->path);
	}

	/**
	 * @param MineceitPlayer $player
	 * @param string         $reason
	 *
	 * Sets the player to be kicked when joining.
	 */
	public function setKickOnJoin(MineceitPlayer $player, string $reason) : void{
		$this->kickedOnJoin[$player->getUniqueId()->toString()] = [$player, $reason];
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return bool
	 *
	 * Kicks the player when joining.
	 */
	public function doKickOnJoin(MineceitPlayer $player) : bool{
		$uuid = $player->getUniqueId()->toString();
		if(isset($this->kickedOnJoin[$uuid])){
			$reason = $this->kickedOnJoin[$uuid][1];
			$player->kick($reason, false);
			unset($this->kickedOnJoin[$uuid]);
			return true;
		}
		return false;
	}

	/**
	 * @return IPManager
	 */
	public function getIPManager() : IPManager{
		return $this->ipManager;
	}

	/**
	 * @return AliasManager
	 */
	public function getAliasManager() : AliasManager{
		return $this->aliasManager;
	}

	/**
	 * @return DeviceInfo
	 */
	public function getDeviceInfo() : DeviceInfo{
		return $this->deviceInfo;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $update
	 *
	 * Updates all aliases.
	 * @param bool           $saveIP
	 */
	public function updateAliases(MineceitPlayer $player, bool $update = true, bool $saveIP = true) : void{

		$ipData = $this->ipManager->collectInfo($player, $saveIP);

		$aliasIps = $ipData['alias-ips'];
		$locIP = $ipData['loc-ip'];

		$aliasData = $this->aliasManager->collectData($player);

		$aliasUUIDs = $aliasData['alias-uuid'];
		$locUUID = $aliasData['uuid'];

		$name = $player->getName();

		$task = new AsyncSearchAliases($name, $locIP, $locUUID, $aliasIps, $aliasUUIDs, $update);
		$this->server->getAsyncPool()->submitTask($task);
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return int
	 */
	public function getOpenChestID(MineceitPlayer $player) : int{

		$result = 1;

		while(array_search($result, $this->closedInventoryIDs) !== false || !is_null($player->getWindow($result)))
			$result++;

		return $result;
	}

	/**
	 * @param int            $id
	 * @param MineceitPlayer $player
	 *
	 * @return bool
	 */
	public function setClosedInventoryID(int $id, MineceitPlayer $player) : bool{

		$result = false;

		$index = array_search($id, $this->closedInventoryIDs);

		if(is_bool($index) && $index === false) $index = null;

		if(is_null($index)){
			$this->closedInventoryIDs[$player->getName()] = $id;
			$result = true;
		}

		return $result;
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function setOpenInventoryID(MineceitPlayer $player) : void{

		$name = $player->getName();

		$id = $this->getClosedChestID($player);

		if($id !== -1){
			unset($this->closedInventoryIDs[$name]);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return int
	 */
	private function getClosedChestID(MineceitPlayer $player) : int{

		$name = $player->getName();

		$id = -1;

		if(isset($this->closedInventoryIDs[$name]))
			$id = intval($this->closedInventoryIDs[$name]);

		return $id;
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function loadPlayerData(MineceitPlayer $player) : void{

		$name = $player->getName();

		$filePath = $this->path . "$name.yml";

		$rankHandler = MineceitCore::getRankHandler();

		$validRanks = $rankHandler->getValidRanks();

		$mysqlStream = MineceitUtil::getMysqlStream($player);

		$statements = [];

		$tables = ["PlayerSettings", "PlayerStats", "VoteData", "DonateData", "PlayerRanks", "PlayerElo"];

		foreach($tables as $table){
			$statements[] = "{$table}.username = '{$name}'";
		}

		$mysqlStream->selectTables($tables, $statements);

		$task = new AsyncLoadPlayerData($player, $filePath, $validRanks, $mysqlStream);

		$this->server->getAsyncPool()->submitTask($task);
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * Saves the player's data -> used for when logging out.
	 */
	public function savePlayerData(MineceitPlayer $player) : void{

		$name = $player->getName();

		$filePath = $this->path . "$name.yml";

		if($player->hasLoadedData()){

			$task = new AsyncSavePlayerData($player, $filePath);

			$this->server->getAsyncPool()->submitTask($task);
		}
	}

	/**
	 * Returns the player data folder.
	 *
	 * @return string
	 */
	public function getDataFolder() : string{
		return $this->path;
	}

	/**
	 * @param string $locale
	 *
	 * @return Language
	 */
	public function getLanguage(string $locale = Language::ENGLISH_US) : Language{
		return self::$languages->getLanguage($locale);
	}

	/**
	 * @param string $locale
	 *
	 * @return Language|null
	 *
	 * Determines whether the data has old format.
	 */
	public function getLanguageFromOldName(string $locale) : ?Language{

		return self::$languages->getLanguageFromOldName($locale);
	}

	/**
	 * @return Language[]|array
	 */
	public function getLanguages() : array{
		return self::$languages->getLanguages();
	}

	/**
	 * @param string $name
	 * @param string $locale
	 *
	 * @return Language|null
	 */
	public function getLanguageFromName(string $name, string $locale = "") : ?Language{
		return self::$languages->getLanguageFromName($name, $locale);
	}

	/**
	 * @param ClientInfo|null $winner
	 * @param ClientInfo|null $loser
	 * @param int             $newWinnerElo
	 * @param int             $newLoserElo
	 * @param string          $queue
	 */
	public function setElo(?ClientInfo $winner, ?ClientInfo $loser, int $newWinnerElo, int $newLoserElo, string $queue) : void{
		if($winner !== null && $loser !== null){
			$winningPlayer = $winner->getPlayer();
			if($winningPlayer !== null
				&& $winningPlayer->isOnline() && $winningPlayer instanceof MineceitPlayer){
				$winningPlayer->getEloInfo()->setElo($queue, $newWinnerElo);
			}else{
				$this->updatePlayerData($winner->getName(), ['elo' => [$queue => $newWinnerElo]]);
			}

			$losingPlayer = $loser->getPlayer();
			if($losingPlayer !== null
				&& $losingPlayer->isOnline() && $losingPlayer instanceof MineceitPlayer){
				$losingPlayer->getEloInfo()->setElo($queue, $newLoserElo);
			}else{
				$this->updatePlayerData($loser->getName(), ['elo' => [$queue => $newLoserElo]]);
			}
		}
	}

	/**
	 * @param MineceitPlayer|string $player
	 * @param array                 $values
	 */
	public function updatePlayerData($player, $values = []) : void{

		$name = ($player instanceof MineceitPlayer) ? $player->getName() : strval($player);

		$filePath = $this->path . "$name.yml";

		$stream = new MysqlStream();

		$keys = array_keys($values);

		foreach($keys as $key){

			$table = "";
			$value = $values[$key];

			switch($key){
				case 'elo':
					$table = "PlayerElo";
					break;
				case 'ranks':
					$table = "PlayerRanks";
					break;
			}

			$row = new MysqlRow($table);

			if(is_array($value)){
				$valueKeys = array_keys($value);
				foreach($valueKeys as $vKey){
					$sqlKey = $vKey;
					$sqlValue = $value[$vKey];
					$row->put($sqlKey, $sqlValue);
				}
			}

			$stream->updateRow($row, ["username = '{$name}'"]);
		}

		$task = new AsyncUpdatePlayerData($filePath, $stream, $values);

		$this->server->getAsyncPool()->submitTask($task);
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Language|null  $lang
	 *
	 * @return array|string[]
	 */
	public function listStats(MineceitPlayer $player, Language $lang = null) : array{

		$leaderboards = MineceitCore::getLeaderboards();
		$language = ($lang === null ? $player
			->getLanguageInfo()->getLanguage() : $lang);

		$statsOf = $language->generalMessage(Language::STATS_OF, ["name" => $player->getDisplayName()]);

		$title = TextFormat::GRAY . '   » ' . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . $statsOf . TextFormat::RESET . TextFormat::GRAY . ' «';

		$k = $player->getStatsInfo()->getKills();

		$killsRanking = $leaderboards->getRankingOf($player->getName(), 'kills', false) ?? "??";
		$killsRanking = TextFormat::DARK_GRAY . "(" . TextFormat::LIGHT_PURPLE . $lang->getMessage(Language::STATS_RANK_LABEL, ['num' => $killsRanking]) . TextFormat::DARK_GRAY . ")";

		$killsStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_KILLS);
		$deathsStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_DEATHS);

		$kills = TextFormat::GRAY . '   » ' . TextFormat::LIGHT_PURPLE . $killsStr . TextFormat::WHITE . ': ' . $k . " {$killsRanking}" . TextFormat::GRAY . ' «';

		$d = $player->getStatsInfo()->getDeaths();

		$deathsRanking = $leaderboards->getRankingOf($player->getName(), 'deaths', false) ?? "??";
		$deathsRanking = TextFormat::DARK_GRAY . "(" . TextFormat::LIGHT_PURPLE . $lang->getMessage(Language::STATS_RANK_LABEL, ['num' => $deathsRanking]) . TextFormat::DARK_GRAY . ")";


		$deaths = TextFormat::GRAY . '   » ' . TextFormat::LIGHT_PURPLE . $deathsStr . TextFormat::WHITE . ': ' . $d . " {$deathsRanking}" . TextFormat::GRAY . ' «';


		$eloFormat = TextFormat::GRAY . '   » ' . TextFormat::LIGHT_PURPLE . '{kit}' . TextFormat::WHITE . ': {elo} {rank}' . TextFormat::GRAY . ' «';

		$eloOf = $language->generalMessage(Language::ELO_OF, ["name" => $player->getDisplayName()]);

		$eloTitle = TextFormat::GRAY . '   » ' . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . $eloOf . TextFormat::RESET . TextFormat::GRAY . ' «';

		$kitArr = [];
		$kits = MineceitCore::getKits()->getKits();
		foreach($kits as $kit){
			if($kit->getMiscKitInfo()->isDuelsEnabled())
				$kitArr[] = $kit->getName();
		}

		$eloStr = '';

		$count = 0;

		$len = count($kitArr) - 1;

		$elo = $player->getEloInfo()->getElo();

		foreach($kitArr as $kit){

			$name = (string) $kit;
			$kit = strtolower($kit);

			if(!isset($elo[$kit])){
				$elo[$kit] = 1000;
			}

			$e = intval($elo[$kit]);
			$line = ($count === $len) ? "" : "\n";
			$kitRanking = $leaderboards->getRankingOf($player->getName(), $kit) ?? "??";
			$kitRanking = TextFormat::DARK_GRAY . "(" . TextFormat::LIGHT_PURPLE . $language->getMessage(Language::STATS_RANK_LABEL, ['num' => $kitRanking]) . TextFormat::DARK_GRAY . ")";
			$str = str_replace('{rank}', $kitRanking, str_replace('{kit}', $name, str_replace('{elo}', $e, $eloFormat))) . $line;
			$eloStr .= $str;
			$count++;
		}

		$lineSeparator = TextFormat::GRAY . '---------------------------';

		return ['title' => $title, 'firstSeparator' => $lineSeparator, 'kills' => $kills, 'deaths' => $deaths, 'secondSeparator' => $lineSeparator, 'eloTitle' => $eloTitle, 'thirdSeparator' => $lineSeparator, 'elo' => $eloStr, 'fourthSeparator' => $lineSeparator];
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Language|null  $lang
	 *
	 * @return string[]|array
	 */
	public function listInfo(MineceitPlayer $player, Language $lang = null) : array{

		$info = $player->getInfo($lang);

		$language = ($lang === null ? $player->getLanguageInfo()->getLanguage() : $lang);

		$infoOf = $language->generalMessage(Language::INFO_OF, ["name" => $player->getDisplayName()]);

		$title = TextFormat::GRAY . '   » ' . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . $infoOf . TextFormat::RESET . TextFormat::GRAY . ' «';

		$lineSeparator = TextFormat::GRAY . '---------------------------';

		$format = TextFormat::GRAY . '   » ' . TextFormat::LIGHT_PURPLE . '%key%' . TextFormat::WHITE . ': %value%' . TextFormat::GRAY . ' «';

		$result = ['title' => $title, 'firstSeparator' => $lineSeparator];

		$keys = array_keys($info);

		foreach($keys as $key){
			$value = $info[$key];
			$message = str_replace('%key%', $key, str_replace('%value%', $value, $format));
			$result[$key] = $message;
		}

		$result['lastSeparator'] = $lineSeparator;

		return $result;
	}


	/**
	 * @param bool           $string
	 * @param MineceitPlayer ...$excluded
	 *
	 * @return array|MineceitPlayer[]|string[]
	 *
	 * Gets the staff that are online.
	 */
	public function getStaffOnline(bool $string = false, MineceitPlayer ...$excluded) : array{

		$server = Server::getInstance();

		if($string){
			$excluded = array_map(function(Mineceitplayer $player){
				return $player->getName();
			}, $excluded);
		}

		$players = [];
		/** @var MineceitPlayer[] $onlinePlayers */
		$onlinePlayers = $server->getOnlinePlayers();
		foreach($onlinePlayers as $player){
			if($player->hasHelperPermissions() || $player->hasBuilderPermissions()){
				$value = $player;
				if($string){
					$value = $player->getName();
				}
				$players[] = $value;
			}
		}

		return array_diff($players, $excluded);
	}
}
