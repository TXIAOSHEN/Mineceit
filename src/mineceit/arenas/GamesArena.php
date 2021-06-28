<?php

declare(strict_types=1);

namespace mineceit\arenas;

use mineceit\kits\DefaultKit;
use mineceit\kits\KitsManager;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

class GamesArena extends Arena{

	/* @var Vector3 */
	protected $center;

	/* @var DefaultKit */
	protected $kit;

	/* @var Level|null */
	protected $level;

	/** @var string */
	private $name;

	/** @var bool */
	private $open;

	/** @var Vector3 */
	private $spawns;

	/** @var int */
	private $size = 5;

	/**
	 * FFAArena constructor.
	 *
	 * @param string            $name
	 * @param Vector3           $center
	 * @param array             $spawns
	 * @param Level|string      $level
	 * @param DefaultKit|string $kit
	 */
	public function __construct(string $name, Vector3 $center, array $spawns, $level, $kit = KitsManager::FIST){
		$kits = MineceitCore::getKits();
		$this->name = $name;
		$this->kit = ($kit instanceof DefaultKit) ? $kit : $kits->getKit($kit);
		$this->center = $center;
		$this->level = $level;
		$this->open = true;
		$this->spawns = $spawns;
	}

	/**
	 * @return bool
	 *
	 * Determines if an arena is open or not.
	 */
	public function isOpen() : bool{
		return $this->open;
	}

	/**
	 * @return string
	 */
	public function getLevel() : string{
		return $this->level;
	}

	/**
	 * @return Vector3
	 */
	public function getCenter() : Vector3{
		return $this->center;
	}

	/**
	 * @param array $excludedSpawns
	 *
	 * @return int
	 */
	public function randomSpawnExclude($excludedSpawns = []) : int{
		$array = $this->spawns;

		foreach($excludedSpawns as $es){
			if(isset($array[$es]))
				unset($array[$es]);
		}

		if(count($array) === 0) $array = $this->spawns;

		$size = count($array) - 1;

		$keys = array_keys($array);

		return (int) $keys[mt_rand(0, $size)];
	}

	public function teleportPlayer(MineceitPlayer $player, $value = false) : void{
	}

	/**
	 * @param MineceitPlayer $player
	 * @param int            $key
	 * @param Level          $level
	 */
	public function teleportPlayerByKey(MineceitPlayer $player, int $key, Level $level) : void{
		$player->getKitHolder()->setKit($this->kit);
		if($level !== null){
			$pos = MineceitUtil::toPosition($this->spawns[$key], $level);
			MineceitUtil::onChunkGenerated($level, $this->spawns[$key]->x >> 4, $this->spawns[$key]->z >> 4, function() use ($pos, $player){
				$player->teleport($pos);
			});
			$language = $player->getLanguageInfo()->getLanguage();
			$message = $language->arenaMessage(Language::ENTER_ARENA, $this->getName());
			$player->sendMessage($message);
		}
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return bool
	 *
	 * Determines whether the player is in the protection.
	 */
	public function isWithinProtection(MineceitPlayer $player) : bool{
		$maxX = $this->center->x + $this->size;
		$minX = $this->center->x - $this->size;
		$maxY = 255;
		$minY = $this->center->y - 0;
		if($minY <= 0){
			$minY = 0;
		}
		$maxZ = $this->center->z + $this->size;
		$minZ = $this->center->z - $this->size;

		$position = $player->asVector3();

		$withinX = MineceitUtil::isWithinBounds($position->x, $maxX, $minX);
		$withinY = MineceitUtil::isWithinBounds($position->y, $maxY, $minY);
		$withinZ = MineceitUtil::isWithinBounds($position->z, $maxZ, $minZ);

		return $withinX && $withinY && $withinZ;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * Sets the spawn of the arena.
	 */
	public function setSpawn(MineceitPlayer $player) : void{
		$count = count($this->spawns);
		$this->spawns[$count + 1] = $player->asVector3();
	}

	/**
	 * @return array
	 */
	public function getData() : array{

		$kit = $this->getKit();
		$kitStr = ($kit !== null) ? $kit->getName() : null;
		$posArr = MineceitUtil::posToArray($this->center);
		$spawnsData[1] = MineceitUtil::posToArray($this->center);
		$spawnArr = [];
		foreach($this->spawns as $spawn => $position){
			$spawnArr[$spawn] = MineceitUtil::posToArray($position);
		}
		$level = ($this->level !== null) ? $this->level->getName() : null;
		return [
			'kit' => $kitStr,
			'center' => $posArr,
			'spawns' => $spawnArr,
			'level' => $level,
			'type' => self::TYPE_GAMES
		];
	}

	/**
	 * @return DefaultKit|null
	 */
	public function getKit() : ?DefaultKit{
		return $this->kit;
	}

	/**
	 * @return string
	 */
	public function getTexture() : string{
		return $this->kit !== null ? $this->kit
			->getMiscKitInfo()->getTexture() : '';
	}
}
