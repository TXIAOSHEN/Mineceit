<?php

declare(strict_types=1);

namespace mineceit\game\items;

use pocketmine\item\GoldenApple as PMGoldenApple;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class GoldenApple extends PMGoldenApple{

	public function __construct(){
		parent::__construct(2);
	}

	/**
	 *
	 * @param bool $head
	 * @param int  $count
	 *
	 * @return Item
	 *
	 * Creates a Golden Head.
	 */
	public static function create(bool $head = true, int $count = 1) : Item{
		if($head) return Item::get(Item::MOB_HEAD, 4, $count)->setCustomName(TextFormat::GOLD . "Golden Head");
		return Item::get(Item::GOLDEN_APPLE, 0, $count);
	}
}
