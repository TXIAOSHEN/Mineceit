<?php

declare(strict_types=1);

namespace mineceit\game\inventories\menus\data;

use pocketmine\math\Vector3;

class MineceitHolderData{
	private $position;

	private $customName;

	public function __construct(Vector3 $position, string $name){
		$this->position = $position;
		$this->customName = $name;
	}

	public function getPos() : Vector3{
		return $this->position;
	}

	public function getCustomName() : string{
		return $this->customName;
	}
}
