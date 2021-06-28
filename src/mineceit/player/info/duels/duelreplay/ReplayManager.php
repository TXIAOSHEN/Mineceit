<?php

declare(strict_types=1);

namespace mineceit\player\info\duels\duelreplay;

use Grpc\Server;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\info\duels\duelreplay\info\DuelReplayInfo;
use mineceit\player\MineceitPlayer;
use pocketmine\level\Level;

class ReplayManager{

	/* @var array|MineceitReplay[] */
	private $activeReplays;
	/** @var \SplQueue */
	private $inactiveReplays;

	/* @var MineceitCore */
	private $core;

	/* @var Server */
	private $server;

	public function __construct(MineceitCore $core){
		$this->activeReplays = [];
		$this->inactiveReplays = new \SplQueue();
		$this->core = $core;
		$this->server = $core->getServer();
	}

	/**
	 * Updates the replay manager.
	 */
	public function update() : void{
		while(!$this->inactiveReplays->isEmpty()){
			// Deallocates inactive duels.
			$id = $this->inactiveReplays->pop();
			unset($this->activeReplays[$id]);
		}

		$numDuels = count($this->activeReplays);
		$activeDuelKeys = array_keys($this->activeReplays);
		for($i = $numDuels - 1; $i >= 0; $i--){
			$duel = $this->activeReplays[$activeDuelKeys[$i]];
			$duel->update();
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param DuelReplayInfo $info
	 */
	public function startReplay(MineceitPlayer $player, DuelReplayInfo $info) : void{
		$worldId = 0;
		$dataPath = $this->server->getDataPath() . 'worlds/';
		while(isset($this->activeReplays[$worldId]) || is_dir($dataPath . '/replay' . $worldId)){
			$worldId++;
		}
		$arena = $info->getWorldData()->getArena();
		$create = MineceitUtil::createLevel($worldId, $arena->getLevel(), "replay");
		if($create){
			$duelHandler = MineceitCore::getDuelHandler();
			if($duelHandler->isInQueue($player)){
				$duelHandler->removeFromQueue($player, false);
			}
			$this->activeReplays[$worldId] = new MineceitReplay($worldId, $player, $info);
		}
	}

	/**
	 * @return array|MineceitReplay[]
	 */
	public function getReplays() : array{
		return $this->activeReplays;
	}

	public function deleteReplay(int $worldId) : void{
		if(isset($this->activeReplays[$worldId])){
			$this->inactiveReplays->push($worldId);
		}
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return MineceitReplay|null
	 *
	 * Gets the replay from the spectator.
	 */
	public function getReplayFrom($player) : ?MineceitReplay{

		$name = $player instanceof MineceitPlayer ? $player->getName() : strval($player);

		foreach($this->activeReplays as $key => $replay){
			if($replay->getSpectator()->getName() === $name){
				return $replay;
			}
		}

		return null;
	}

	/**
	 * @param Level|string $level
	 *
	 * @return MineceitReplay|null
	 */
	public function getReplayFromLevel($level) : ?MineceitReplay{
		$name = $level instanceof Level ? $level->getName() : $level;
		return $this->isReplayLevel($level) ? $this->activeReplays[$name] : null;
	}

	/**
	 * @param Level|string $level
	 *
	 * @return bool
	 *
	 * Determines if the level is a replay level.
	 */
	public function isReplayLevel($level) : bool{
		$name = $level instanceof Level ? $level->getName() : $level;
		return isset($this->activeReplays[$name]);
	}
}
