<?php

declare(strict_types=1);

namespace mineceit\game\behavior\kits;


use pocketmine\entity\Entity;
use pocketmine\entity\Human;

interface IKitHolderEntity{

	/**
	 * @return KitHolder|null
	 *
	 * Gets the kid holder of the entity.
	 */
	public function getKitHolder() : ?KitHolder;

	/**
	 * @return Entity|null
	 *
	 * Gets the kit holder entity.
	 */
	public function getKitHolderEntity() : ?Human;
}