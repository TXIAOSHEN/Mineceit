<?php

declare(strict_types=1);

namespace mineceit\game\items;

use pocketmine\item\Item;
use pocketmine\item\ProjectileItem;
use pocketmine\nbt\tag\CompoundTag;

class BotPotion extends ProjectileItem{

	public function __construct(){
		parent::__construct(Item::SPLASH_POTION, 22, "Bot Potion");
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function getProjectileEntityType() : string{
		return "BotPotion";
	}

	public function getThrowForce() : float{
		return 0.5;
	}

	protected function addExtraTags(CompoundTag $tag) : void{
		$tag->setShort("PotionId", 22);
	}
}
