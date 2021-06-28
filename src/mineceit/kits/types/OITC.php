<?php

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\kits\DefaultKit;
use mineceit\kits\info\MiscKitInfo;
use mineceit\MineceitUtil;

class OITC extends DefaultKit{

	/** @var MiscKitInfo */
	private $miscKitInfo;

	public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10){
		parent::__construct('OITC', $xkb, $ykb, $speed);
		$this->miscKitInfo = new MiscKitInfo(
			'textures/items/arrow.png',
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
			MineceitUtil::createItem(272),
			MineceitUtil::createItem(261),
			MineceitUtil::createItem(0, 0, 0),
			MineceitUtil::createItem(0, 0, 0),
			MineceitUtil::createItem(0, 0, 0),
			MineceitUtil::createItem(0, 0, 0),
			MineceitUtil::createItem(0, 0, 0),
			MineceitUtil::createItem(0, 0, 0),
			MineceitUtil::createItem(262)
		];
	}
}
