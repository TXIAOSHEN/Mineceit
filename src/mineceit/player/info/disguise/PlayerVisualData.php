<?php

declare(strict_types=1);

namespace mineceit\player\info\disguise;

use pocketmine\entity\Skin;

/**
 * Class PlayerVisualData
 * @package mineceit\player\info
 *
 * Contains the visual data of the player.
 */
class PlayerVisualData{

	/** @var string */
	private $playerName;
	/** @var Skin|null */
	private $skin;

	public function __construct(string $playerName, ?Skin $skin = null){
		$this->playerName = $playerName;
		$this->skin = $skin;
	}

	public function getDisplayName() : string{
		return $this->playerName;
	}

	public function setDisplayName(string $displayName) : void{
		$this->playerName = $displayName;
	}

	public function getSkin() : ?Skin{
		return $this->skin;
	}

	public function setSkin(Skin $skin) : void{
		$this->skin = $skin;
	}
}