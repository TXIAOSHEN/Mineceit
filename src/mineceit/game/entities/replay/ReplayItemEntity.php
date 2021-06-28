<?php

declare(strict_types=1);

namespace mineceit\game\entities\replay;

use mineceit\game\entities\MineceitItemEntity;
use mineceit\MineceitCore;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;

class ReplayItemEntity extends MineceitItemEntity implements IReplayEntity{

	/* @var bool
	 * Determined when the item is paused.
	 */
	private $paused = false;

	/* @var int
	 * Determined when the item is picked up.
	 */
	private $pickupTick = -1;

	/* @var ReplayHuman $human
	 * The human that will pick up the item.
	 */
	private $humanPickup = null;

	/* @var int
	 * Determined when the item was first dropped.
	 */
	private $spawnTick = -1;

	/* @var int|float
	 * The time scale of the replay item.
	 */
	private $timeScale = MineceitCore::REPLAY_TIME_SCALE_DEFAULT;

	public function onUpdate(int $currentTick) : bool{
		if($this->closed || $this->paused){
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

	public function isPaused() : bool{
		return $this->paused;
	}

	/**
	 * @param bool $paused
	 */
	public function setPaused(bool $paused) : void{
		$this->paused = $paused;
	}

	public function getTimeScale(){
		return $this->timeScale;
	}

	public function setTimeScale($timeScale) : void{
		$this->timeScale = $timeScale;
	}

	/**
	 * @param int|float $replayTick
	 */
	public function updatePickup($replayTick) : void{
		if($this->pickupTick > 0 && $replayTick >= $this->pickupTick && $this->humanPickup !== null){
			$this->pickupItem($this->humanPickup);
		}
	}

	/**
	 * @param ReplayHuman $human
	 */
	private function pickupItem(ReplayHuman $human) : void{

		/*if($this->getPickupDelay() !== 0){
			return;
		}*/

		$pk = new TakeItemActorPacket();
		$pk->eid = $human->getId();
		$pk->target = $this->getId();
		$this->server->broadcastPacket($this->getViewers(), $pk);

		$this->flagForDespawn();
	}

	/**
	 * @param int $time
	 */
	public function setPickupTick(int $time) : void{
		$this->pickupTick = $time;
	}

	/**
	 * @param ReplayHuman $human
	 */
	public function setHumanPickup(ReplayHuman $human) : void{
		$this->humanPickup = $human;
	}

	/**
	 * @param int|float $replayTick
	 *
	 * @return bool
	 */
	public function shouldDespawn($replayTick) : bool{
		$diff = intval($replayTick - $this->pickupTick);
		return ($this->spawnTick > 0 && $replayTick < $this->spawnTick) || ($this->pickupTick > 0 && $diff >= 0);
	}

	/**
	 * @param int|float $tick
	 */
	public function setDroppedTick($tick) : void{
		$this->spawnTick = $tick;
	}
}
