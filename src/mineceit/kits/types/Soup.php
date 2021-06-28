<?php

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\kits\DefaultKit;
use mineceit\kits\info\MiscKitInfo;
use mineceit\MineceitUtil;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;

class Soup extends DefaultKit{

	/** @var MiscKitInfo */
	private $miscKitInfo;

	public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10){
		parent::__construct('Soup', $xkb, $ykb, $speed);
		$this->miscKitInfo = new MiscKitInfo(
			'textures/items/mushroom_stew.png',
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
			MineceitUtil::createItem(267, 0, 1, [new EnchantmentInstance(Enchantment::getEnchantment(17), 10)])
		];
		for($i = 0; $i < 35; $i++){
			$this->items[] = MineceitUtil::createItem(282);
		}
		$e1 = new EnchantmentInstance(Enchantment::getEnchantment(17), 10);
		$helmet = MineceitUtil::createItem(0, 0, 0);
		$chest = MineceitUtil::createItem(307, 0, 1, [$e1, new EnchantmentInstance(Enchantment::getEnchantment(0), 1)]);
		$legs = MineceitUtil::createItem(304, 0, 1, [$e1]);
		$boots = MineceitUtil::createItem(305, 0, 1, [$e1]);
		$this->armor = [$helmet, $chest, $legs, $boots];
	}
}
