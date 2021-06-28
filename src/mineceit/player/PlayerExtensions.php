<?php

declare(strict_types=1);

namespace mineceit\player;

use mineceit\bossbar\BossBar;
use mineceit\kits\info\KnockbackInfo;
use mineceit\MineceitCore;
use mineceit\misc\AbstractListener;
use mineceit\parties\events\types\PartyGames;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\Player;

/**
 * Class PlayerExtensions
 * @package mineceit\player
 *
 * This class implements various extension functions that would otherwise
 * belong in the player class if we were writing in the pocketmine class.
 */
class PlayerExtensions extends AbstractListener{
	/** @var Player */
	private $player;
	/** @var BossBar */
	private $bossBar;
	/** @var bool - Determines if the player is in fake spectator mode. */
	private $fakeSpectator = false;

	/** @var Vector3|null */
	private $initialKnockbackMotion = false, $shouldCancelKBMotion = false;

	public function __construct(Player $player){
		parent::__construct(MineceitCore::getInstance());
		$this->player = $player;
		$this->bossBar = new BossBar($player);
	}

	public function getBossBar() : BossBar{
		return $this->bossBar;
	}

	public function enableFlying(bool $flying) : void{
		$this->player->setAllowFlight($flying);
		$this->player->setFlying($flying);
	}

	public function clearAll() : void{
		$this->player->setHealth($this->player->getMaxHealth());
		$this->player->setFood($this->player->getMaxFood());
		$this->player->setSaturation($this->getMaxSaturation());
		$this->clearInventory();
		$this->player->removeAllEffects();
		$this->setXpAndProgress(0, 0.0);
	}

	/**
	 * @return float
	 *
	 * Gets the maximum saturation.
	 */
	public function getMaxSaturation() : float{
		return $this->getPlayer()->getAttributeMap()
			->getAttribute(Attribute::SATURATION)->getMaxValue();
	}

	public function getPlayer() : ?Player{
		return $this->player;
	}

	/**
	 * Clears the inventory of the player.
	 */
	public function clearInventory() : void{
		$this->player->getInventory()->clearAll();
		$this->player->getArmorInventory()->clearAll();
	}

	/**
	 * @param int   $level
	 * @param float $progress
	 *
	 * Sets the xp Level and progress.
	 */
	public function setXpAndProgress(int $level, float $progress){
		$this->player->setXpLevel($level);
		$this->player->setXpProgress($progress);
	}

	/**
	 * @param EntityMotionEvent $event
	 *
	 * Called when the entity motion is called.
	 */
	public function onEntityMotion(EntityMotionEvent $event) : void{
		if(
			$this->player === null
			|| !$this->player->isOnline()
		){
			return;
		}
		$player = $event->getEntity();
		if(
			$player instanceof Player
			&& $player->getName() === $this->player->getName()
		){
			if($this->initialKnockbackMotion){
				$this->initialKnockbackMotion = false;
				$this->shouldCancelKBMotion = true;
			}elseif($this->shouldCancelKBMotion){
				// Cancels the next motion according to knockback.
				$this->shouldCancelKBMotion = false;
				$event->setCancelled();
			}
		}
	}

	/**
	 * @param EntityDamageByEntityEvent $event
	 *
	 * Called when the player gets damaged by an entity.
	 */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event){
		if(
			$this->player === null
			|| !$this->player->isOnline()
		){
			return;
		}

		$player = $event->getEntity();
		if(
			$player instanceof MineceitPlayer
			&& $player->getName() == $this->player->getName()
			&& !($event instanceof EntityDamageByChildEntityEvent)
			&& !$event->isCancelled()
		){
			if($this->isSpectator()){
				$event->setCancelled();
				return;
			}
			$attacker = $event->getDamager();
			if(
				$attacker instanceof MineceitPlayer
				&& $attacker->getName() !== $this->player->getName()
			){
				$attackerKit = $attacker->getKitHolder()->getKit();
				$attackedKit = $player->getKitHolder()->getKit();
				if($attackedKit !== null && $attackedKit->equals($attackerKit)){
					// Makes sure no added motion is applied to the traditional knockback.
					// Overrides the knockback of the player.
					// $this->knockBack($attacker, $knockbackInfo);
					$knockbackInfo = $attackerKit->getKnockbackInfo();
					$event->setKnockBack(0.0);
					$event->setAttackCooldown($knockbackInfo->getSpeed());
					$this->knockBack($attacker, $knockbackInfo);
				}
			}
		}
	}

	public function isSpectator() : bool{
		return $this->player->isSpectator() || $this->isFakeSpectator();
	}

	public function isFakeSpectator() : bool{
		return $this->fakeSpectator;
	}

	/**
	 * Sets the player in a fake spectator mode.
	 *
	 * @param bool $spec
	 */
	public function setFakeSpectator(bool $spec = true) : void{
		$player = $this->getPlayer();
		$player->setGamemode($spec ?
			GameMode::ADVENTURE : GameMode::SURVIVAL);
		$player->setInvisible($spec);
		$player->setAllowFlight($spec);
		$player->setFlying($spec);
		$this->fakeSpectator = $spec;
	}

	/**
	 * @param Entity        $entity
	 * @param KnockbackInfo $info
	 *
	 * A custom knockback function that overrides the default knockback.
	 */
	private function knockBack(Entity $entity, KnockbackInfo $info) : void{
		$xzKb = $info->getHorizontalKb();
		$yKb = $info->getVerticalKb();
		$x = $this->player->getX() - $entity->x;
		$z = $this->player->getZ() - $entity->z;
		$f = sqrt($x * $x + $z * $z);
		if($f <= 0){
			return;
		}

		if(mt_rand() / mt_getrandmax() > $this->player->getAttributeMap()
				->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()
		){
			$f = 1 / $f;
			$motion = clone $this->player->getMotion();
			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $xzKb;
			$motion->y += $yKb;
			$motion->z += $z * $f * $xzKb;
			if($motion->y > $yKb){
				$motion->y = $yKb;
			}
			$this->initialKnockbackMotion = true;
			$this->player->setMotion($motion);
		}
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 */
	public function onPacketReceive(DataPacketReceiveEvent $event) : void{
		if(
			$this->player === null
			|| !$this->player->isOnline()
		){
			return;
		}

		$player = $event->getPlayer();
		$packet = $event->getPacket();

		if($player instanceof MineceitPlayer && $player->getName() == $this->player->getName()){
			if($packet instanceof EmotePacket){
				$player->getServer()->broadcastPacket($player->getViewers(), EmotePacket::create($player->getId(), $packet->getEmoteId(), EmotePacket::FLAG_SERVER));
			}elseif($packet instanceof PlayerActionPacket){
				$player->setAction($packet->action);
			}elseif($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE){
				$player->getClicksInfo()->addClick(false);
				if($player->getClientInfo()->isPE()){

					$item = $player->getInventory()->getItemInHand();
					$id = $item->getId();
					if($id === Item::FISHING_ROD){
						$player->useRod(true);
					}elseif($id === Item::ENDER_PEARL && $player->canThrowPearl()){
						$player->throwPearl($item, true);
					}elseif($id === Item::SPLASH_POTION){
						$player->throwPotion($item, true);
					}
				}
			}elseif($packet instanceof InventoryTransactionPacket){
				if($packet->trData instanceof UseItemOnEntityTransactionData){
					$player->getClicksInfo()->addClick(false);
					if(($target = $player->getLevel()->getEntity($packet->trData->getEntityRuntimeId())) instanceof MineceitPlayer
						&& $target->isAlive() && $player->canInteract($target, 8)
					){
						if($target->isInArena() && $player->isInArena()){
							$arena = $player->getArena();
							if($arena->getName() === 'Knock' && ($arena->isWithinProtection($player) || $arena->isWithinProtection($target))){
								$event->setCancelled();
							}
						}elseif(($partyEvent = $player->getPartyEvent()) instanceof PartyGames){
							$arena = $partyEvent->getArena();
							if($partyEvent->getKit() === 'Knock' && ($arena->isWithinProtection($player) || $arena->isWithinProtection($target))){
								$event->setCancelled();
							}
						}
					}
				}elseif($packet->trData instanceof ReleaseItemTransactionData && $packet->trData->getActionType() === ReleaseItemTransactionData::ACTION_RELEASE){
					if($player->isInArena()){
						$arena = $player->getArena();
						if($arena->getName() === 'Knock' && $arena->isWithinProtection($player)){
							$event->setCancelled();
						}
					}elseif(($partyEvent = $player->getPartyEvent()) instanceof PartyGames){
						$arena = $partyEvent->getArena();
						if($partyEvent->getKit() === 'Knock' && $arena->isWithinProtection($player)){
							$event->setCancelled();
						}
					}
				}
			}
		}
	}

	/**
	 * @param PlayerGameModeChangeEvent $changeEvent
	 *
	 * Called when a player's gamemode is changed.
	 */
	public function onGameModeChange(PlayerGameModeChangeEvent $changeEvent){
		if(
			$this->player === null
			|| !$this->player->isOnline()
		){
			return;
		}

		$player = $changeEvent->getPlayer();
		if(
			$player->getName() == $this->player->getName()
			&& $this->fakeSpectator
		){
			$this->fakeSpectator = false;
			$this->player->setGamemode(GameMode::SURVIVAL);
			$this->player->setFlying(false);
			$this->player->setAllowFlight(false);
			$this->player->setInvisible(false);
		}
	}
}
