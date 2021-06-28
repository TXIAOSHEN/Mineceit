<?php

declare(strict_types=1);

namespace mineceit\player\info\settings;


use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\Server;

class BuilderModeInfo{

	/** @var bool */
	private $enabled = false;
	/** @var array|bool[] */
	private $builderLevels = [];

	/** @var Player */
	private $player;
	/** @var Server */
	private $server;
	/** @var string */
	private $playerName;

	public function __construct(Player $player){
		$this->player = $player;
		$this->playerName = $player->getName();
		$this->server = Server::getInstance();
	}

	public function init(array &$data) : void{
		MineceitUtil::loadData('place-break',
			$data, $this->enabled);
		$this->updateBuilderLevels();
	}

	/**
	 * Updates the builder levels.
	 */
	public function updateBuilderLevels() : void{
		$levels = $this->server->getLevels();
		$outputLevels = [];
		foreach($levels as $level){
			$name = $level->getName();
			if(MineceitCore::getDuelHandler()->isDuelLevel($name)
				|| MineceitCore::getReplayManager()->isReplayLevel($level)){
				continue;
			}
			$outputLevels[$name] = isset($this->builderLevels[$name]) ?
				$this->builderLevels[$name] : true;
		}
		$this->builderLevels = $outputLevels;
	}

	public function canBuild() : bool{
		// TODO: Rank permissions
		return $this->player !== null
			&& $this->player->isOnline()
			&& $this->player->isOp()
			&& $this->isEnabled()
			&& isset($this->builderLevels[$levelName = $this->player->getLevel()->getName()])
			&& $this->builderLevels[$levelName];
	}

	public function isEnabled() : bool{
		return $this->enabled;
	}

	public function setEnabled(bool $enabled) : void{
		$this->enabled = $enabled;
	}

	/**
	 * @param      $level
	 * @param bool $enabled - The ability to build in a level.
	 *
	 * Sets the ability to build in a level.
	 */
	public function setBuildEnabledInLevel($level, bool $enabled) : void{
		if($level instanceof Level){
			$levelName = $level->getName();
		}elseif(is_string($level)){
			$levelName = $level;
		}

		if(isset($levelName)){
			$this->builderLevels[$levelName] = $enabled;
		}
	}

	/**
	 * @return array
	 *
	 * Gets the build in levels.
	 */
	public function getBuilderLevels() : array{
		return $this->builderLevels;
	}
}