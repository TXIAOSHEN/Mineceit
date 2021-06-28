<?php

declare(strict_types=1);

namespace mineceit\game\entities\bots;

use mineceit\game\behavior\kits\IKitHolderEntity;
use mineceit\game\behavior\kits\KitHolder;
use mineceit\MineceitCore;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ProjectileItem;
use pocketmine\level\Position;

/**
 * Class AbstractCombatBot
 * @package mineceit\game\entities\bots
 *
 * Combat bot used specifically for bots that are directly attacking the player.
 */
abstract class AbstractCombatBot extends AbstractBot implements IKitHolderEntity{
	protected $currentTick = 0;
	/** @var float - The bot speed. */
	protected $botSpeed = 0.4;
	/** @var int - The number of pearls remaining. */
	protected $pearlsRemaining = 16;
	/** @var int - The last tick the pearl was thrown in Milliseconds. */
	protected $lastPearlTimeMillis = -1;
	/** @var int - The last tick an agro pearl was thrown. */
	protected $lastAgroPearlTick = -1;
	/** @var int - The agro pearl cooldown ticks. */
	protected $agroPearlCooldownTime = 50;
	/** @var int - The number of splash potions remaining. */
	protected $potsRemaining = 33;
	/** @var int - The tick that the bot last pot. */
	protected $lastPotTick = -1;
	/** @var int - The number of ticks to refill. */
	protected $ticksToRefill = 80;
	/** @var int - The end of the refill tick. */
	protected $endRefillTick = -1;
	/** @var int - The number of ticks until bot can pot again after. */
	protected $potCooldownTime = 100;
	/** @var int - The tick that the bot was hit. */
	protected $tickHit = -1;
	/** @var KitHolder|null */
	private $kitHolder = null;

	public function giveItems() : void{
		// TODO: Check if the extra parameters on the Enchantment instances mean anything.
		$sword = Item::get(Item::DIAMOND_SWORD);
		$unbreaking = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3);
		$protection = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), 2);
		$sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName('sharpness'), 2));
		$sword->addEnchantment($unbreaking);
		$this->getInventory()->setItem(0, $sword);
		$this->getInventory()->setItem(1, Item::get(Item::ENDER_PEARL, 0, 16));
		$this->getInventory()->setItem(2, Item::get(Item::SPLASH_POTION, 22));
		$helmet = Item::get(Item::DIAMOND_HELMET);
		$helmet->addEnchantment($protection);
		$helmet->addEnchantment($unbreaking);
		$chestplate = Item::get(Item::DIAMOND_CHESTPLATE);
		$chestplate->addEnchantment($protection);
		$chestplate->addEnchantment($unbreaking);
		$leggings = Item::get(Item::DIAMOND_LEGGINGS);
		$leggings->addEnchantment($protection);
		$leggings->addEnchantment($unbreaking);
		$boots = Item::get(Item::DIAMOND_BOOTS);
		$boots->addEnchantment($protection);
		$boots->addEnchantment($unbreaking);
		$this->getArmorInventory()->setHelmet($helmet);
		$this->getArmorInventory()->setChestplate($chestplate);
		$this->getArmorInventory()->setLeggings($leggings);
		$this->getArmorInventory()->setBoots($boots);
		$this->getInventory()->setHeldItemIndex(0);
		$effect = Effect::getEffect(1);
		$this->addEffect(new EffectInstance($effect, 72000, 0, true));
	}

	public function attack(EntityDamageEvent $source) : void{
		if($source->getCause() === EntityDamageEvent::CAUSE_FALL){
			$source->setCancelled();
		}elseif($source->getCause() === EntityDamageEvent::CAUSE_VOID){
			$source->setCancelled();

			$botHandler = MineceitCore::getBotHandler();
			$duel = $botHandler->getDuel($this->getTarget());
			$spawnPos = $duel->getArena()->getP2SpawnPos();
			$x = $spawnPos->getX();
			$y = $spawnPos->getY();
			$z = $spawnPos->getZ();
			$this->teleport(new Position($x, $y, $z, $this->getLevel()));
		}

		parent::attack($source);

		if(!$source->isCancelled()){
			$this->tickHit = $this->currentTick;
		}
	}

	public function getSpeed() : float{
		return $this->botSpeed;
	}

	/**
	 * @return KitHolder|null
	 *
	 * Gets the kit holder for the bot.
	 */
	public function getKitHolder() : ?KitHolder{
		$this->kitHolder = $this->kitHolder ?? new KitHolder($this);
		return $this->kitHolder;
	}

	/**
	 * @return Human|null
	 *
	 * Gets the kit holder entity.
	 */
	public function getKitHolderEntity() : ?Human{
		return $this;
	}

	protected function onBotConstruct() : void{
	}

	protected function doBotBehavior(int $tickDiff) : void{
		// TODO: Implementation
		$this->currentTick += $tickDiff;
	}

	protected function getProjectileTypeFromItem(ProjectileItem $item) : string{
		switch($item->getId()){
			// Sets the projectile type to a bot potion.
			case Item::SPLASH_POTION:
				return "BotPotion";
		}
		return parent::getProjectileTypeFromItem($item);
	}

	protected function canPot() : bool{
		return $this->potsRemaining > 0 &&
			$this->currentTick >= ($this->lastPotTick + $this->potCooldownTime);
	}

	protected function isRefillingPots() : bool{
		return $this->endRefillTick > 0 && $this->currentTick < $this->endRefillTick;
	}

	protected function pot() : void{
		$this->getInventory()->setHeldItemIndex(2);
		$this->onClickAir(Item::get(Item::SPLASH_POTION, 22), $this->getDirectionVector(), true);
		$this->potsRemaining--;
		if(
			$this->potsRemaining === 26 || $this->potsRemaining === 19
			|| $this->potsRemaining === 12 || $this->potsRemaining === 5
		){
			$this->yaw = 180;
			$this->pearl();
			$this->endRefillTick = $this->currentTick + $this->ticksToRefill;
		}
		$this->lastPotTick = $this->currentTick;
	}

	protected function pearl(bool $agroPearl = false) : void{
		if($this->pearlsRemaining <= 0){
			return;
		}

		$currentTimeMillis = time();
		if($currentTimeMillis <= $this->lastPearlTimeMillis){
			return;
		}

		$this->lastPearlTimeMillis = $currentTimeMillis + 10;
		$this->pearlsRemaining--;
		$this->lookAt($this->getTarget()->asVector3());
		$this->getInventory()->setHeldItemIndex(1);
		$directionVector = $this->getDirectionVector()->add(0, 0.2, 0);
		if($agroPearl){
			$this->lastAgroPearlTick = $this->currentTick;
			$directionVector = $this->getDirectionVector()->add(0, 0.1, 0);
		}
		$this->onClickAir(Item::get(Item::ENDER_PEARL), $directionVector, true);
	}

	protected function canAgroPearl() : bool{
		return $this->lastAgroPearlTick > 0 && $this->currentTick >=
			($this->lastAgroPearlTick + $this->agroPearlCooldownTime);
	}

	protected function wasRecentlyHit(int $threshold = 4) : bool{
		return $this->tickHit >= 0 && ($this->currentTick - $this->tickHit <= $threshold);
	}
}
