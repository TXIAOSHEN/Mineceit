<?php

declare(strict_types=1);

namespace mineceit\game\leaderboard\holograms;

use mineceit\game\leaderboard\Leaderboards;
use pocketmine\level\Level;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;

class PatchNoteHologram extends LeaderboardHologram{

	public function __construct(Vector3 $vec3, Level $level, bool $build, Leaderboards $leaderboards = null){
		parent::__construct($vec3, $level, $leaderboards);
		if($build){
			$this->placeFloatingHologram(false);
		}
	}

	/**
	 *
	 * @param bool $updateKey
	 *
	 * Places the hologram down into the world.
	 */
	protected function placeFloatingHologram(bool $updateKey = true) : void{
		$title = '§d§lPatch §f§lNote';
		$texts = [
			'§dSeason §f1.5 §6Release', '§r', '§d- §fFix many bugs',
			' ', 'Join our discord at §ddiscord.gg/zeqa'
		];
		$string = implode("\n", $texts);
		if($this->floatingText === null)
			$this->floatingText = new FloatingTextParticle($this->vec3, $string, $title);
		else{
			$this->floatingText->setTitle($title);
			$this->floatingText->setText($string);
		}
		$this->level->addParticle($this->floatingText);
	}

	public function updateHologram() : void{
		if($this->placedHologram()){
			return;
		}
		parent::updateHologram();
	}
}
