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

class Knock extends DefaultKit{

	/** @var MiscKitInfo */
	private $miscKitInfo;

	public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10){
		parent::__construct('Knock', $xkb, $ykb, $speed);
		$this->miscKitInfo = new MiscKitInfo(
			'textures/items/bow_pulling_2.png',
			true,
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
		$this->items = [
			MineceitUtil::createItem(280, 0, 1, [new EnchantmentInstance(Enchantment::getEnchantment(12), 2)]), MineceitUtil::createItem(261, 0, 1, [new EnchantmentInstance(Enchantment::getEnchantment(20), 1), new EnchantmentInstance(Enchantment::getEnchantment(17), 10), new EnchantmentInstance(Enchantment::getEnchantment(22), 1)]), MineceitUtil::createItem(262)
		];
		$this->effects = [
			new EffectInstance(Effect::getEffect(Effect::RESISTANCE), MineceitUtil::hoursToTicks(1), 10)
		];
	}
}
