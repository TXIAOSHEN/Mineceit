<?php

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\kits\DefaultKit;
use mineceit\kits\info\MiscKitInfo;
use mineceit\MineceitUtil;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

class Gapple extends DefaultKit{

	/** @var MiscKitInfo */
	private $miscKitInfo;

	public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10){
		parent::__construct('Gapple', $xkb, $ykb, $speed);
		$this->miscKitInfo = new MiscKitInfo(
			'textures/items/apple_golden.png',
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
		$this->items = [
			MineceitUtil::createItem(
				276,
				0,
				1,
				[
					new EnchantmentInstance(Enchantment::getEnchantment(9), 2),
					new EnchantmentInstance(Enchantment::getEnchantment(17), 3)
				]
			),
			Item::get(Item::GOLDEN_APPLE, 0, 64)
		];

		$e1 = new EnchantmentInstance(Enchantment::getEnchantment(0), 2);
		$e2 = new EnchantmentInstance(Enchantment::getEnchantment(17), 3);

		$helmet = MineceitUtil::createItem(310, 0, 1, [$e1, $e2]);
		$chest = MineceitUtil::createItem(311, 0, 1, [$e1, $e2]);
		$legs = MineceitUtil::createItem(312, 0, 1, [$e1, $e2]);
		$boots = MineceitUtil::createItem(
			313,
			0,
			1,
			[$e1, $e2, new EnchantmentInstance(Enchantment::getEnchantment(2), 4)]
		);
		$this->armor = [$helmet, $chest, $legs, $boots];
	}
}
