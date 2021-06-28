<?php

declare(strict_types=1);

namespace mineceit\duels;

use mineceit\duels\groups\MineceitDuel;
use mineceit\duels\players\QueuedPlayer;
use mineceit\duels\requests\RequestHandler;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\ScoreboardUtil;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class DuelHandler{

	/* @var QueuedPlayer[]|array */
	private $queuedPlayers;
	/* @var MineceitDuel[]|array */
	private $activeDuels;
	/** @var \SplQueue */
	private $inactiveDuels;
	/* @var Server */
	private $server;

	/** @var RequestHandler */
	private $requestHandler;

	public function __construct(MineceitCore $core){
		$this->requestHandler = new RequestHandler();
		$this->queuedPlayers = [];
		$this->activeDuels = [];
		$this->inactiveDuels = new \SplQueue();
		$this->server = $core->getServer();
	}

	/**
	 * Updates the duels.
	 */
	public function update() : void{
		if(!$this->inactiveDuels->isEmpty()){
			while(!$this->inactiveDuels->isEmpty()){
				// Deallocates inactive duels.
				$duelId = $this->inactiveDuels->pop();
				unset($this->activeDuels[$duelId]);
			}
			ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_FIGHTS);
		}

		$numDuels = count($this->activeDuels);
		$activeDuelKeys = array_keys($this->activeDuels);
		for($i = $numDuels - 1; $i >= 0; $i--){
			$duel = $this->activeDuels[$activeDuelKeys[$i]];
			$duel->update();
		}
	}

	/**
	 * @return RequestHandler
	 */
	public function getRequestHandler() : RequestHandler{
		return $this->requestHandler;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param string         $queue
	 * @param bool           $ranked
	 */
	public function placeInQueue(MineceitPlayer $player, string $queue, bool $ranked = false) : void{

		$local = strtolower($player->getName());
		if(isset($this->queuedPlayers[$local])){
			unset($this->queuedPlayers[$local]);
		}

		$theQueue = new QueuedPlayer($player, $queue, $ranked);
		$this->queuedPlayers[$local] = $theQueue;

		MineceitCore::getItemHandler()->addLeaveQueueItem($player);
		$player->getScoreboardInfo()->addQueueToScoreboard($ranked, $queue);

		$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET .
			$player->getLanguageInfo()->getLanguage()->getDuelMessage(Language::DUEL_ENTER_QUEUE, $queue, $ranked));

		if(($matched = $this->findMatch($theQueue)) !== null && $matched instanceof QueuedPlayer){
			$matchedLocal = strtolower($matched->getPlayer()->getName());
			unset($this->queuedPlayers[$local], $this->queuedPlayers[$matchedLocal]);
			$this->placeInDuel($player, $matched->getPlayer(), $queue, $ranked);
		}

		ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
	}

	/**
	 * @param QueuedPlayer $player
	 *
	 * @return QueuedPlayer|null
	 */
	public function findMatch(QueuedPlayer $player) : ?QueuedPlayer{

		$p = $player->getPlayer();
		$peOnly = $player->isPeOnly();
		$isPe = $p->getClientInfo()->isPE();

		foreach($this->queuedPlayers as $queue){

			$queuedPlayer = $queue->getPlayer();
			$isMatch = false;

			if($p->getDisplayName() === $queue->getPlayer()->getDisplayName() || !$queuedPlayer->canDuel()){
				continue;
			}

			if($queue->isRanked() === $player->isRanked() && $player->getQueue() === $queue->getQueue()){

				$isMatch = true;

				if($peOnly && $isPe){
					$isMatch = $queuedPlayer->isOnline()
						&& $queuedPlayer->getClientInfo()->isPE();
				}
			}elseif(!$queue->isRanked() === !$player->isRanked() && $player->getQueue() === $queue->getQueue()){

				$isMatch = true;

				if($peOnly && $isPe){
					$isMatch = $queuedPlayer->isOnline() && $queuedPlayer
							->getClientInfo()->isPE();
				}
			}

			if($isMatch){
				return $queue;
			}
		}

		return null;
	}

	/**
	 * @param MineceitPlayer $p1
	 * @param MineceitPlayer $p2
	 * @param string         $queue
	 * @param bool           $ranked
	 * @param bool           $foundDuel
	 */
	public function placeInDuel(MineceitPlayer $p1, MineceitPlayer $p2, string $queue, bool $ranked = false, bool $foundDuel = true) : void{

		$worldId = 0;

		$dataPath = $this->server->getDataPath() . '/worlds';

		while(isset($this->activeDuels[$worldId]) || is_dir($dataPath . '/duel' . $worldId)){
			$worldId++;
		}

		$kit = MineceitCore::getKits()->getKit($queue);
		$arena = MineceitCore::getArenas()->findDuelArena($kit->getName());

		$create = MineceitUtil::createLevel($worldId, $arena->getLevel(), "duel");

		if($create){
			$this->activeDuels[$worldId] = new MineceitDuel($worldId, $p1, $p2, $queue, $ranked, $arena);

			if($foundDuel){

				$p1Msg = $p1->getLanguageInfo()->getLanguage()
					->getDuelMessage(Language::DUEL_FOUND_MATCH, $queue, $ranked, $p2->getDisplayName());

				$p2Msg = $p2->getLanguageInfo()->getLanguage()
					->getDuelMessage(Language::DUEL_FOUND_MATCH, $queue, $ranked, $p1->getDisplayName());

				$p1->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $p1Msg);
				$p2->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $p2Msg);
			}

			ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_FIGHTS);
			ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
		}
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return bool
	 */
	public function isInQueue($player) : bool{
		$name = $player instanceof MineceitPlayer ? $player->getName() : $player;
		return isset($this->queuedPlayers[strtolower($name)]);
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $sendMessage
	 */
	public function removeFromQueue(MineceitPlayer $player, bool $sendMessage = true) : void{

		$local = strtolower($player->getName());
		if(!isset($this->queuedPlayers[$local])){
			return;
		}

		/** @var QueuedPlayer $queue */
		$queue = $this->queuedPlayers[$local];
		unset($this->queuedPlayers[$local]);

		MineceitCore::getItemHandler()->removeQueueItem($player);
		$player->getScoreboardInfo()->removeQueueFromScoreboard();

		if($sendMessage){
			$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET .
				$player->getLanguageInfo()->getLanguage()->getDuelMessage(
					Language::DUEL_LEAVE_QUEUE,
					$queue->getQueue(),
					$queue->isRanked()
				));
		}

		ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
	}

	/**
	 * @param bool   $ranked
	 * @param string $queue
	 *
	 * @return int
	 */
	public function getPlayersInQueue(bool $ranked, string $queue) : int{

		$count = 0;
		foreach($this->queuedPlayers as $pQueue){
			if($queue === $pQueue->getQueue() && $pQueue->isRanked() === $ranked){
				$count++;
			}
		}

		return $count;
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return null|QueuedPlayer
	 */
	public function getQueueOf($player) : ?QueuedPlayer{

		$name = $player instanceof MineceitPlayer ? $player->getName() : $player;

		if(isset($this->queuedPlayers[strtolower($name)])){
			return $this->queuedPlayers[strtolower($name)];
		}

		return null;
	}

	/**
	 * @return int
	 */
	public function getEveryoneInQueues() : int{
		return count($this->queuedPlayers);
	}

	/**
	 * @param bool $count
	 *
	 * @return array|MineceitDuel[]|int
	 */
	public function getDuels(bool $count = false){
		return $count ? count($this->activeDuels) : $this->activeDuels;
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return MineceitDuel|null
	 */
	public function getDuel($player) : ?MineceitDuel{

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
	 * @return MineceitDuel|null
	 *
	 * Gets the duel based on the level name.
	 */
	public function getDuelFromLevel($level) : ?MineceitDuel{
		$name = is_numeric($level) ? intval($level) : $level;
		return (is_numeric($name) && isset($this->activeDuels[$name])) ? $this->activeDuels[$name] : null;
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return MineceitDuel|null
	 *
	 * Gets the duel from the spectator.
	 */
	public function getDuelFromSpec($player) : ?MineceitDuel{
		foreach($this->activeDuels as $duel){
			if($duel->isSpectator($player)){
				return $duel;
			}
		}
		return null;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param MineceitDuel   $duel
	 *
	 * Adds a spectator to a duel.
	 */
	public function addSpectatorTo(MineceitPlayer $player, MineceitDuel $duel) : void{

		$local = strtolower($player->getName());

		if(isset($this->queuedPlayers[$local])){
			unset($this->queuedPlayers[$local]);
			ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
		}

		$duel->addSpectator($player);
	}
}
