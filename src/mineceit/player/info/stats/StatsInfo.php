<?php

declare(strict_types=1);

namespace mineceit\player\info\stats;


use mineceit\data\IDataHolder;
use mineceit\data\mysql\MysqlRow;
use mineceit\MineceitUtil;
use mineceit\player\info\ClientInfo;
use mineceit\player\MineceitPlayer;
use mineceit\utils\Math;

class StatsInfo implements IDataHolder{

	/** @var int */
	private $kills = 0;
	/** @var int */
	private $deaths = 0;
	/** @var int */
	private $coins = 0;
	/** @var int */
	private $shards = 0;
	/** @var int */
	private $exp = 0;
	/** @var int */
	private $streaks = 0;

	/** @var bool - Determines if stats changed. */
	private $statsChanged = false;

	/** @var MineceitPlayer */
	private $parentPlayer;
	/** @var string */
	private $playerName;

	public function __construct(MineceitPlayer $player){
		$this->parentPlayer = $player;
		$this->playerName = $player->getName();
	}

	/**
	 * @param int        $winnerElo
	 * @param int        $loserElo
	 * @param ClientInfo $winnerInfo
	 * @param ClientInfo $loserInfo
	 *
	 * @return \stdClass
	 *
	 * Calculates the elo from the winner & loser and returns the elo change output.
	 */
	public static function calculateElo(int $winnerElo, int $loserElo, ClientInfo $winnerInfo, ClientInfo $loserInfo) : \stdClass{
		$kFactor = 32;
		$winnerExpectedScore = 1.0 / (1.0 + pow(10, floatval(($loserElo - $winnerElo) / 400)));
		$loserExpectedScore = abs(floatval(1.0 / (1.0 + pow(10, floatval(($winnerElo - $loserElo) / 400)))));
		$newWinnerElo = $winnerElo + intval($kFactor * (1 - $winnerExpectedScore));
		$newLoserElo = $loserElo + intval($kFactor * (0 - $loserExpectedScore));
		$winnerEloChange = $newWinnerElo - $winnerElo;
		$loserEloChange = abs($loserElo - $newLoserElo);

		$winnerDevice = $winnerInfo->getDeviceOS();
		$loserDevice = $loserInfo->getDeviceOS();

		if($winnerDevice === MineceitPlayer::WINDOWS_10
			&& $loserDevice !== MineceitPlayer::WINDOWS_10){
			$loserEloChange = intval($loserEloChange * 0.9);
		}elseif($winnerDevice !== MineceitPlayer::WINDOWS_10
			&& $loserDevice === MineceitPlayer::WINDOWS_10){
			$winnerEloChange = intval($winnerEloChange * 1.1);
		}

		$newLElo = Math::floor($loserElo - $loserEloChange, 700);
		$result = new \stdClass();
		$result->loserEloChange = $loserElo - $newLElo;
		$result->winnerEloChange = $winnerEloChange;
		return $result;
	}

	/**
	 * @param array $loadedData - The player data sent from load data task.
	 * Initializes the data contained here.
	 */
	public function init(array &$loadedData) : void{
		MineceitUtil::loadData('kills',
			$loadedData, $this->kills);
		MineceitUtil::loadData('deaths',
			$loadedData, $this->deaths);
		MineceitUtil::loadData('coins',
			$loadedData, $this->coins);
		MineceitUtil::loadData('shards',
			$loadedData, $this->shards);
		MineceitUtil::loadData('exp',
			$loadedData, $this->exp);
	}

	/**
	 * @param array $data
	 *
	 * Exports to an array.
	 */
	public function export(array &$data) : void{
		$data['kills'] = $this->kills;
		$data['deaths'] = $this->deaths;
		$data['coins'] = $this->coins;
		$data['shards'] = $this->shards;
		$data['exp'] = $this->exp;
	}

	/**
	 * @param bool $updateRow
	 *
	 * @return MysqlRow
	 *
	 * Gets the MYSQL Row from the stats data.
	 */
	public function generateMYSQLRow(bool $updateRow) : MysqlRow{
		$statisticsRow = new MysqlRow("PlayerStats");
		$statisticsRow->put("username", $this->playerName);
		if($updateRow){
			$statisticsRow->put("kills", $this->kills);
			$statisticsRow->put("deaths", $this->deaths);
			$statisticsRow->put("coins", $this->coins);
			$statisticsRow->put("shards", $this->shards);
			$statisticsRow->put("exp", $this->exp);
		}
		return $statisticsRow;
	}

	/**
	 * Adds a kill to the player.
	 */
	public function addKill() : void{
		$this->statsChanged = true;
		$this->kills += 1;
		$this->addKillStreak();
	}

	/**
	 * Adds a killstreak to the player.
	 */
	private function addKillStreak() : void{
		$this->streaks += 1;
	}

	/**
	 * Gets the number of kills the player has.
	 *
	 * @return int
	 */
	public function getKills() : int{
		return $this->kills;
	}

	/**
	 * Adds a death to the player.
	 */
	public function addDeath() : void{
		$this->statsChanged = true;
		$this->deaths += 1;
		$this->resetKillStreak();
	}

	/**
	 * Resets the number of killstreaks the player has.
	 */
	private function resetKillStreak() : void{
		$this->streaks = 0;
	}

	/**
	 * Gets the number of deaths the player has.
	 *
	 * @return int
	 */
	public function getDeaths() : int{
		return $this->deaths;
	}

	/**
	 * Gets the number of killstreaks the player has.
	 *
	 * @return int
	 */
	public function getKillStreak() : int{
		return $this->streaks;
	}

	/**
	 * @param int $coins
	 * Adds coins to the player.
	 */
	public function addCoins(int $coins) : void{
		$this->statsChanged = true;
		$this->coins += $coins;
	}

	/**
	 * @param int $coins
	 * Removes coins from the player.
	 */
	public function removeCoins(int $coins) : void{
		$this->statsChanged = true;
		$this->coins -= $coins;
	}

	/**
	 * Gets the number of coins the player has.
	 *
	 * @return int
	 */
	public function getCoins() : int{
		return $this->coins;
	}

	/**
	 * @param int $shards
	 * Adds shards to the player.
	 */
	public function addShards(int $shards) : void{
		$this->statsChanged = true;
		$this->shards += $shards;
	}

	/**
	 * @param int $shards
	 * Removes shards from the player.
	 */
	public function removeShards(int $shards) : void{
		$this->statsChanged = true;
		$this->shards -= $shards;
	}

	/**
	 * Gets the number of shards the player has.
	 *
	 * @return int
	 */
	public function getShards() : int{
		return $this->shards;
	}

	/**
	 * @param int $exp
	 * Adds exp to the player.
	 */
	public function addExp(int $exp) : void{
		$this->statsChanged = true;
		$this->exp += $exp;
	}

	/**
	 * Gets the number of exp the player has.
	 *
	 * @return int
	 */
	public function getExp() : int{
		return $this->exp;
	}
}
