<?php

declare(strict_types=1);

namespace mineceit\kits\info;

use mineceit\MineceitCore;

class MiscKitInfo{

	/** @var string - The texture for the kit to display on the form. */
	private $texture;
	/** @var bool -  Determines whether or not to damage players, used for kits like spleef. */
	private $damagePlayers;
	/** @var bool - Determines whether replays are enabled. */
	private $replaysEnabled;
	/** @var bool - Determines whether the kit is for duels. */
	private $duelsEnabled;
	/** @var bool - Determines whether the kit is for ffa. */
	private $ffaEnabled;

	public function __construct(string $texture, bool $replaysEnabled, bool $damagePlayers, bool $ffaEnabled = true, bool $duelsEnabled = true){
		$this->duelsEnabled = $duelsEnabled;
		$this->ffaEnabled = $ffaEnabled;
		$this->replaysEnabled = $replaysEnabled;
		$this->damagePlayers = $damagePlayers;
		$this->texture = $texture;
	}

	public function isFFAEnabled() : bool{
		return $this->ffaEnabled;
	}

	public function isDuelsEnabled() : bool{
		return $this->duelsEnabled;
	}

	public function isReplaysEnabled() : bool{
		return $this->replaysEnabled &&
			$this->duelsEnabled && MineceitCore::REPLAY_ENABLED;
	}

	public function canDamagePlayers() : bool{
		return $this->damagePlayers;
	}

	public function getTexture() : string{
		return $this->texture;
	}

	public function hasTexture() : bool{
		return $this->texture !== "";
	}
}