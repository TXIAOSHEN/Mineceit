<?php

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\kits\DefaultKit;
use mineceit\kits\info\MiscKitInfo;
use mineceit\MineceitUtil;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;

class Resistance extends DefaultKit{

	/** @var MiscKitInfo */
	private $miscKitInfo;

	public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10){
		parent::__construct('Resistance', $xkb, $ykb, $speed);
		$this->miscKitInfo = new MiscKitInfo(
			'textures/ui/resistance_effect.png',
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
		$this->items = [MineceitUtil::createItem(Item::STEAK)];
		$this->effects = [new EffectInstance(Effect::getEffect(Effect::RESISTANCE), MineceitUtil::hoursToTicks(1), 10)];
	}
}
