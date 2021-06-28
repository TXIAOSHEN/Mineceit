<?php

declare(strict_types=1);

namespace mineceit\kits;

use mineceit\game\behavior\kits\IKitHolderEntity;
use mineceit\kits\info\KnockbackInfo;
use mineceit\MineceitUtil;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;

abstract class DefaultKit implements IKit{


	/** @var KnockbackInfo */
	protected $knockbackInfo;

	/* @var Item[]|array */
	protected $items;
	/* @var Item[]|array */
	protected $armor;
	/* @var EffectInstance[]|array */
	protected $effects;

	/** @var string */
	private $name;
	/** @var string */
	private $localizedName;

	public function __construct(string $name, $xkb = 0.4, $ykb = 0.4, $speed = 10){
		$this->name = $name;
		$this->localizedName = strtolower($name);
		$this->items = [];
		$this->armor = [];
		$this->effects = [];
		$this->knockbackInfo = new KnockbackInfo($xkb, $ykb, $speed);

		$this->initItems();
	}

	/**
	 * Initializes the items within the abstract kit.
	 */
	abstract protected function initItems() : void;

	/**
	 * @return array|Item[]
	 *
	 * Gets the items of the kit.
	 */
	public function getItems() : array{
		return $this->items;
	}

	/**
	 * @return array|Item[]
	 *
	 * Gets the armor of the kit.
	 */
	public function getArmor() : array{
		return $this->armor;
	}

	/**
	 * @return array|EffectInstance[]
	 *
	 * Gets the effects of the kit.
	 */
	public function getEffects() : array{
		return $this->effects;
	}

	public function getKnockbackInfo() : KnockbackInfo{
		return $this->knockbackInfo;
	}

	public function setKnockbackInfo(KnockbackInfo $info) : void{
		$this->knockbackInfo = $info;
	}

	/**
	 * @return string
	 *
	 * Gets the name of the kit.
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return string
	 *
	 * Gets the localized name of the kit.
	 */
	public function getLocalizedName() : string{
		return $this->localizedName;
	}

	/**
	 * @param IKitHolderEntity $player
	 *
	 * @return bool - True if the player receives a kit.
	 *
	 * Gives the kit to another player.
	 */
	public function giveTo(IKitHolderEntity $player) : bool{
		$entityHolder = $player->getKitHolderEntity();
		foreach($this->armor as $slot => $item){
			$entityHolder->getArmorInventory()->setItem(
				$slot, $item);
		}

		foreach($this->items as $slot => $item){
			$entityHolder->getInventory()->setItem(
				$slot, $item);
		}

		foreach($this->effects as $effect){
			$effect->setDuration(MineceitUtil::minutesToTicks(59));
			$effect->setVisible(false);
			$entityHolder->addEffect($effect);
		}

		// TODO: Move Somewhere
		/*
			$language = $player->getLanguageInfo()->getLanguage();
			$message = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->kitMessage($this, Language::KIT_RECEIVE);
			if ($msg) {
				$player->sendMessage($message);
			}
		*/
		return true;
	}

	/**
	 * @return array
	 *
	 * Exports the kit to a format that can be saved.
	 */
	public function export() : array{
		return $this->knockbackInfo->export();
	}

	/**
	 * @param $kit
	 *
	 * @return bool
	 *
	 * Determines if a kit is equivalent to another.
	 */
	public function equals($kit) : bool{
		if($kit instanceof IKit){
			return $this->localizedName == $kit->getLocalizedName();
		}
		return false;
	}
}
