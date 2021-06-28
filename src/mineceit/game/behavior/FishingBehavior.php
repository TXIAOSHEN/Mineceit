<?php

declare(strict_types=1);

namespace mineceit\game\behavior;


use mineceit\game\entities\FishingHook;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\entity\Human;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\Server;

class FishingBehavior{
	/** @var FishingHook|null */
	private $fishing = null;
	/** @var IFishingBehaviorEntity|null */
	private $parent;
	/** @var bool - Determines if the behavior damages a fishing rod after use. */
	private $damageRod = false;

	/** @var Server */
	private $server;

	public function __construct(IFishingBehaviorEntity $human){
		$this->parent = $human;
		$this->server = Server::getInstance();
	}

	public function setDamageRod(bool $damageRod) : void{
		$this->damageRod = $damageRod;
	}

	public function DoesDamageRod() : bool{
		return $this->damageRod;
	}

	/**
	 * @param bool $animate - Sets the behaviour to animate.
	 * Start fishing.
	 */
	public function startFishing(bool $animate = false) : void{
		$parent = $this->parent->getFishingEntity();
		if(
			$parent === null
			|| !$parent->isAlive()
			|| $this->isFishing()
		){
			return;
		}

		$parent = $this->getParent();
		if($parent instanceof InventoryHolder){
			$itemInHand = $parent->getInventory()->getItemInHand();
			if($itemInHand->getId() !== Item::FISHING_ROD){
				return;
			}
		}

		$position = $parent->asVector3();
		$yaw = $parent->yaw;
		$pitch = $parent->pitch;
		$tag = Entity::createBaseNBT(
			$position->add(0.0, $parent->getEyeHeight(), 0.0),
			$parent->getDirectionVector(),
			$yaw,
			$pitch
		);
		$rod = Entity::createEntity("FishingHook", $parent->getLevelNonNull(), $tag, $parent);

		if($rod !== null && $rod instanceof FishingHook){
			$x = -sin(deg2rad($yaw)) * cos(deg2rad($pitch));
			$y = -sin(deg2rad($pitch));
			$z = cos(deg2rad($yaw)) * cos(deg2rad($pitch));
			$rod->setMotion(new Vector3($x, $y, $z));

			$event = new ProjectileLaunchEvent($rod);
			$event->call();

			if($event->isCancelled()){
				$rod->flagForDespawn();
				return;
			}

			$rod->spawnToAll();
			$parent->getLevel()->broadcastLevelSoundEvent(
				$parent,
				LevelSoundEventPacket::SOUND_THROW,
				0,
				EntityIds::PLAYER
			);
			$this->fishing = $rod;

			if($animate){
				$this->swingArm();
			}
		}
	}

	public function isFishing() : bool{
		return $this->fishing !== null;
	}

	/**
	 * @return Human|null
	 *
	 * Gets the parent of the human.
	 */
	public function getParent() : ?Entity{
		return $this->parent->getFishingEntity();
	}

	/**
	 * Swings an arm of the human.
	 */
	private function swingArm(){
		$pkt = new AnimatePacket();
		$pkt->action = AnimatePacket::ACTION_SWING_ARM;
		$pkt->entityRuntimeId = $this->getParent()->getId();
		$this->server->broadcastPacket(
			$this->getParent()->getLevel()->getPlayers(),
			$pkt
		);
	}

	/**
	 * Stops the parent from fishing.
	 *
	 * @param bool $switchedItem
	 * @param bool $animate
	 */
	public function stopFishing(bool $switchedItem, bool $animate = false) : void{
		if($this->isFishing()){
			$this->fishing->reelLine();
			if($animate){
				$this->swingArm();
			}

			$this->fishing = null;

			if(!$switchedItem && $this->damageRod){
				$parent = $this->getParent();
				if(
					$parent instanceof Player
					&& ($parent->isSpectator() || $parent->isCreative())
				){
					return;
				}

				if($parent instanceof InventoryHolder){
					$inv = $parent->getInventory();
					$itemInHand = $inv->getItemInHand();
					$itemInHand->setDamage($itemInHand->getDamage() + 1);
					if($itemInHand->getDamage() > 65){
						$itemInHand = Item::get(ItemIds::AIR);
					}
					$inv->setItemInHand($itemInHand);
				}
			}
		}
	}
}
