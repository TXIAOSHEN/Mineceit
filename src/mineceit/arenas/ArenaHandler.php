<?php

declare(strict_types=1);

namespace mineceit\arenas;

use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;
use pocketmine\utils\Config;

class ArenaHandler{

	/* @var array|Arena[] */
	private $arenas;

	/* @var Config */
	private $config;

	/* @var Server */
	private $server;

	public function __construct(MineceitCore $core){
		$this->arenas = [];
		$this->server = $core->getServer();
		$this->initConfig($core->getDataFolder());
	}

	/**
	 * @param string $dataFolder
	 */
	private function initConfig(string $dataFolder) : void{

		$file = $dataFolder . '/arenas.yml';

		$this->config = new Config($file, Config::YAML, []);

		if(!file_exists($file)){
			$this->config->save();
		}else{

			$keys = $this->config->getAll(true);

			foreach($keys as $arenaName){
				$arena = Arena::parseArena($arenaName, $this->config->get((string) $arenaName));
				if($arena !== null){
					$this->arenas[$arenaName] = $arena;
				}
			}
		}
	}

	/**
	 * @param string         $name
	 * @param string|[] $kit
	 * @param MineceitPlayer $player
	 * @param string         $arena
	 *
	 * @return bool
	 */
	public function createArena(string $name, $kit, MineceitPlayer $player, string $arena) : bool{

		$level = $player->getLevel();
		$pos = $player->asVector3();

		if($arena === 'Event'){
			$arena = new EventArena($name, $pos, $level, $kit);
			$events = MineceitCore::getEventManager();
			$events->createEvent($arena);
		}elseif($arena === 'FFA'){
			$spawns[1] = $pos;
			$arena = new FFAArena($name, $pos, $spawns, $level, $kit);
		}elseif($arena === 'Duel'){
			$arena = new DuelArena($name, $pos, $name, $kit);
		}elseif($arena === 'Games'){
			$spawns[1] = $pos;
			$arena = new GamesArena($name, $pos, $spawns, $level, $kit);
		}

		if(!isset($this->arenas[$name]) && !$this->config->exists($name)){
			$this->arenas[$name] = $arena;
			$this->config->set($name, $arena->getData());
			$this->config->save();
			return true;
		}

		return false;
	}


	/**
	 * @param Arena $arena
	 *
	 * @return bool
	 *
	 * Edits the arena.
	 */
	public function editArena(Arena $arena) : bool{

		$name = $arena->getName();

		if(isset($this->arenas[$name])){
			$this->arenas[$name] = $arena;
			$this->config->set($name, $arena->getData());
			$this->config->save();
			return true;
		}

		return false;
	}


	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function deleteArena(string $name) : bool{

		if(isset($this->arenas[$name]) && $this->config->exists($name)){

			$this->config->remove($name);
			/** @var Arena $arena */
			$arena = $this->arenas[$name];

			unset($this->arenas[$name]);
			$this->config->save();

			if($arena instanceof Arena){
				$events = MineceitCore::getEventManager();
				$events->removeEventFromArena($arena->getName());
			}

			return true;
		}

		return false;
	}

	/**
	 * @param string $name
	 *
	 * @return Arena|null
	 */
	public function getArena(string $name) : ?Arena{
		return isset($this->arenas[$name]) ? $this->arenas[$name] : null;
	}

	/**
	 * @param bool $string
	 *
	 * @return array|string[]|FFAArena[]
	 */
	public function getFFAArenas(bool $string = false) : array{
		$result = [];
		foreach($this->arenas as $arena){
			if($arena instanceof FFAArena){
				$result[] = $string ? $arena->getName() : $arena;
			}
		}
		return $result;
	}

	/**
	 * @param bool $string
	 *
	 * @return array|string[]|GamesArena[]
	 */
	public function getGamesArenas(bool $string = false) : array{
		$result = [];
		foreach($this->arenas as $arena){
			if($arena instanceof GamesArena){
				$result[] = $string ? $arena->getName() : $arena;
			}
		}
		return $result;
	}

	/**
	 * @param string $string
	 *
	 * @return GamesArena|null
	 */
	public function findGamesArena(string $string) : ?GamesArena{
		foreach($this->arenas as $arena){
			if($arena instanceof GamesArena && $string === $arena->getName()){
				return $arena;
			}
		}
		return null;
	}

	/**
	 * @param string $string
	 *
	 * @return DuelArena
	 */
	public function findDuelArena(string $string) : DuelArena{
		$result = [];
		foreach($this->arenas as $arena){
			if($arena instanceof DuelArena && in_array($string, $arena->getKit())){
				$result[] = $arena;
			}
		}
		$rnd = count($result);
		return $result[rand(0, $rnd - 1)];
	}

	/**
	 * @param bool $string
	 *
	 * @return array|string[]|EventArena[]
	 */
	public function getEventArenas(bool $string = false) : array{

		$result = [];
		foreach($this->arenas as $arena){
			if($arena instanceof EventArena){
				$result[] = $string ? $arena->getName() : $arena;
			}
		}
		return $result;
	}

	/**
	 * @param FFAArena|string $arena
	 *
	 * @return int
	 */
	public function getPlayersInArena($arena) : int{

		$name = ($arena instanceof FFAArena) ? $arena->getName() : $arena;

		$players = $this->server->getOnlinePlayers();

		$count = 0;

		foreach($players as $player){
			if($player instanceof MineceitPlayer && $player->isInArena()){
				if($player->getArena()->getName() === $name)
					$count++;
			}
		}
		return $count;
	}
}
