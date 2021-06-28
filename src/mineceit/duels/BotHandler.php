<?php

declare(strict_types=1);

namespace mineceit\duels;

use mineceit\duels\groups\BotDuel;
use mineceit\game\entities\bots\AbstractBot;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\Server;

class BotHandler{

	/* @var BotDuel[]|array */
	private $activeDuels;
	/** @var \SplQueue */
	private $inactiveDuels;

	/* @var Server */
	private $server;

	public function __construct(MineceitCore $core){
		$this->server = $core->getServer();
		$this->activeDuels = [];
		$this->inactiveDuels = new \SplQueue();
	}

	/**
	 * Updates the duels.
	 */
	public function update() : void{
		while(!$this->inactiveDuels->isEmpty()){
			// Deallocates inactive duels.
			$duelId = $this->inactiveDuels->pop();
			unset($this->activeDuels[$duelId]);
		}

		$numDuels = count($this->activeDuels);
		$activeDuelKeys = array_keys($this->activeDuels);
		for($i = $numDuels - 1; $i >= 0; $i--){
			$duel = $this->activeDuels[$activeDuelKeys[$i]];
			$duel->update();
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param string         $bot
	 */
	public function placeInDuel(MineceitPlayer $player, string $bot) : void{
		$worldId = 0;
		$dataPath = $this->server->getDataPath() . '/worlds';

		while(isset($this->activeDuels[$worldId]) || is_dir($dataPath . '/bot' . $worldId)){
			$worldId++;
		}

		if($bot === "ClutchBot"){
			$arena = MineceitCore::getArenas()->getArena("Clutch");
		}else{
			$kit = MineceitCore::getKits()->getKit("NoDebuff");
			$arena = MineceitCore::getArenas()->findDuelArena($kit->getName());
		}

		$create = MineceitUtil::createLevel($worldId, $arena->getLevel(), "bot");

		if($create){
			$skin = $player->getSkin();
			$spawnPos = $arena->getP2SpawnPos();
			$x = $spawnPos->getX();
			$y = $spawnPos->getY();
			$z = $spawnPos->getZ();

			$level = $this->server->getLevelByName("bot$worldId");
			$pos = new Position($x, $y, $z, $level);
			$nbt = AbstractBot::getHumanNBT($bot, $skin, $pos);
			$bot = Entity::createEntity($bot, $level, $nbt);
			$bot->setSkin($skin);

			$this->activeDuels[$worldId] = new BotDuel($worldId, $player, $bot, $arena);
		}
	}

	/**
	 * @param bool $count
	 *
	 * @return array|BotDuel[]|int
	 */
	public function getDuels(bool $count = false){
		return $count ? count($this->activeDuels) : $this->activeDuels;
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return BotDuel|null
	 */
	public function getDuel($player) : ?BotDuel{

		foreach($this->activeDuels as $duel){
			if($duel->isPlayer($player)){
				return $duel;
			}
		}

		return null;
	}

	/**
	 * @param int $key
	 *
	 * Removes a duel with the given key.
	 */
	public function removeDuel(int $key) : void{
		if(isset($this->activeDuels[$key])){
			$this->inactiveDuels->push($key);
		}
	}

	/**
	 * @param string|int $level
	 *
	 * @return bool
	 *
	 * Determines if the level is a duel level.
	 */
	public function isDuelLevel($level) : bool{
		$name = is_int($level) ? intval($level) : $level;
		return is_numeric($name) && isset($this->activeDuels[intval($name)]);
	}

	/**
	 * @param string|int $level
	 *
	 * @return BotDuel|null
	 *
	 * Gets the duel based on the level name.
	 */
	public function getDuelFromLevel($level) : ?BotDuel{
		$name = is_numeric($level) ? intval($level) : $level;
		return (is_numeric($name) && isset($this->activeDuels[$name])) ? $this->activeDuels[$name] : null;
	}
}
