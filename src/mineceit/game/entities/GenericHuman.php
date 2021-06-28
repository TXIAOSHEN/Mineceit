<?php

declare(strict_types=1);

namespace mineceit\game\entities;

use mineceit\game\behavior\FishingBehavior;
use mineceit\game\behavior\IFishingBehaviorEntity;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\entity\Human;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Item;
use pocketmine\item\ProjectileItem;
use pocketmine\item\SplashPotion as ItemSplashPotion;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class GenericHuman extends Human implements IFishingBehaviorEntity{

	/** @var FishingBehavior|null */
	protected $fishingBehavior = null;

	/**
	 * @return FishingBehavior|null
	 *
	 * Gets the fishing behavior of the human.
	 */
	public function getFishingBehavior() : ?FishingBehavior{
		$this->fishingBehavior = $this->fishingBehavior ?? new FishingBehavior($this);
		return $this->fishingBehavior;
	}

	/**
	 * @param Item    $item
	 * @param Vector3 $directionVector
	 * @param bool    $throwAnimation
	 *
	 * @return bool
	 *
	 * Called to force the bot to handle the onClickAir function.
	 */
	public function onClickAir(Item $item, Vector3 $directionVector, bool $throwAnimation = false) : bool{
		if(!$this->isAlive() || !($item instanceof ProjectileItem)){
			return false;
		}

		$nbt = $this->createProjectileNBT($item, $directionVector, (int) $this->yaw, (int) $this->pitch);
		$projectile = Entity::createEntity(
			$this->getProjectileTypeFromItem($item),
			$this->getLevel(),
			$nbt,
			$this
		);

		if($projectile !== null){
			$projectile->setMotion($projectile->getMotion()->multiply($item->getThrowForce()));
			$projectile->spawnToAll();

			if($projectile instanceof Projectile){
				$ev = new ProjectileLaunchEvent($projectile);
				$ev->call();

				if($ev->isCancelled()){
					$projectile->close();
					return false;
				}

				$this->onProjectileThrown($projectile);
				$this->getLevel()->broadcastLevelSoundEvent(
					$this,
					LevelSoundEventPacket::SOUND_THROW,
					0,
					EntityIds::PLAYER
				);
			}

			if($throwAnimation){
				$this->broadcastEntityEvent(ActorEventPacket::ARM_SWING);
			}
			return true;
		}
		return false;
	}

	/**
	 * @param ProjectileItem $item
	 * @param Vector3        $directionVector
	 * @param int            $yaw
	 * @param int            $pitch
	 *
	 * @return CompoundTag
	 *
	 * Creates a projectile NBT Tag compound.
	 */
	protected function createProjectileNBT(ProjectileItem $item, Vector3 $directionVector, int $yaw, int $pitch) : CompoundTag{
		$nbt = Entity::createBaseNBT(
			$this->add(0, $this->getEyeHeight(), 0),
			$directionVector,
			$yaw,
			$pitch
		);
		if($item instanceof ItemSplashPotion){
			$nbt->setShort("PotionId", $item->getDamage());
		}
		return $nbt;
	}

	/**
	 * @param ProjectileItem $item
	 *
	 * @return string
	 *
	 * Gets the projectile entity type from item.
	 */
	protected function getProjectileTypeFromItem(ProjectileItem $item) : string{
		return $item->getProjectileEntityType();
	}

	/**
	 * @param Projectile $projectile
	 *
	 * Called when the projectile is thrown.
	 */
	protected function onProjectileThrown(Projectile $projectile) : void{
	}

	/**
	 * @return Entity|null
	 *
	 * Gets the fishing entity.
	 */
	public function getFishingEntity() : ?Entity{
		return $this;
	}
}
