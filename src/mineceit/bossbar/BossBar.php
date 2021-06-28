<?php

declare(strict_types=1);

namespace mineceit\bossbar;

use mineceit\utils\Math;
use pocketmine\entity\Attribute;
use pocketmine\entity\AttributeMap;
use pocketmine\entity\DataPropertyManager;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\Player;


class BossBar{

	/* @var Player */
	private $player;

	/** @var float */
	private $filledPercentage;
	/** @var string */
	private $bossBarText;
	/** @var bool - Boss bar enabled. */
	private $enabled;

	/** @var int - The boss entity id corresponding to the bossbar. */
	private $entityId;

	/** @var AttributeMap */
	private $attributeMap;
	/** @var DataPropertyManager */
	private $propertyManager;

	public function __construct(Player $player){
		$this->bossBarText = "";
		$this->enabled = false;
		$this->player = $player;
		$this->entityId = Entity::$entityCount++;

		$this->attributeMap = new AttributeMap();
		$this->attributeMap->addAttribute(
			Attribute::getAttribute(Attribute::HEALTH)
				->setMaxValue(100.0)
				->setMinValue(0.0)
				->setDefaultValue(100.0)
		);

		$this->propertyManager = new DataPropertyManager();
		$this->propertyManager->setLong(
			Entity::DATA_FLAGS,
			0
			^ 1 << Entity::DATA_FLAG_SILENT
			^ 1 << Entity::DATA_FLAG_INVISIBLE
			^ 1 << Entity::DATA_FLAG_NO_AI
			^ 1 << Entity::DATA_FLAG_FIRE_IMMUNE
		);

		$this->propertyManager->setShort(Entity::DATA_MAX_AIR, 400);
		$this->propertyManager->setString(Entity::DATA_NAMETAG, "");
		$this->propertyManager->setLong(Entity::DATA_LEAD_HOLDER_EID, -1);
		$this->propertyManager->setFloat(Entity::DATA_SCALE, 0);
		$this->propertyManager->setFloat(Entity::DATA_BOUNDING_BOX_WIDTH, 0.0);
		$this->propertyManager->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, 0.0);
	}

	public function isEnabled() : bool{
		return $this->enabled;
	}

	/**
	 * @param bool $enabled
	 *
	 * Sets the boss bar as enabled.
	 */
	public function setEnabled(bool $enabled) : void{
		if($this->enabled !== $enabled){
			if($this->enabled){
				$this->sendBossBar();
			}else{
				$this->removeBossBar();
			}
		}
		$this->enabled = $enabled;
	}

	/**
	 * Sends the boss bar.
	 */
	private function sendBossBar() : void{
		$this->addBossEntity();
		$this->removeBossBar();

		$pkt = new BossEventPacket();
		$pkt->bossEid = $this->entityId;
		$pkt->eventType = BossEventPacket::TYPE_SHOW;
		$pkt->title = $this->bossBarText;
		$pkt->healthPercent = $this->filledPercentage;
		$pkt->color = 1;
		$pkt->overlay = 1;
		$pkt->unknownShort = 0;
		$this->player->dataPacket($pkt);
	}

	/**
	 * Adds the boss entity corresponding to the boss bar.
	 */
	private function addBossEntity() : void{
		$pkt = new AddActorPacket();
		$pkt->entityRuntimeId = $this->entityId;
		$pkt->type = AddActorPacket::LEGACY_ID_MAP_BC[EntityIds::SLIME];
		$pkt->attributes = $this->attributeMap->getAll();
		$pkt->metadata = $this->propertyManager->getAll();
		$pkt->position = $this->player
			->subtract(0, 28);
		$this->player->dataPacket($pkt);
	}

	/**
	 * Removes the boss bar from the player.
	 */
	private function removeBossBar() : void{
		$pkt = new BossEventPacket();
		$pkt->bossEid = $this->player->getId();
		$pkt->eventType = BossEventPacket::TYPE_HIDE;
		$this->player->dataPacket($pkt);
	}

	/**
	 * @return float
	 *
	 * Returns a value between 0 & 100.
	 */
	public function getFilledPercentage() : float{
		return $this->filledPercentage;
	}

	/**
	 * @param float $percentage
	 *
	 * Sets the current filled percentage.
	 */
	public function setFilledPercentage(float $percentage) : void{
		$percentage = Math::clamp($percentage, 0.0, 100.0);

		if($this->filledPercentage !== $percentage
			&& $this->enabled){
			$this->attributeMap->getAttribute(Attribute::HEALTH)
				->setValue($percentage);
			$this->sendFilledPercentage($percentage);
		}
		$this->filledPercentage = $percentage;
	}

	/**
	 * @param float $percentage
	 *
	 * Sends the filled percentage.
	 */
	private function sendFilledPercentage(float $percentage) : void{
		$pkt = new BossEventPacket();
		$pkt->bossEid = $this->entityId;
		$pkt->eventType = BossEventPacket::TYPE_HEALTH_PERCENT;
		$pkt->healthPercent = $percentage;
		$this->player->dataPacket($pkt);
	}

	/**
	 * @param string $text
	 *
	 * Sets the text of the boss bar.
	 */
	public function setText(string $text) : void{
		if($text !== $this->bossBarText
			&& $this->enabled){
			$this->sendText($text);
		}
		$this->bossBarText = $text;
	}

	/**
	 * @param string $text
	 *
	 * Sends the text to the player.
	 */
	private function sendText(string $text) : void{
		$pkt = new BossEventPacket();
		$pkt->bossEid = $this->entityId;
		$pkt->eventType = BossEventPacket::TYPE_TITLE;
		$pkt->title = $text;
		$this->player->dataPacket($pkt);
	}
}
