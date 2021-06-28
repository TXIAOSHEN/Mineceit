<?php

declare(strict_types=1);

namespace mineceit\kits\edited;


use mineceit\game\behavior\kits\IKitHolderEntity;
use mineceit\kits\DefaultKit;
use mineceit\kits\IKit;
use mineceit\kits\info\KnockbackInfo;
use mineceit\kits\info\MiscKitInfo;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use pocketmine\item\Item;

/**
 * Class EditedKit
 * @package mineceit\kits\edited
 *
 * The edited kit class for the new edited kit type.
 */
class EditedKit implements IKit{

	/** @var DefaultKit|null */
	private $parentKit;
	/** @var array|Item[] */
	private $items;

	public function __construct($parentKit, array $items){
		$this->items = $items;
		$this->parentKit = null;
		$this->setParentKit($parentKit);
	}

	/**
	 * @param $parentKit
	 *
	 * Sets the parent kit of the edited kit.
	 */
	public function setParentKit($parentKit) : void{
		if($parentKit instanceof DefaultKit){
			$this->parentKit = $parentKit;
		}elseif(is_string($parentKit)){
			$this->parentKit = MineceitCore::getKits()->getKit($parentKit);
		}
	}

	/**
	 * @param string $kit
	 * @param        $data
	 *
	 * @return EditedKit|null
	 *
	 * Loads the edited kit from the data in to memory.
	 */
	public static function load(string $kit, $data) : ?EditedKit{
		if(isset($data["items"])){
			// Loads the item from the old format.
			$encodedItems = $data["items"];
			$outputItems = [];
			foreach($encodedItems as $slot => $item){
				$exportedItem = MineceitUtil::arrToItem($item);
				if($exportedItem !== null){
					$outputItems[$slot] = $exportedItem;
				}
			}
			return new EditedKit($kit, $outputItems);
		}
		return null;
	}

	/**
	 * @param array|Item[] $items
	 */
	public function setItems(array $items) : void{
		$this->items = $items;
	}

	/**
	 * @param IKitHolderEntity $entity
	 *
	 * @return bool - Gives the kit to the player.
	 *
	 * Gives the kit to the holder entity.
	 */
	public function giveTo(IKitHolderEntity $entity) : bool{
		if(!$this->hasParentKit()){
			return false;
		}

		$entityHolder = $entity->getKitHolderEntity();
		foreach($this->items as $slot => $item){
			$entityHolder->getInventory()->setItem(
				$slot, $item);
		}

		$armor = $this->parentKit->getArmor();
		foreach($armor as $slot => $item){
			$entityHolder->getArmorInventory()->setItem(
				$slot, $item);
		}

		$effects = $this->parentKit->getEffects();
		foreach($effects as $effect){
			$effect->setDuration(MineceitUtil::minutesToTicks(59));
			$effect->setVisible(false);
			$entityHolder->addEffect($effect);
		}
		return true;
	}

	/**
	 * @return bool
	 *
	 * Determines if the player has a parent kit.
	 */
	public function hasParentKit() : bool{
		return $this->parentKit !== null;
	}

	/**
	 * @return KnockbackInfo
	 *
	 * Gets the knockback information of the kit.
	 */
	public function getKnockbackInfo() : KnockbackInfo{
		return $this->parentKit->getKnockbackInfo();
	}

	/**
	 * @return MiscKitInfo
	 *
	 * Gets the misc kit information.
	 */
	public function getMiscKitInfo() : MiscKitInfo{
		return $this->parentKit->getMiscKitInfo();
	}

	/**
	 * @return string
	 *
	 * Gets the name of the kit.
	 */
	public function getName() : string{
		return $this->parentKit->getName();
	}

	/**
	 * @return string
	 *
	 * Gets the localized name of the kit.
	 */
	public function getLocalizedName() : string{
		return $this->parentKit->getLocalizedName();
	}

	/**
	 * @return array
	 *
	 * Exports the kit to an array.
	 */
	public function export() : array{
		$outputItems = [];
		foreach($this->items as $slot => $item){
			$outputItems[$slot] = MineceitUtil::itemToArr($item);
		}
		return [
			"parentName" => $this->parentKit->getName(),
			"items" => $outputItems
		];
	}

	/**
	 * @param $kit
	 *
	 * @return bool
	 *
	 * Determines if one kit is equivalent to another.
	 */
	public function equals($kit) : bool{
		return $this->parentKit->equals($kit);
	}
}