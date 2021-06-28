<?php

declare(strict_types=1);

namespace mineceit\bossbar;

use mineceit\player\MineceitPlayer;

class BossBarUtil{

	/**
	 * @param array $players
	 * @param float $percentage
	 */
	public static function updateBossBar(array $players, float $percentage) : void{

		foreach($players as $player){
			if($player instanceof MineceitPlayer){
				$player->getExtensions()->getBossBar()
					->setFilledPercentage($percentage);
			}
		}
	}
}
