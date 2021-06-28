<?php

declare(strict_types=1);

namespace mineceit\game\entities\bots;

use mineceit\MineceitCore;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClutchBot extends AbstractBot{

	protected $horizontalKnockback = 0.0;
	protected $verticalKnockback = 0.0;

	/** @var int */
	private $hittedTick = 0;

	/** @var int */
	private $hitreg = 1;

	/** @var float */
	private $knockback = 0.4;

	/** @var int */
	private $attackcooldown = 20;

	/** @var int */
	private $currentTicks = 0;


	public function setCanMove(bool $move) : void{
		if($move){
			$this->hittedTick = $this->currentTicks;
		}
		parent::setCanMove($move);
	}

	public function giveItems() : void{
		$this->getInventory()->setItem(0, Item::get(Item::SNOWBALL));
		$this->getInventory()->setHeldItemIndex(0);
		$effect = Effect::getEffect(10);
		$this->addEffect(new EffectInstance($effect, 72000, 9, false));
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

		if($source->isCancelled()){
			return;
		}
		if($source instanceof EntityDamageByEntityEvent){
			$killer = $source->getDamager();
			if($killer instanceof Player){
				$this->knockBack($killer, 0, 0, 0);
			}
		}
	}

	public function getAttackCoolDown() : int{
		return $this->attackcooldown / 20;
	}

	public function setAttackCoolDown(int $cooldown) : void{
		$this->attackcooldown = $cooldown * 20;
	}

	public function getHitReg() : int{
		return $this->hitreg;
	}

	public function setHitReg(int $hit) : void{
		$this->hitreg = $hit;
	}

	public function getKnockBack() : int{
		return (int) ($this->knockback * 10);
	}

	public function setKnockBack(int $kb) : void{
		$this->knockback = ((float) $kb) / 10;
	}

	/**
	 * @return void
	 * Called when the bot's constructor is called.
	 */
	protected function onBotConstruct() : void{
	}

	/**
	 * @return string
	 *
	 * Gets the bot's nametag.
	 */
	protected function getBotNameTag() : string{
		return TextFormat::LIGHT_PURPLE . 'Clutch' . TextFormat::WHITE . ' Bot [' . TextFormat::LIGHT_PURPLE . (int) $this->getHealth() . TextFormat::WHITE . ']';
	}

	/**
	 * Function for implementing each bot's behavior.
	 *
	 * @param $tickDiff - The tick differential.
	 */
	protected function doBotBehavior(int $tickDiff) : void{
		if($this->target === null){
			return;
		}

		$this->currentTicks += $tickDiff;
		$this->attackTargetPlayer();
	}

	public function attackTargetPlayer() : void{
		$player = $this->getTarget();
		$this->lookAt($player->asVector3());
		$diffTicks = $this->currentTicks - $this->hittedTick;
		$hitTotal = ($this->hitreg * 10);
		if($diffTicks <= $hitTotal){
			$event = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 0, [], $this->knockback);
			if($this->isAlive()){
				$this->broadcastEntityEvent(4);
				if($player->isOnline()){
					$player->attack($event);
					$volume = 0x10000000 * (min(30, 10) / 5);
					$player->getLevel()->broadcastLevelSoundEvent($player->asVector3(), LevelSoundEventPacket::SOUND_ATTACK, (int) $volume);
				}
			}
		}else if($diffTicks > $hitTotal + $this->attackcooldown){
			$this->hittedTick = $this->currentTicks;
		}
	}
}
