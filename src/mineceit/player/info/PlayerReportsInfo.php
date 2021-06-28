<?php

declare(strict_types=1);

namespace mineceit\player\info;


use mineceit\maitenance\reports\data\ReportInfo;
use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\Player;

class PlayerReportsInfo{

	/** @var array */
	private $reports = [];
	/** @var array */
	private $reportsSearchHistory = [];

	/** @var Player */
	private $player;

	public function __construct(Player $player){
		$this->player = $player;
	}

	public function getPlayer() : ?Player{
		return $this->player;
	}

	/**
	 * @param ReportInfo $info
	 *
	 * Adds a report to the reports array.
	 */
	public function addReport(ReportInfo $info) : void{
		$reporter = $info->getReporter();
		$reportType = $info->getReportType();
		if(!isset($this->reports[$reportType])){
			$this->reports[$reportType] = [];
		}
		$this->reports[$reportType][$reporter] = $info;
	}

	/**
	 * @param MineceitPlayer $reporter
	 * @param int            $reportType
	 *
	 * @return bool
	 *
	 * Determines if the player has reported you.
	 */
	public function hasReport(MineceitPlayer $reporter, int $reportType) : bool{
		if(isset($this->reports[$reportType])){
			$reports = (array) $this->reports[$reportType];
			return isset($reports[$reporter->getName()]);
		}
		return false;
	}

	/**
	 *
	 * @param int $reportType
	 *
	 * @return int
	 *
	 * Gets the number of reports on you while you were.
	 */
	public function getOnlineReportsCount(int $reportType = ReportInfo::TYPE_HACK) : int{
		if(!isset($this->reports[$reportType])){
			return 0;
		}
		return count($this->reports[$reportType]);
	}

	/**
	 * @param bool $allReports
	 * @param int  ...$reportTypes
	 *
	 * @return ReportInfo[]|array
	 *
	 * Gets all of the reports on you.
	 */
	public function getReports(bool $allReports = false, int...$reportTypes) : array{
		if(!$allReports){
			$result = [];
			foreach($reportTypes as $type){
				if(!isset($this->reports[$type])){
					continue;
				}
				$reports = (array) $this->reports[$type];
				foreach($reports as $key => $value){
					if($value instanceof ReportInfo){
						$result[$value->getLocalName()] = $value;
					}
				}
			}
			return $result;
		}else{
			$reportManager = MineceitCore::getReportManager();
			return $reportManager->getReportsOf($this->player->getName(), $reportTypes);
		}
	}

	/**
	 * @return array|ReportInfo[]
	 *
	 * Gets the report history of the current player.
	 */
	public function getReportHistory() : array{
		$reportManager = MineceitCore::getReportManager();
		return $reportManager->getReportHistoryOf(
			$this->player->getName());
	}

	/**
	 * @param array $data
	 *
	 * Adds the report search to the history.
	 */
	public function setReportSearchHistory(array $data) : void{
		$this->reportsSearchHistory[count($this->reportsSearchHistory)] = $data;
	}

	/**
	 * @return array|null
	 *
	 * Gets the last search report.
	 */
	public function getLastSearchReportHistory() : ?array{
		$index = count($this->reportsSearchHistory) - 1;
		return isset($this->reportsSearchHistory[$index]) ? $this->reportsSearchHistory[$index] : null;
	}
}