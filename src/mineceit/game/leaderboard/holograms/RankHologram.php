<?php

declare(strict_types=1);

namespace mineceit\game\leaderboard\holograms;

use mineceit\game\leaderboard\Leaderboards;
use pocketmine\level\Level;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;

class RankHologram extends LeaderboardHologram{

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
		$string = '';
		$title = '§d§lZeqa §f§lRanks';

		$texts = [
			'§7Support our server', '§7and recieve a Rank',
			'§r', '§5Booster §8- §dBoost §fZeqa Discord', '§d- §fSpecial Booster Tags, Capes, Artifacts.', '§d- §e100 §fcoins every 24 hours.', '§d- §fIncrease §dParty Size §fto §c6§f.',
			'§r', '§d- §c/host §fto host events for §6Donator §eDonator§f+', '§bMedia §cFamous §fand §5Booster',
			'§r', '§d- §dBattle §fPass §6Elite §8- §a$§f2.49',
			'§r', '§2Voter §8- §fVote for the server', '§d- §fSpecial Voter Tags, Capes, Artifacts.', '§d- §fEvery times you vote get 150 coins.', '§d- §fIncrease §dParty Size §fto §c6§f.', '§d- §fVote at §2gg.gg/zeqavote §c(1 times per day)', '§d- §fUse §2/vote §fto claims rewards',
			'§r', '§6Donate at §8- §dstore.zeqa.net', '§fHosted by Apex Hosting'
		];
		foreach($texts as $text){
			$line = "\n";
			$string .= $text . $line;
		}

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
