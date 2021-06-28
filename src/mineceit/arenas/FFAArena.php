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
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class FFAArena extends Arena{

	/* @var Vector3 */
	protected $center;

	/* @var DefaultKit */
	protected $kit;

	/* @var Level|null */
	protected $level;

	/** @var string */
	private $name;

	/** @var bool */
	private $interrupt;

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
	 * @param array|Vector3[]   $spawns
	 * @param Level|string      $level
	 * @param DefaultKit|string $kit
	 * @param bool              $interrupt
	 */
	public function __construct(string $name, Vector3 $center, array $spawns, $level, $kit = KitsManager::FIST, bool $interrupt = true){
		$kits = MineceitCore::getKits();
		$this->name = $name;
		$this->kit = ($kit instanceof DefaultKit) ? $kit : $kits->getKit($kit);
		$this->center = $center;
		$this->level = ($level instanceof Level) ? $level : Server::getInstance()->getLevelByName($level);
		$this->interrupt = $interrupt;
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
	 * @return bool
	 *
	 * Determines if an arena can interrupt or not.
	 */
	public function canInterrupt() : bool{
		return $this->interrupt;
	}

	/**
	 * @return Level|null
	 */
	public function getLevel() : ?Level{
		return $this->level;
	}

	/**
	 * @return Vector3
	 */
	public function getCenter() : Vector3{
		return $this->center;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param                $value
	 */
	public function teleportPlayer(MineceitPlayer $player, $value = true) : void{
		$player->getKitHolder()->setKit($this->kit);
		if($this->level !== null){
			$range = count($this->spawns);
			$spawn = rand(1, $range);

			$pos = MineceitUtil::toPosition($this->spawns[$spawn], $this->level);
			MineceitUtil::onChunkGenerated($this->level, $this->spawns[$spawn]->x >> 4, $this->spawns[$spawn]->z >> 4, function() use ($pos, $player){
				$player->teleport($pos);
			});

			$language = $player->getLanguageInfo()->getLanguage();

			$message = $language->arenaMessage(Language::ENTER_ARENA, $this);

			if($value){
				$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
			}
		}
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
		$interrupt = $this->interrupt;
		$posArr = MineceitUtil::posToArray($this->center);
		$spawnsData[1] = MineceitUtil::posToArray($this->center);
		$spawnArr = [];
		foreach($this->spawns as $spawn => $position){
			$spawnArr[$spawn] = MineceitUtil::posToArray($position);
		}
		$level = ($this->level !== null) ? $this->level->getName() : null;
		return [
			'kit' => $kitStr,
			'interrupt' => $interrupt,
			'center' => $posArr,
			'spawns' => $spawnArr,
			'level' => $level,
			'type' => self::TYPE_FFA
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
