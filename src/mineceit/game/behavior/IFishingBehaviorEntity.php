<?php

declare(strict_types=1);

namespace mineceit\game\behavior;


use pocketmine\entity\Entity;

interface IFishingBehaviorEntity{

	/**
	 * @return Entity|null
	 *
	 * Gets the fishing entity.
	 */
	public function getFishingEntity() : ?Entity;

	/**
	 * @return FishingBehavior|null
	 *
	 * Gets the fishing behavior.
	 */
	public function getFishingBehavior() : ?FishingBehavior;
}
