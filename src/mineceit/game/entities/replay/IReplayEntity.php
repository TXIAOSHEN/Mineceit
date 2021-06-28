<?php

declare(strict_types=1);

namespace mineceit\game\entities\replay;

interface IReplayEntity{
	/**
	 * @param float|int $timeScale - The time scale of the replay entity.
	 */
	public function setTimeScale($timeScale) : void;

	/**
	 * Gets the time scale of the replay entity.
	 * @return int|float
	 */
	public function getTimeScale();

	/**
	 * @param bool $paused
	 * Sets the replay entity as paused.
	 */
	public function setPaused(bool $paused) : void;

	/**
	 * @return bool
	 */
	public function isPaused() : bool;
}
