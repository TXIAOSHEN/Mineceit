<?php

declare(strict_types=1);

namespace mineceit\game\entities;

use mineceit\game\behavior\IFishingBehaviorEntity;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\RayTraceResult;
use pocketmine\network\mcpe\protocol\ActorEventPacket;

class FishingHook extends Projectile{

	public const NETWORK_ID = self::FISHING_HOOK;

	public $caught = false;
	public $height = 0.15;
	public $width = 0.15;
	public $gravity = 0.075;
	public $drag = 0.05;

	private $attachedEntity = null;

	public function onUpdate(int $currentTick) : bool{
		if($this->isFlaggedForDespawn() || !$this->isAlive()){
			return false;
		}

		$this->timings->startTiming();
		$update = parent::onUpdate($currentTick);

		if(!$this->isCollidedVertically){
			$this->motion->x *= 1.13;
			$this->motion->z *= 1.13;
			$this->motion->y -= $this->gravity * -0.04;

			if($this->isUnderwater()){
				$this->motion->z = 0;
				$this->motion->x = 0;
				$difference = floatval($this->getWaterHeight() - $this->y);

				if($difference > 0.15){
					$this->motion->y += 0.1;
				}else{
					$this->motion->y += 0.01;
				}
			}

			$update = true;
		}elseif($this->isCollided && $this->keepMovement){

			$this->motion->x = 0;
			$this->motion->y = 0;
			$this->motion->z = 0;
			$this->keepMovement = false;
			$update = true;
		}

		if($this->isOnGround()){
			$this->motion->y = 0;
		}

		if(($source = $this->getOwningEntity()) != null && $source instanceof Human){
			$itemInHand = $source->getInventory()->getItemInHand();
			if(
				$source->distance($this) > 35
				|| $itemInHand->getId() !== Item::FISHING_ROD
				|| $this->attachedEntity !== null
			){

				$this->kill();
				$this->close();

				if($source instanceof IFishingBehaviorEntity){
					$behavior = $source->getFishingBehavior();
					if($behavior->isFishing()){
						$behavior->stopFishing(true);
					}
				}
			}
		}

		$this->timings->stopTiming();
		return $update;
	}

	public function getWaterHeight() : int{
		$floorY = $this->getFloorY();
		for($y = $floorY; $y < 256; $y++){
			$id = $this->getLevel()->getBlockIdAt($this->getFloorX(), $y, $this->getFloorZ());
			if($id === 0){
				return $y;
			}
		}

		return $floorY;
	}

	public function reelLine() : void{
		$e = $this->getOwningEntity();

		if($e instanceof Human && $this->caught){
			$this->broadcastEntityEvent(ActorEventPacket::FISH_HOOK_TEASE, 0, $this->getLevel()->getPlayers());
		}

		if(!$this->closed){
			$this->kill();
			$this->close();
		}
	}

	public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : void{
		$damage = $this->getResultDamage();

		$this->attachedEntity = $entityHit;

		if($damage >= 0){

			if($this->getOwningEntity() === null){
				$ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
			}else{
				$ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
			}

			$entityHit->attack($ev);

			if($this->isOnFire()){
				$ev = new EntityCombustByEntityEvent($this, $entityHit, 5);
				$ev->call();
				if(!$ev->isCancelled()){
					$entityHit->setOnFire($ev->getDuration());
				}
			}
		}
	}

	public function getResultDamage() : int{
		return parent::getResultDamage();
	}
}
