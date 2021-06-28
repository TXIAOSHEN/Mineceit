<?php

declare(strict_types=1);

namespace mineceit\player\info\clicks;

use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\utils\TextFormat;

class ClicksInfo{


	/** @var array */
	private $cps;

	/** @var MineceitPlayer */
	private $player;

	public function __construct(MineceitPlayer $player){
		$this->cps = [];
		$this->player = $player;
	}


	/**
	 *
	 * @param bool $clickedBlock
	 *
	 * Adds a click.
	 */
	public function addClick(bool $clickedBlock) : void{
		$currentMillis = (int) round(microtime(true) * 1000);
		$this->cps[$currentMillis] = $clickedBlock;
		if($this->player->getSettingsInfo()->isCpsPopupEnabled()){
			$this->player->sendTip($this->player->getLanguageInfo()->getLanguage()->scoreboard(Language::PLAYER_CPS) . ': ' . TextFormat::RESET . count($this->cps));
		}
	}

	/**
	 * @return int
	 *
	 * Gets the clicks per second.
	 */
	public function getCps() : int{
		return count($this->cps);
	}


	/**
	 *
	 * Updates the clicks per second.
	 */
	public function updateCPS() : void{
		$currentTime = (int) round(microtime(true) * 1000);
		foreach($this->cps as $time => $bool){
			if(($currentTime - $time) >= 1000){
				unset($this->cps[$time]);
			}
		}
	}


	/**
	 * @return MineceitPlayer
	 *
	 * Gets the player.
	 */
	public function getPlayer() : MineceitPlayer{
		return $this->player;
	}
}
