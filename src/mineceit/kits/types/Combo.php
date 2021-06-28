<?php

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\kits\DefaultKit;
use mineceit\kits\info\MiscKitInfo;
use mineceit\MineceitUtil;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

class Combo extends DefaultKit{

	/** @var MiscKitInfo */
	private $miscKitInfo;

	public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10){
		parent::__construct('Combo', $xkb, $ykb, $speed);
		$this->miscKitInfo = new MiscKitInfo(
			'textures/items/fish_pufferfish_raw.png',
			true,
			true,
			true,
			true
		);
	}

	/**
	 * @return MiscKitInfo
	 *
	 * Gets the misc kit information.
	 */
	public function getMiscKitInfo() : MiscKitInfo{
		return $this->miscKitInfo;
	}

	/**
	 * Initializes the items within the abstract kit.
	 */
	protected function initItems() : void{
		$sword = MineceitUtil::createItem(
			276,
			0,
			1,
			[new EnchantmentInstance(Enchantment::getEnchantment(9), 5), new EnchantmentInstance(Enchantment::getEnchantment(17), 3)]
		);
		$e = new EnchantmentInstance(Enchantment::getEnchantment(0), 3);
		$helmet = MineceitUtil::createItem(310, 0, 1, [$e]);
		$chest = MineceitUtil::createItem(311, 0, 1, [$e]);
		$legs = MineceitUtil::createItem(
			312,
			0,
			1,
			[$e, new EnchantmentInstance(Enchantment::getEnchantment(2), 4)]
		);
		$boots = MineceitUtil::createItem(313, 0, 1, [$e]);

		$this->items = [
			$sword,
			Item::get(Item::APPLEENCHANTED, 0, 64),
			Item::get(Item::POTION, 15),
			27 => $helmet,
			28 => $chest,
			29 => $legs,
			30 => $boots
		];
		$this->armor = [$helmet, $chest, $legs, $boots];
	}
}
