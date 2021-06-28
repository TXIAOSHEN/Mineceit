<?php

declare(strict_types=1);

namespace mineceit\game\entities\replay;

use mineceit\game\entities\EnderPearl;
use mineceit\MineceitCore;
use pocketmine\math\Vector3;

class ReplayPearl extends Enderpearl implements IReplayEntity{

	/* @var bool
	 * Determines whether the pearl is paused.
	 */
	private $paused = false;

	/* @var float|int
	 * The time scale used by the replay entity.
	 */
	private $timeScale = MineceitCore::REPLAY_TIME_SCALE_DEFAULT;


	public function onUpdate(int $currentTick) : bool{
		if($this->closed || $this->isCollided || $this->paused){
			return false;
		}
		return parent::onUpdate($currentTick);
	}

	/**
	 * @return Vector3
	 */
	public function getMotion() : Vector3{
		$outputMotion = clone $this->motion;
		$outputMotion->x *= $this->timeScale;
		$outputMotion->y *= $this->timeScale;
		$outputMotion->z *= $this->timeScale;
		return $outputMotion;
	}

	/**
	 * Determines whether the entity is paused.
	 */
	public function isPaused() : bool{
		return $this->paused;
	}

	/**
	 * @param bool $paused
	 */
	public function setPaused(bool $paused) : void{
		$this->paused = $paused;
	}

	/**
	 * Gets the time scale of the replay entity.
	 */
	public function getTimeScale(){
		return $this->timeScale;
	}

	/**
	 * Sets the time scale of the entity.
	 *
	 * @param float|int $timeScale - The time scale of the replay entity.
	 */
	public function setTimeScale($timeScale) : void{
		$this->timeScale = $timeScale;
	}
}
