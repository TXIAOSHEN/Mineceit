<?php

declare(strict_types=1);

namespace mineceit\guild;

class Agent{
	/* @var string */
	private $name;

	/* @var string */
	private $type;

	/* @var string */
	private $skin;

	/* @var array[]|string */
	private $validSkin;

	/* @var array[]|int */
	private $level;

	public function __construct(string $name){
		$this->name = $name;
		$this->skin = 'default';
		$this->validSkin = [];
		$this->level = [0, 0, 0, 0];

		$validTankAgent = ['Golem', 'Guardian', 'Grakk'];
		$validDamageAgent = ['Swordman', 'Assasin', 'Sniper'];
		$validSupportAgent = ['Healer', 'Alchemist'];

		if(in_array($this->name, $validTankAgent)) $this->type = 'tank';
		else if(in_array($this->name, $validDamageAgent)) $this->type = 'damage';
		else if(in_array($this->name, $validSupportAgent)) $this->type = 'support';
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @param bool $flag
	 *
	 * @return int
	 */
	public function getLevel(bool $flag = false){
		return ($flag) ? (string) $this->level[0] . ' ' . (string) $this->level[1]
			. ' ' . (string) $this->level[2] . ' ' . (string) $this->level[3] : $this->level;
	}
}
