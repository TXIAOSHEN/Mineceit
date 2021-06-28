<?php

declare(strict_types=1);

namespace mineceit\player\info\stats;


use mineceit\data\IDataHolder;
use mineceit\data\mysql\MysqlRow;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use pocketmine\Player;

class EloInfo implements IDataHolder{

	/** @var int */
	private $averageElo = 0;
	/** @var int[]|array */
	private $elo = [];
	/** @var string */
	private $playerName;

	public function __construct(Player $player){
		$this->playerName = $player->getName();
	}

	/**
	 * @param array $loadedData - The player data sent from load data task.
	 * Initializes the data contained here.
	 */
	public function init(array &$loadedData) : void{
		MineceitUtil::loadData('elo',
			$loadedData, $this->elo);
		$this->calculateAverageElo();
	}

	/**
	 * Calculates the average elo.
	 */
	private function calculateAverageElo() : void{
		$totalElo = 0;
		foreach($this->elo as $kitName => $elo){
			$totalElo += $elo;
		}
		$numberOfElo = count($this->elo);
		$this->averageElo = $numberOfElo <= 0 ? 1000 :
			intval($totalElo / $numberOfElo);
	}

	/**
	 * @param array $data
	 *
	 * Exports to an array.
	 */
	public function export(array &$data) : void{
		$data['elo'] = $this->getElo();
	}

	/**
	 * @return array|int[]
	 *
	 * Gets the elo based on kit.
	 */
	public function getElo() : array{
		return $this->elo;
	}

	/**
	 * @param string $kit
	 * @param int    $elo
	 *
	 * Sets the elo based on kit.
	 */
	public function setElo(string $kit, int $elo) : void{
		$this->elo[$kit] = $elo;
		$this->calculateAverageElo();
	}

	/**
	 * @param bool $updateRow
	 *
	 * @return MysqlRow
	 *
	 * Gets the MYSQL Elo row from the stats data.
	 */
	public function generateMYSQLRow(bool $updateRow) : MysqlRow{
		$eloRow = new MysqlRow("PlayerElo");
		$eloRow->put("username", $this->playerName);

		if($updateRow){
			foreach($this->elo as $key => $value){
				$eloRow->put($key, $value);
			}
		}
		return $eloRow;
	}

	/**
	 * @param string $kit
	 *
	 * @return int|null
	 *
	 * Gets the elo from the kit.
	 */
	public function getEloFromKit(string $kit) : ?int{
		if(isset($this->elo[$lowerCase = strtolower($kit)])){
			return (int) $this->elo[$lowerCase];
		}

		$kitFound = MineceitCore::getKits()->getKit($kit);
		if($kitFound == null){
			return null;
		}
		$this->elo[$lowerCase] = 1000;
		return 1000;
	}

	/**
	 * @return int
	 *
	 * Gets the average elo.
	 */
	public function getAverageElo() : int{
		return $this->averageElo;
	}
}