<?php

declare(strict_types=1);

namespace mineceit\kits\types;

use mineceit\kits\DefaultKit;
use mineceit\kits\info\MiscKitInfo;
use pocketmine\item\Item;

class Fist extends DefaultKit{

	/** @var MiscKitInfo */
	private $miscKitInfo;

	public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10){
		parent::__construct('Fist', $xkb, $ykb, $speed);
		$this->miscKitInfo = new MiscKitInfo(
			'textures/items/beef_cooked.png',
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
		$this->items = [Item::get(Item::STEAK)];
	}
}
