<?php

declare(strict_types=1);

namespace mineceit\game\entities\bots;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\TextFormat;

class EasyBot extends AbstractCombatBot{

	protected $horizontalKnockback = 0.305;
	protected $verticalKnockback = 0.530;

	protected $agroPearlCooldownTime = 175;
	protected $potCooldownTime = 80;

	/**
	 * @return string
	 *
	 * Gets the bot's nametag.
	 */
	protected function getBotNameTag() : string{
		return TextFormat::GREEN . 'Easy' . TextFormat::WHITE . ' Bot [' . TextFormat::LIGHT_PURPLE . (int) $this->getHealth() . TextFormat::WHITE . ']';
	}

	/**
	 * Function for implementing each bot's behavior.
	 *
	 * @param $tickDiff - The tick differential.
	 */
	protected function doBotBehavior(int $tickDiff) : void{
		if(
			$this->target === null
			|| $this->isRefillingPots()
		){
			parent::doBotBehavior($tickDiff);
			return;
		}

		$position = $this->getTarget()->asVector3();
		$x = $position->x - $this->getX() - 1;
		$z = $position->z - $this->getZ() - 1;
		if(($x != 0 || $z != 0) && !$this->wasRecentlyHit()){
			$this->motion->x = $this->getSpeed() * 0.35 * ($x / (abs($x) + abs($z)));
			$this->motion->z = $this->getSpeed() * 0.35 * ($z / (abs($x) + abs($z)));
		}

		if($this->getTarget()->isSprinting()){
			$this->setSprinting(true);
		}

		if($this->getHealth() < 7){
			if($this->canPot()){
				$this->pot();
			}else{
				if(!$this->wasRecentlyHit()){
					$this->move($this->motion->x, $this->motion->y, $this->motion->z);
				}
				$this->attackTargetPlayer();
			}
		}else{
			if(!$this->wasRecentlyHit()){
				$this->move($this->motion->x, $this->motion->y, $this->motion->z);
			}
			$this->attackTargetPlayer();
		}

		if($this->canPot() && $this->getHealth() <= 10){
			$this->pot();
		}

		$distanceToTarget = $this->distance($this->getTarget());
		if($distanceToTarget > 20){
			$this->pearl();
		}elseif(
			$distanceToTarget > 0.25 && $distanceToTarget < 4
			&& $this->getTarget()->getHealth() <= 15 && $this->canAgroPearl()
		){
			$this->pearl(true);
		}
		parent::doBotBehavior($tickDiff);
	}

	protected function canPot() : bool{
		return $this->potsRemaining > 10 && parent::canPot();
	}

	protected function pot() : void{
		$this->yaw = ($this->yaw <= 0) ? 192 : -$this->yaw;
		$this->pitch = 93;
		parent::pot();
	}

	public function attackTargetPlayer() : void{
		$player = $this->getTarget();
		$this->lookAt($player->asVector3());
		$this->getInventory()->setHeldItemIndex(0);
		// 40% Chance of attacking the current player.
		if(mt_rand(0, 100) <= 40){
			if($this->distance($player) <= 3){
				$event = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 8);
				if($this->isAlive()){
					$this->broadcastEntityEvent(ActorEventPacket::ARM_SWING);
					if($player->isOnline()){
						$player->attack($event);
						$volume = 0x10000000 * (min(30, 10) / 5);
						$player->getLevel()->broadcastLevelSoundEvent($player->asVector3(), LevelSoundEventPacket::SOUND_ATTACK, (int) $volume);
					}
				}
			}elseif($this->distance($player) > 3 && $this->distance($player) <= 4){
				if($this->isAlive()){
					$this->broadcastEntityEvent(ActorEventPacket::ARM_SWING);
					$volume = 0x10000000 * (min(30, 10) / 5);
					$player->getLevel()->broadcastLevelSoundEvent($this->asVector3(), LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE, (int) $volume);
				}
			}
		}else{
			if($this->distance($player) <= 4){
				if($this->isAlive()){
					$this->broadcastEntityEvent(ActorEventPacket::ARM_SWING);
					$volume = 0x10000000 * (min(30, 10) / 5);
					$player->getLevel()->broadcastLevelSoundEvent($this->asVector3(), LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE, (int) $volume);
				}
			}
		}
	}
}
