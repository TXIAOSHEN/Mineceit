<?php

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\game\items\GoldenApple;
use mineceit\kits\DefaultKit;
use mineceit\kits\info\MiscKitInfo;
use mineceit\MineceitUtil;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;

class Build extends DefaultKit{

	/** @var MiscKitInfo */
	private $miscKitInfo;

	public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10){
		parent::__construct('Build', $xkb, $ykb, $speed);
		$this->miscKitInfo = new MiscKitInfo(
			'textures/items/iron_pickaxe.png',
			false,
			true,
			true,
			false
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
		$e1 = new EnchantmentInstance(Enchantment::getEnchantment(0), 2);
		$e2 = new EnchantmentInstance(Enchantment::getEnchantment(17), 3);
		$e3 = new EnchantmentInstance(Enchantment::getEnchantment(15), 1);
		$e4 = new EnchantmentInstance(Enchantment::getEnchantment(9), 1);

		$this->items = [
			MineceitUtil::createItem(283, 0, 1, [$e2, $e4]),
			GoldenApple::create(false, 5),
			MineceitUtil::createItem(257, 0, 1, [$e3, $e2]),
			MineceitUtil::createItem(24, 0, 64),
			MineceitUtil::createItem(24, 0, 64),
			MineceitUtil::createItem(0, 0, 0),
			MineceitUtil::createItem(0, 0, 0),
			MineceitUtil::createItem(0, 0, 0),
			MineceitUtil::createItem(30, 0, 1)
		];

		$helmet = MineceitUtil::createItem(306, 0, 1, [$e1, $e2]);
		$chest = MineceitUtil::createItem(315, 0, 1, [$e1, $e2]);
		$legs = MineceitUtil::createItem(316, 0, 1, [$e1, $e2]);
		$boots = MineceitUtil::createItem(309, 0, 1, [$e1, $e2]);
		$this->armor = [$helmet, $chest, $legs, $boots];
	}
}
