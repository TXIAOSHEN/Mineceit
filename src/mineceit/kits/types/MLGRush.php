<?php

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\kits\DefaultKit;
use mineceit\kits\info\MiscKitInfo;
use mineceit\MineceitUtil;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;

class MLGRush extends DefaultKit{
	/** @var MiscKitInfo */
	private $miscKitsInfo;

	public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10){
		parent::__construct('MLGRush', $xkb, $ykb, $speed);
		$this->miscKitsInfo = new MiscKitInfo(
			'textures/blocks/sandstone_normal.png',
			true,
			true,
			false,
			true
		);
	}

	/**
	 * @return MiscKitInfo
	 *
	 * Gets the misc kit information.
	 */
	public function getMiscKitInfo() : MiscKitInfo{
		return $this->miscKitsInfo;
	}

	/**
	 * Initializes the items within the abstract kit.
	 */
	protected function initItems() : void{
		$e1 = new EnchantmentInstance(Enchantment::getEnchantment(15), 1);
		$e2 = new EnchantmentInstance(Enchantment::getEnchantment(17), 3);
		$e3 = new EnchantmentInstance(Enchantment::getEnchantment(12), 2);

		$this->items = [
			MineceitUtil::createItem(280, 0, 1, [$e3]),
			MineceitUtil::createItem(257, 0, 1, [$e1, $e2]),
			MineceitUtil::createItem(24, 0, 64),
			MineceitUtil::createItem(24, 0, 64)
		];
		$this->effects = [new EffectInstance(Effect::getEffect(Effect::RESISTANCE), MineceitUtil::hoursToTicks(1), 10)];
	}
}
