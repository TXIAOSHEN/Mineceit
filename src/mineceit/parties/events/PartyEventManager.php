<?php

declare(strict_types=1);

namespace mineceit\parties\events;

use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\parties\events\types\match\data\QueuedParty;
use mineceit\parties\events\types\PartyDuel;
use mineceit\parties\events\types\PartyGames;
use mineceit\parties\MineceitParty;
use mineceit\player\language\Language;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PartyEventManager{

	/* @var QueuedParty[]|array */
	private $queuedPartys;
	/* @var PartyEvent[]|array */
	private $partyEvents;
	/** @var \SplQueue */
	private $inactivePartyEvents;
	/* @var Server */
	private $server;

	/* @var MineceitCore */
	private $core;

	public function __construct(MineceitCore $core){
		$this->partyEvents = [];
		$this->inactivePartyEvents = new \SplQueue();
		$this->queuedPartys = [];
		$this->core = $core;
		$this->server = $core->getServer();
	}

	/**
	 * @param MineceitParty $party
	 *
	 * @return PartyEvent|null
	 */
	public function getPartyEvent(MineceitParty $party) : ?PartyEvent{

		$event = null;
		foreach($this->partyEvents as $match){
			if($match->isParty($party))
				$event = $match;
		}

		return $event;
	}

	////////////////////////////////////////////////////PARTY DUEL//////////////////////////////////////////////////////

	/**
	 * @param MineceitParty $party
	 * @param string        $queue
	 * @param int           $size
	 */
	public function placeInQueue(MineceitParty $party, string $queue, int $size) : void{

		if($party->getPlayers(true) < 2 || $party->getPlayers(true) < $size){
			$owner = $party->getOwner();
			$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $owner
					->getLanguageInfo()->getLanguage()->generalMessage(Language::PARTIES_PLAYER_NOT_ENOUGH);
			$owner->sendMessage($msg);
			return;
		}elseif($party->getPlayers(true) > $size){
			$owner = $party->getOwner();
			$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $owner
					->getLanguageInfo()->getLanguage()->generalMessage(Language::PARTIES_PLAYER_NOT_MATCH);
			$owner->sendMessage($msg);
			return;
		}

		$local = strtolower($party->getName());
		if(isset($this->queuedPartys[$local])){
			unset($this->queuedPartys[$local]);
		}

		$theQueue = new QueuedParty($party, $queue);
		$this->queuedPartys[$local] = $theQueue;

		MineceitCore::getItemHandler()->addLeaveQueuePartyItem($party);

		$members = $party->getPlayers();
		foreach($members as $member){
			$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member->getLanguageInfo()->getLanguage()->getPartyDuelMessage(
					Language::PARTIES_ENTER_QUEUE,
					$queue,
					$party->getPlayers(true)
				);
			$member->sendMessage($msg);
		}

		if(($matched = $this->findMatch($theQueue)) !== null && $matched instanceof QueuedParty){
			$matchedLocal = strtolower($matched->getParty()->getName());
			unset($this->queuedPartys[$local], $this->queuedPartys[$matchedLocal]);
			$this->placeInDuel($party, $matched->getParty(), $queue);
		}
	}

	/**
	 * @param QueuedParty $party
	 *
	 * @return QueuedParty|null
	 */
	public function findMatch(QueuedParty $party) : ?QueuedParty{

		$p = $party->getParty();

		foreach($this->queuedPartys as $queue){

			$queuedParty = $queue->getParty();

			$isMatch = false;

			if($p->getName() === $queuedParty->getName() || $party->getSize() !== $queue->getSize()){
				continue;
			}

			if($party->getQueue() === $queue->getQueue()){
				$isMatch = true;
			}

			if($isMatch){
				return $queue;
			}
		}

		return null;
	}

	/**
	 * @param MineceitParty $p1
	 * @param MineceitParty $p2
	 * @param string        $queue
	 */
	public function placeInDuel(MineceitParty $p1, MineceitParty $p2, string $queue) : void{

		$worldId = 0;

		$dataPath = $this->server->getDataPath() . '/worlds';

		while(isset($this->partyEvents[$worldId]) || is_dir($dataPath . '/party' . $worldId)){
			$worldId++;
		}

		$kit = MineceitCore::getKits()->getKit($queue);
		$arena = MineceitCore::getArenas()->findDuelArena($kit->getName());

		$create = MineceitUtil::createLevel($worldId, $arena->getLevel(), "party");

		if($create){
			$this->partyEvents[$worldId] = new PartyDuel($worldId, $p1, $p2, $queue, $arena);

			$members1 = $p1->getPlayers();
			$members2 = $p2->getPlayers();

			$size = $p1->getPlayers(true);

			foreach($members1 as $member){
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member->getLanguageInfo()
						->getLanguage()->getPartyDuelMessage(Language::PARTIES_FOUND_MATCH, $queue, $size, $p2->getName());
				$member->sendMessage($msg);
			}

			foreach($members2 as $member){
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member->getLanguageInfo()
						->getLanguage()->getPartyDuelMessage(Language::PARTIES_FOUND_MATCH, $queue, $size, $p1->getName());
				$member->sendMessage($msg);
			}
		}
	}

	/**
	 * @param MineceitParty|string $party
	 *
	 * @return bool
	 */
	public function isInQueue($party) : bool{
		$name = $party instanceof MineceitParty ? $party->getName() : $party;
		return isset($this->queuedPartys[strtolower($name)]);
	}

	/**
	 * @param MineceitParty $party
	 * @param bool          $sendMessage
	 */
	public function removeFromQueue(MineceitParty $party, bool $sendMessage = true) : void{

		$local = strtolower($party->getName());
		if(!isset($this->queuedPartys[$local])){
			return;
		}

		/** @var QueuedParty $queue */
		$queue = $this->queuedPartys[$local];
		unset($this->queuedPartys[$local]);

		MineceitCore::getItemHandler()->removeQueuePartyItem($party);

		if($sendMessage){
			$members = $party->getPlayers();
			foreach($members as $member){
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member->getLanguageInfo()
						->getLanguage()->getPartyDuelMessage(Language::PARTIES_LEAVE_QUEUE, $queue->getQueue(), $queue->getSize());
				$member->sendMessage($msg);
			}
		}
	}

	/**
	 * @param int         $size
	 * @param string|null $queue
	 *
	 * @return int
	 */
	public function getPartysInQueue(int $size, string $queue = null) : int{

		$count = 0;
		foreach($this->queuedPartys as $pQueue){

			if($queue === null && $pQueue->getSize() === $size){
				$count++;
			}elseif($queue === $pQueue->getQueue() && $pQueue->getSize() === $size){
				$count++;
			}
		}

		return $count;
	}

	/**
	 * @param MineceitParty|string $party
	 *
	 * @return null|QueuedParty
	 */
	public function getQueueOf($party) : ?QueuedParty{

		$name = $party instanceof MineceitParty ? $party->getName() : $party;

		if(isset($this->queuedPartys[strtolower($name)])){
			return $this->queuedPartys[strtolower($name)];
		}

		return null;
	}

	/**
	 * @return int
	 */
	public function getEveryoneInQueues() : int{
		return count($this->queuedPartys);
	}

	/**
	 * @param int $key
	 *
	 * Removes a duel with the given key.
	 */
	public function removeDuel(int $key) : void{
		if(isset($this->partyEvents[$key])){
			$this->inactivePartyEvents->push($key);
		}
	}

	////////////////////////////////////////////////////PARTY GAMES//////////////////////////////////////////////////////

	/**
	 * @param MineceitParty $party
	 * @param string        $arena
	 * @param int           $size
	 */
	public function placeInGames(MineceitParty $party, string $arena, int $size = 1) : void{
		$worldId = 0;
		$dataPath = $this->server->getDataPath() . '/worlds';
		while(isset($this->partyEvents[$worldId]) || is_dir($dataPath . '/party' . $worldId)){
			$worldId++;
		}
		$arena = MineceitCore::getArenas()->findGamesArena($arena);
		$create = MineceitUtil::createLevel($worldId, $arena->getLevel(), "party");

		if($create){
			$this->partyEvents[$worldId] = new PartyGames($worldId, $party, $size, $arena);
			//TODO MESSAGE ON START PG
		}
	}
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Updates the party events.
	 */
	public function update() : void{
		while(!$this->inactivePartyEvents->isEmpty()){
			$id = $this->inactivePartyEvents->pop();
			unset($this->partyEvents[$id]);
		}

		$count = count($this->partyEvents);
		$partyEventKeys = array_keys($this->partyEvents);
		for($i = $count - 1; $i >= 0; $i--){
			$event = $this->partyEvents[$partyEventKeys[$i]];
			$event->update();
		}
	}
}
