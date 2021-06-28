<?php

declare(strict_types=1);

namespace mineceit\game\leaderboard\holograms;

use mineceit\game\leaderboard\Leaderboards;
use pocketmine\level\Level;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;

class RuleHologram extends LeaderboardHologram{

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
		$title = '§d§lIn-game §f§lRules';

		$texts = [
			'§7Going against any of these rules',
			'§7may lead to PUNISHMENTS', '§r',
			'§d1. §fNo Racism',
			'§d2. §fNo Advertising',
			'§d3. §fDo not be toxic',
			'§d4. §fDo not Spam Chat',
			'§d5. §fDo not Send DDoS Threats',
			'§d6. §fDo not interrupt other fights',
			'§d7. §fNo Cheating / Hacking Or Exploits',
			'§d8. §fDo not refuse to screenshare (AnyDesk)',
			'§d9. §fDo not Disrespect any members or server',
			'§d10. §fNo Keymapping / No Macroing in any way',
			'Mouse Debounce Time has to be set to 10ms',
			'§d11. §fDo not abuse any types of Glitches / Bugs',
			' ', 'Join our discord at §ddiscord.gg/zeqa'
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
