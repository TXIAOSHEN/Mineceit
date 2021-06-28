<?php

declare(strict_types=1);

namespace mineceit\game\entities\bots;

use mineceit\game\entities\GenericHuman;
use mineceit\player\MineceitPlayer;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;

abstract class AbstractBot extends GenericHuman{
	/* @var bool
	 * Determines whether the bot is moving.
	 */
	protected $_canMove = false;

	protected $verticalKnockback = 0.4;
	protected $horizontalKnockback = 0.4;

	/* @var MineceitPlayer|null
	 * The target of the bot.
	 */
	protected $target = null;

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->onBotConstruct();
		$this->setHealth(20.0);
		$this->setImmobile(true);
	}

	/**
	 * @return void
	 * Called when the bot's constructor is called.
	 */
	abstract protected function onBotConstruct() : void;

	/**
	 * @return void
	 * Gives the items to the bot.
	 */
	// TODO: Should this be a kit?
	/**
	 * @param string   $name
	 * @param Skin     $skin
	 * @param Position $spawnPos
	 *
	 * @return CompoundTag
	 *
	 * Gets the human NBT for a bot.
	 */
	public static function getHumanNBT(string $name, Skin $skin, Position $spawnPos) : CompoundTag{
		$nbt = Entity::createBaseNBT($spawnPos, null, 0, 0);
		$nbt->setShort("Health", 20);
		$nbt->setString("CustomName", $name);
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

	public function setCanMove(bool $move) : void{
		$this->_canMove = $move;
		$this->setImmobile(!$move);
	}


	abstract public function giveItems() : void;

	/**
	 * @return MineceitPlayer|null
	 *
	 * Gets the target of the bot.
	 */
	public function getTarget() : ?MineceitPlayer{
		return $this->target;
	}

	/**
	 * @param MineceitPlayer|null $player
	 *
	 * Sets the target of the bot.
	 */
	public function setTarget(?MineceitPlayer $player) : void{
		$this->target = $player;
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);
		if(!$this->isAlive()){
			if(!$this->closed){
				$this->flagForDespawn();
			}
			return false;
		}

		$this->setNameTag($this->getBotNameTag());
		if(!$this->canMove()){
			$this->setHealth(20.0);
			return $hasUpdate;
		}

		$this->doBotBehavior($tickDiff);
		return $hasUpdate;
	}

	/**
	 * @return string
	 *
	 * Gets the bot's nametag.
	 */
	abstract protected function getBotNameTag() : string;

	public function canMove() : bool{
		return $this->_canMove;
	}

	/**
	 * Function for implementing each bot's behavior.
	 *
	 * @param $tickDiff - The tick differential.
	 */
	abstract protected function doBotBehavior(int $tickDiff) : void;

	/**
	 * @param Entity $attacker
	 * @param float  $damage
	 * @param float  $x
	 * @param float  $z
	 * @param float  $base
	 *
	 * Called to apply knockback to the bot.
	 */
	public function knockBack(Entity $attacker, float $damage, float $x, float $z, float $base = 0.4) : void{
		$f = sqrt($x * $x + $z * $z);
		if($f <= 0){
			return;
		}
		if(mt_rand() / mt_getrandmax() > $this->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
			$f = 1 / $f;
			$motion = clone $this->motion;
			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $this->horizontalKnockback;
			$motion->y += $this->verticalKnockback;
			$motion->z += $z * $f * $this->horizontalKnockback;
			if($motion->y > $this->verticalKnockback){
				$motion->y = $this->verticalKnockback;
			}
			if($this->isAlive() && !$this->isClosed()){
				$this->setMotion($motion);
			}
			$this->onKnockbackSuccess();
		}
	}

	/**
	 * Called when the knockback was successful.
	 */
	protected function onKnockbackSuccess() : void{
	}
}
