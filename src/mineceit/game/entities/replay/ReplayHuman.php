<?php

declare(strict_types=1);

namespace mineceit\game\entities\replay;

use mineceit\game\entities\GenericHuman;
use mineceit\MineceitCore;
use mineceit\player\info\duels\duelreplay\data\PlayerReplayData;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\item\Item;
use pocketmine\item\ProjectileItem;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class ReplayHuman extends GenericHuman implements IReplayEntity{
	/** @var Vector3|null */
	private $initialPosition = null;
	/* @var Vector3
	 * The target position where you want the player to go.
	 */
	private $targetPosition = null;

	/* @var Vector3
	 * The target motion where you want the player to go.
	 */
	private $targetMotion = null;
	/** @var float - The current replay tick. */
	private $currentReplayTick = 0.0;
	/* @var float
	 * The time scale of the parent replay.
	 */
	private $timeScale = MineceitCore::REPLAY_TIME_SCALE_DEFAULT;

	/* @var bool */
	private $paused = false;
	/* @var int */
	private $startAction = -1;

	/**
	 * @param PlayerReplayData $data
	 *
	 * @return CompoundTag
	 *
	 * Generate nbt for the replay human.
	 */
	public static function getHumanNBT(PlayerReplayData $data) : CompoundTag{

		$startPos = $data->getStartPosition();
		$startRot = $data->getStartRotation();
		$startNameTag = $data->getStartTag();
		$skin = $data->getSkin();

		$nbt = Entity::createBaseNBT($startPos, null, $startRot['yaw'], $startRot['pitch']);
		$nbt->setShort("Health", 20);
		$nbt->setString("CustomName", $startNameTag);

		$tag = new CompoundTag("Skin", [
			new StringTag("Name", $skin->getSkinId()),
			new ByteArrayTag("Data", $skin->getSkinData()),
			new ByteArrayTag("CapeData", $skin->getCapeData()),
			new StringTag("GeometryName", $skin->getGeometryName()),
			new ByteArrayTag("GeometryData", $skin->getGeometryData())
		]);

		$nbt->setTag($tag);
		return $nbt;
	}

	/**
	 * @param ReplayHuman $human
	 * @param bool        $fishing
	 *
	 * Uses the rod for the replay human.
	 */
	public static function useRod(ReplayHuman $human, bool $fishing) : void{
		$fishingBehavior = $human->getFishingBehavior();
		if(!$fishing){
			$fishingBehavior->stopFishing(false);
		}elseif($fishing){
			$fishingBehavior->startFishing();
		}
	}

	public function onUpdate(int $currentTick) : bool{
		// TODO: Check if this fixes the entity leaving arena.
		if($this->initialPosition === null){
			$pos = $this->getPosition();
			$this->initialPosition = new Vector3($pos->x, $pos->y, $pos->z);
		}

		if(!$this->isAlive() || $this->paused){
			return false;
		}

		$update = parent::onUpdate($currentTick);

		if($this->targetPosition !== null){
			$x = ($this->targetPosition->x - $this->x) * $this->timeScale;
			$z = ($this->targetPosition->z - $this->z) * $this->timeScale;
			$y = ($this->targetPosition->y - $this->y) * $this->timeScale;
			$this->move($x, $y, $z);
		}

		if($this->targetMotion !== null){
			$this->motion->x = $this->targetMotion->x * $this->timeScale;
			$this->motion->y = $this->targetMotion->y * $this->timeScale;
			$this->motion->z = $this->targetMotion->z * $this->timeScale;
		}

		if($this->isOnFire()){
			$this->setHealth($this->getMaxHealth());
		}

		return $update;
	}

	public function resetPosition() : void{
		if($this->initialPosition !== null){
			$this->teleport($this->initialPosition);
			$this->setTargetPosition($this->initialPosition);
		}
	}

	/**
	 * @return Vector3|null - Returns the target position of the replay human.
	 */
	public function getTargetPosition() : ?Vector3{
		return $this->targetPosition;
	}

	/**
	 * @param Vector3 $target
	 *
	 * Sets the target position the player needs to go to.
	 */
	public function setTargetPosition(Vector3 $target) : void{
		$this->targetPosition = $target;
	}

	/**
	 * @return Vector3|null - Returns the target motion of the replay human.
	 */
	public function getTargetMotion() : ?Vector3{
		return $this->targetMotion;
	}

	/**
	 * @param Vector3 $target
	 *
	 * Sets the target motion the player needs to go to.
	 */
	public function setTargetMotion(Vector3 $target) : void{
		$this->targetMotion = $target;
	}

	public function setReplayTick(float $replayTick) : void{
		$this->currentReplayTick = $replayTick;
	}

	/**
	 * Gets the time scale of the replay human.
	 */
	public function getTimeScale(){
		return $this->timeScale;
	}

	/**
	 * Sets the time scale of the replay human for motion.
	 *
	 * @param $timeScale
	 */
	public function setTimeScale($timeScale) : void{
		$this->timeScale = $timeScale;
	}

	public function isPaused() : bool{
		return $this->paused;
	}

	/**
	 * @param bool $paused
	 *
	 * Turns the human on pause.
	 */
	public function setPaused(bool $paused) : void{
		$this->paused = $paused;
	}

	/**
	 * @param float $force
	 *
	 * Releases the bow.
	 */
	public function setReleaseBow(float $force) : void{
		$nbt = Entity::createBaseNBT(
			$this->add(0, $this->getEyeHeight(), 0),
			$this->getDirectionVector(),
			($this->yaw > 180 ? 360 : 0) - $this->yaw,
			-$this->pitch
		);

		$nbt->setShort("Fire", $this->isOnFire() ? 45 * 60 : 0);
		$entity = Entity::createEntity("ReplayArrow", $this->getLevel(), $nbt, $this, $force >= 1);

		if($entity instanceof Projectile){
			$entity->setMotion($entity->getMotion()->multiply($force));
			$this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_BOW);
			$entity->spawnToAll();
		}
	}

	protected function doOnFireTick(int $tickDiff = 1) : bool{
		return $this->paused ? false : parent::doOnFireTick($tickDiff);
	}

	protected function getProjectileTypeFromItem(ProjectileItem $item) : string{
		switch($item->getId()){
			case Item::SPLASH_POTION:
				return "ReplayPotion";
			case Item::ENDER_PEARL:
				return "ReplayPearl";
		}

		return parent::getProjectileTypeFromItem($item);
	}
}
