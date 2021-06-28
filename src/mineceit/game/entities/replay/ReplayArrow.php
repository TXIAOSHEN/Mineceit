<?php

declare(strict_types=1);

namespace mineceit\game\entities\replay;

use mineceit\MineceitCore;
use pocketmine\entity\projectile\Arrow;
use pocketmine\math\Vector3;

class ReplayArrow extends Arrow implements IReplayEntity{

	/* @var bool
	 * Determines whether the arrow is paused.
	 */
	private $paused = false;

	/* @var float
	 * The time scale in order to update the motion.
	 */
	private $timeScale = MineceitCore::REPLAY_TIME_SCALE_DEFAULT;

	public function onUpdate(int $currentTick) : bool{
		if($this->closed || $this->paused)
			return false;

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
	 * @return float
	 * @var - The time scale of the replay arrow.
	 */
	public function getTimeScale() : float{
		return $this->timeScale;
	}

	/**
	 * @var float|int $timeScale
	 * The time scale of the motion.
	 */
	public function setTimeScale($timeScale) : void{
		$this->timeScale = $timeScale;
	}
}
