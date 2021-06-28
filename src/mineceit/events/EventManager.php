<?php

declare(strict_types=1);

namespace mineceit\events;

use mineceit\arenas\EventArena;
use mineceit\kits\KitsManager;
use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;

class EventManager{

	const EVENT_TYPES = [
		KitsManager::SUMO => MineceitEvent::TYPE_SUMO,
		KitsManager::GAPPLE => MineceitEvent::TYPE_GAPPLE,
		KitsManager::NODEBUFF => MineceitEvent::TYPE_NODEBUFF,
		KitsManager::SOUP => MineceitEvent::TYPE_BOSS
	];

	/** @var MineceitEvent[]|array */
	private $events;

	/** @var MineceitCore */
	private $core;

	/** @var Server */
	private $server;

	public function __construct(MineceitCore $core){
		$this->core = $core;
		$this->server = $core->getServer();
		$this->events = [];
		$this->initEvents();
	}


	/**
	 * Initializes the events to the event handler.
	 */
	private function initEvents() : void{
		$arenas = MineceitCore::getArenas()->getEventArenas();
		foreach($arenas as $arena){
			$this->createEvent($arena);
		}
	}

	/**
	 * @param EventArena $arena
	 *
	 * Creates a new event from an arena.
	 */
	public function createEvent(EventArena $arena) : void{
		$type = $arena->getKit()->getLocalizedName();
		if(isset(self::EVENT_TYPES[$type])){
			$type = self::EVENT_TYPES[$type];
			$this->events[$arena->getName()] = new MineceitEvent($type, $arena);
		}
	}

	/**
	 * Updates the events.
	 */
	public function update() : void{
		foreach($this->events as $event){
			$event->update();
		}
	}

	/**
	 * @param string $name
	 *
	 * Removes an event based on the arena name.
	 */
	public function removeEventFromArena(string $name) : void{
		if(isset($this->events[$name])){
			unset($this->events[$name]);
		}
	}

	/**
	 * @return array|MineceitEvent[]
	 *
	 * Gets the events.
	 */
	public function getEvents() : array{
		return $this->events;
	}

	/**
	 * @param int $index
	 *
	 * @return MineceitEvent|null
	 *
	 * Gets the event based on the name.
	 */
	public function getEventFromIndex(int $index) : ?MineceitEvent{
		$keys = array_keys($this->events);
		if(isset($keys[$index])){
			return $this->events[$keys[$index]];
		}
		return null;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return MineceitEvent|null
	 */
	public function getEventFromPlayer(MineceitPlayer $player) : ?MineceitEvent{
		foreach($this->events as $event){
			if($event->isPlayer($player)){
				return $event;
			}
		}
		return null;
	}
}
