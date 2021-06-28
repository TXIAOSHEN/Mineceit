<?php

declare(strict_types=1);

namespace mineceit\game\leaderboard\holograms;

use mineceit\game\leaderboard\Leaderboards;
use pocketmine\level\Level;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;

class Rank2Hologram extends LeaderboardHologram{

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
		$title = '§d§lZeqa §f§lRanks';
		$texts = [
			'§7Support our server', '§7and recieve a Rank',
			'§r', '§6Donator §8- §a$§f7.49', '§d- §fSpecial Donator Tags, Capes, Artifacts.', '§d- §e300 §fcoins every 24 hours.', '§d- §fIncrease §dParty Size §fto §c8§f.',
			'§r', '§eDonator§f+ §8- §a$§f14.99', '§d- §fSpecial Donator+ Tags, Capes, Artifacts.', '§d- §e500 §fcoins every 24 hours.', '§d- §fIncrease §dParty Size §fto §c8§f.',
			'§r', '§bMedia §8- §f800 Subs', '§cFamous §8- §f3,000 Subs', '§d- §fSpecial Media/Famous Tags, Capes, Artifacts.', '§d- §fIncrease §dParty Size §fto §c6, 8§f.',
			'§r', '§6Donate at §8- §dstore.zeqa.net', '§fHosted by Apex Hosting'
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
