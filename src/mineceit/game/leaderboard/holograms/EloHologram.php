<?php

declare(strict_types=1);

namespace mineceit\game\leaderboard\holograms;

use mineceit\game\leaderboard\Leaderboards;
use mineceit\MineceitCore;
use pocketmine\level\Level;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class EloHologram extends LeaderboardHologram{

	public function __construct(Vector3 $vec3, Level $level, bool $build, Leaderboards $leaderboards = null){
		parent::__construct($vec3, $level, $leaderboards);
		$this->leaderboardKeys = $this->leaderboards->getLeaderboardKeys();
		if($build){
			$this->placeFloatingHologram(false);
		}
	}

	/**
	 *
	 * @param bool $updateKey
	 *
	 * @return void
	 *
	 * Places the Leaderboard hologram.
	 */
	protected function placeFloatingHologram(bool $updateKey = true) : void{
		$len = count($this->leaderboardKeys) - 1;
		$key = $this->leaderboardKeys[$this->currentKey];
		$text = $this->leaderboards->getEloLeaderboardOf($key);

		if($updateKey){
			$this->currentKey++;
		}

		if($this->currentKey > $len)
			$this->currentKey = 0;

		$string = '';
		$count = 0;
		$size = count($text);
		if($size > 0){

			$kit = MineceitCore::getKits()->getKit($key);

			$queue = $key !== 'global' ? $kit->getName() : 'Global';

			if(strtolower($queue) === 'builduhc')
				$queue = 'BuildUHC';

			$title = TextFormat::LIGHT_PURPLE . TextFormat::BOLD . $queue . TextFormat::WHITE . ' Leaderboards';

			foreach($text as $name => $elo){
				if($count > 9) break;
				$line = $count === 9 ? "" : "\n";
				$place = $count + 1;
				$format = TextFormat::GRAY . $place . '. ' . TextFormat::LIGHT_PURPLE . $name . ' ' . TextFormat::DARK_GRAY . '(' . TextFormat::WHITE . $elo . TextFormat::DARK_GRAY . ')' . $line;
				$string .= $format;
				$count++;
			}

			if($this->floatingText === null)
				$this->floatingText = new FloatingTextParticle($this->vec3, $string, $title);
			else{
				$this->floatingText->setTitle($title);
				$this->floatingText->setText($string);
			}
			$this->level->addParticle($this->floatingText);
		}
	}
}
