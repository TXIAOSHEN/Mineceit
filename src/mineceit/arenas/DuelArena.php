<?php

declare(strict_types=1);

namespace mineceit\arenas;

use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

class DuelArena extends Arena{

	const P1 = 'p1';
	const P2 = 'p2';

	/** @var Vector3 */
	protected $center, $p1Spawn, $p2Spawn;

	/** @var String */
	protected $level;

	/** @var string */
	private $name;

	/** @var array|[]DefaultKit */
	private $kit;

	/**
	 * DuelArena constructor.
	 *
	 * @param string         $name
	 * @param Vector3        $center
	 * @param Level|string   $level
	 * @param array|string[] $kits
	 * @param Vector3|null   $p1Spawn
	 * @param Vector3|null   $p2Spawn
	 */
	public function __construct(string $name, Vector3 $center, $level, $kits, $p1Spawn = null, $p2Spawn = null){
		$this->name = $name;
		$this->kit = $kits;
		$this->center = $center;
		$this->p1Spawn = $p1Spawn ?? $center;
		$this->p2Spawn = $p2Spawn ?? $center;
		$this->level = $level;
	}

	/**
	 * @return string
	 */
	public function getLevel() : string{
		return $this->level;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	public function teleportPlayer(MineceitPlayer $player, $value = false) : void{
	}

	/**
	 * @return array
	 */
	public function getData() : array{
		$kit = $this->getKit();

		$center = MineceitUtil::posToArray($this->center);
		$p1Spawn = MineceitUtil::posToArray($this->p1Spawn);
		$p2Spawn = MineceitUtil::posToArray($this->p2Spawn);

		$level = $this->level;

		return [
			'level' => $level,
			'center' => $center,
			'kit' => $kit,
			'p1' => $p1Spawn,
			'p2' => $p2Spawn,
			'type' => self::TYPE_DUEL
		];
	}

	/**
	 * @return array
	 */
	public function getKit() : array{
		return $this->kit;
	}

	/**
	 * @param array $kits
	 */
	public function setKit(array $kits) : void{
		$this->kit = $kits;
	}


	/**
	 * @param Vector3 $pos
	 */
	public function setP1SpawnPos(Vector3 $pos) : void{
		$this->p1Spawn = $pos;
	}


	/**
	 * @param Vector3 $pos
	 */
	public function setP2SpawnPos(Vector3 $pos) : void{
		$this->p2Spawn = $pos;
	}

	/**
	 * @return Vector3
	 */
	public function getP1SpawnPos() : Vector3{
		return $this->p1Spawn;
	}


	/**
	 * @return Vector3
	 */
	public function getP2SpawnPos() : Vector3{
		return $this->p2Spawn;
	}
}
