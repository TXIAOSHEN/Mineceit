<?php

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\kits\DefaultKit;
use mineceit\kits\info\MiscKitInfo;
use mineceit\MineceitUtil;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;

class NoDebuff extends DefaultKit{

	/** @var MiscKitInfo */
	private $miscKitInfo;

	public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10){
		parent::__construct('NoDebuff', $xkb, $ykb, $speed);

		$this->miscKitInfo = new MiscKitInfo(
			'textures/items/potion_bottle_splash_heal.png',
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
					new EnchantmentInstance(Enchantment::getEnchantment(17), 10)
				]
			),
			MineceitUtil::createItem(368, 0, 16)
		];

		for($i = 0; $i < 6; $i++){
			$id = ($i !== 5) ? 438 : 373;
			$meta = ($i !== 5) ? 22 : 15;
			$this->items[] = MineceitUtil::createItem($id, $meta);
		}

		for($i = 0; $i < 28; $i++){
			$this->items[] = MineceitUtil::createItem(438, 22);
		}
		$e1 = new EnchantmentInstance(Enchantment::getEnchantment(0), 2);
		$e2 = new EnchantmentInstance(Enchantment::getEnchantment(17), 10);

		$helmet = MineceitUtil::createItem(310, 0, 1, [$e1, $e2]);
		$chest = MineceitUtil::createItem(311, 0, 1, [$e1, $e2]);
		$legs = MineceitUtil::createItem(312, 0, 1, [$e1, $e2]);
		$boots = MineceitUtil::createItem(313, 0, 1, [$e1, $e2]);
		$this->armor = [$helmet, $chest, $legs, $boots];
	}
}
