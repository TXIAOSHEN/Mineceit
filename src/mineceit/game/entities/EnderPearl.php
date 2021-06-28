<?php

declare(strict_types=1);

namespace mineceit\game\entities;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\level\Level;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\utils\Random;

class EnderPearl extends Projectile{

	public const NETWORK_ID = self::ENDER_PEARL;

	public $width = 0.2;
	public $height = 0.2;
	public $gravity = 0.1;

	public function __construct(Level $level, CompoundTag $nbt, ?Entity $owner = null){
		parent::__construct($level, $nbt, $owner);
		if($owner instanceof Human){
			$this->setPosition($this->add(0, $owner->getEyeHeight()));
			$this->setMotion($owner->getDirectionVector()->multiply(1));
			$this->handleMotion($this->motion->x, $this->motion->y, $this->motion->z, 1.3, 1, $owner);
		}
	}

	public function handleMotion(float $x, float $y, float $z, float $f1, float $f2, Entity $owner) : void{
		$rand = new Random();
		$f = sqrt($x * $x + $y * $y + $z * $z);
		$x = $x / (float) $f;
		$y = $y / (float) $f;
		$z = $z / (float) $f;
		$x = $x + $rand->nextSignedFloat() * 0.007499999832361937 * (float) $f2;
		$y = $y + $rand->nextSignedFloat() * 0.008599999832361937 * (float) $f2;
		$z = $z + $rand->nextSignedFloat() * 0.007499999832361937 * (float) $f2;
		$x = $x * (float) $f1;
		$y = $y * (float) $f1;
		$z = $z * (float) $f1;
		$this->motion->x += $x;
		$this->motion->y += $y * 1.40;
		$this->motion->z += $z;
	}

	public function initEntity() : void{
		parent::initEntity();
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);
		$owner = $this->getOwningEntity();
		if($owner === null || !$owner->isAlive() || $owner->isClosed() || $this->isCollided){
			$this->flagForDespawn();
		}
		return $hasUpdate;
	}

	public function onHit(ProjectileHitEvent $event) : void{
		$owner = $this->getOwningEntity();
		if($owner !== null){
			$this->level->broadcastLevelEvent($owner, LevelEventPacket::EVENT_PARTICLE_ENDERMAN_TELEPORT);
			$this->level->addSound(new EndermanTeleportSound($owner));
			$owner->teleport($event->getRayTraceResult()->getHitVector());
			$this->level->addSound(new EndermanTeleportSound($owner));
		}
	}

	public function applyGravity() : void{
		if($this->isUnderwater()){
			$this->motion->y += $this->gravity;
		}else{
			parent::applyGravity();
		}
	}

	public function getResultDamage() : int{
		return -1;
	}

	public function close() : void{
		parent::close();
	}
}
