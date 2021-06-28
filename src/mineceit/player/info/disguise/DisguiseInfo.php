<?php

declare(strict_types=1);

namespace mineceit\player\info\disguise;

use mineceit\data\mysql\MysqlRow;
use mineceit\MineceitUtil;
use pocketmine\Player;

class DisguiseInfo{

	/** @var PlayerVisualData */
	private $disguisedData;
	/** @var PlayerVisualData */
	private $originalData;

	/** @var bool - The Disguised data. */
	private $disguised = false;
	/** @var Player */
	private $player;

	public function __construct(Player $player){
		$this->player = $player;
		$this->originalData = new PlayerVisualData(
			$player->getDisplayName(),
			$player->getSkin());
		$this->disguisedData = new PlayerVisualData(
			$player->getDisplayName(),
			$player->getSkin());
	}

	private static function applyVisualData(Player $player, PlayerVisualData &$data) : void{
		if(!$player->isOnline()){
			return;
		}
		if($data->getSkin() !== null){
			$player->setSkin($data->getSkin());
			$player->sendSkin();
		}
		$player->setDisplayName($data->getDisplayName());
	}

	/**
	 * @param array $data
	 *
	 * Initializes the disguise info.
	 */
	public function init(array &$data) : void{
		// Loads the disguised data based on legacy format.
		$disguised = "";
		MineceitUtil::loadData('disguised',
			$data, $disguised);

		if(strlen($disguised) <= 0){
			return;
		}

		// Sets the display name of the disguised data.
		$this->disguisedData->setDisplayName($disguised);
		// TODO: Set skin
		$this->setDisguised(true, true);
	}

	/**
	 * @param array $data
	 *
	 * Exports the data into the array.
	 */
	public function export(array &$data) : void{
		$data['disguised'] = $this->disguised ?
			$this->disguisedData->getDisplayName() : '';
	}

	public function isDisguised() : bool{
		return $this->disguised;
	}

	/**
	 * @param bool $disguised
	 * @param bool $force
	 * Sets the player as disguised.
	 */
	public function setDisguised(bool $disguised, bool $force = false) : void{
		if($this->disguised !== $disguised
			|| $force){
			if(!$this->disguised){
				$this->originalData->setSkin(
					$this->player->getSkin());
			}
			$data = $disguised ? $this->disguisedData : $this->originalData;
			self::applyVisualData($this->player, $data);
		}
		$this->disguised = $disguised;
	}

	/**
	 * @param MysqlRow $row
	 * @param bool     $update
	 *
	 * Applies the data to a mysql row.
	 */
	public function applyToMYSQLRow(MysqlRow &$row, bool $update) : void{
		if($update){
			// Loads the disguise info based on the legacy format.
			$displayName = $this->disguised ? $this->getDisguiseData()->getDisplayName() : '';
			$row->put("disguised", $displayName);
		}
	}

	public function getDisguiseData() : PlayerVisualData{
		return $this->disguisedData;
	}
}