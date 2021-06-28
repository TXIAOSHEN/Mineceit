<?php

declare(strict_types=1);

namespace mineceit\parties\events\types;

use mineceit\arenas\EventArena;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\parties\MineceitParty;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PartyTournament{

	const TYPE_SUMO = 0;
	const TYPE_GAPPLE = 1;
	const TYPE_FIST = 2;
	const TYPE_NODEBUFF = 3;

	const STATUS_PREPAIRING = 0;
	const STATUS_AWAITING_PARTIES = 1;
	const STATUS_IN_PROGESS = 2;
	const STATUS_ENDING = 3;

	const MINUTES_AWAITING_PARTIES = 5;

	const MAX_PARTIES = 15;
	const MIN_PARTIES = 2;

	const MAX_DELAY_SECONDS = 3;

	/** @var int */
	protected $type;

	/** @var EventArena */
	protected $arena;

	/** @var int */
	protected $status;

	/** @var array|MineceitParty[] */
	protected $parties;

	/** @var int */
	protected $currentTick;

	/** @var int */
	protected $currentEventTick;

	/** @var int */
	protected $awaitingPartiesTick;

	/** @var int */
	protected $currentDelay;

	/** @var int */
	protected $endingDelay;

	/** @var array */
	protected $eliminated;

	/** @var MineceitPartyTournamentDuel|null */
	protected $current1vs1;

	/** @var string|null */
	protected $winner;

	/** @var bool */
	protected $prize;

	/** @var int */
	protected $startingDelay;

	/** @var int */
	private $maxNumberOfParties;

	/** @var string|null */
	private $lastWinnerOfDuel;

	public function __construct(int $type, EventArena $arena){
		$this->type = $type;
		$this->arena = $arena;
		$this->parties = [];
		$this->status = self::STATUS_PREPAIRING;
		$this->prize = false;
		$this->currentTick = 0;
		$this->currentEventTick = 0;
		$this->awaitingPlayersTick = 0;
		$this->currentDelay = 0;
		$this->endingDelay = 5;
		$this->eliminated = [];
		$this->current1vs1 = null;
		$this->startingDelay = 4;
		$this->maxNumberOfParties = 0;
		$this->lastWinnerOfDuel = null;
		$this->eventPlayers = [];
	}

	/**
	 * @param MineceitParty $party
	 *
	 * Adds a party to the list of parties.
	 */
	public function addParty(MineceitParty $party) : void{

		$partiesCount = $this->getParties(true);

		if(!$this->canJoin()){

			$player = $party->getOwner();

			$stop = true;

			$msg = null;

			$lang = $player->getLanguageInfo()->getLanguage();

			if($this->hasOpened()){
				$msg = $lang->getMessage(Language::EVENTS_MESSAGE_JOIN_FAIL_STARTED);
			}elseif($this->isAwaitingParties()){
				if($partiesCount < self::MAX_PARTIES){
					$stop = false;
				}else{
					$msg = $lang->getMessage(Language::EVENTS_MESSAGE_JOIN_FAIL_PLAYERS);
				}
			}else{
				$msg = $lang->getMessage(Language::EVENTS_MESSAGE_JOIN_FAIL_RUNNING);
			}

			if($msg !== null){
				$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
			}

			if($stop){
				return;
			}
		}

		$name = $party->getName();
		if(!isset($this->parties[$name])){
			$this->parties[$name] = $party;
		}

		$numParties = strval(count($this->parties));

		$playersLine = $this->isAwaitingParties() ? 3 : 1;

		$players = $party->getPlayers();
		foreach($players as $player){
			if(!isset($this->eventPlayers[$player->getName()]))
				$this->eventPlayers[] = $player;

			$duelHandler = MineceitCore::getDuelHandler();
			if($player->isInQueue()){
				$duelHandler->removeFromQueue($player, false);
			}

			$player->getKitHolder()->clearKit();

			$player->getExtensions()->enableFlying(false);
			$player->setGamemode(0);

			$arena = $this->getArena();
			$arena->teleportPlayer($player);

			$pSb = $player->getScoreboardInfo();
			if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
				$pSb->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
			}
		}

		foreach($this->parties as $party){
			foreach($party->getPlayers() as $player){
				if($player->isOnline()){
					$lang = $player->getLanguageInfo()->getLanguage();
					if($pName !== $name){
						$playersLineValue = $lang->getMessage(Language::PLAYERS_LABEL);
						$players = " " . $playersLineValue . ": " . TextFormat::LIGHT_PURPLE . strval($numParties) . " ";
						$player->getScoreboardInfo()->updateLineOfScoreboard($playersLine, $players);
					}

					$message = $lang->getMessage(Language::EVENTS_MESSAGE_JOIN_SUCCESS, ["name" => $name]);
					$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
				}
			}
		}
	}

	/**
	 * @param bool $intval
	 *
	 * @return array|int|MineceitParty[]
	 */
	public function getParties(bool $intval = false){
		return $intval ? count($this->parties) : $this->parties;
	}

	/**
	 * @return bool
	 *
	 * Determines if the player can join the event.
	 */
	public function canJoin() : bool{
		return $this->hasOpened() ? false : $this->isAwaitingParties();
	}

	/**
	 * @return bool
	 *
	 * Determines if the event is awaiting players.
	 */
	public function hasOpened() : bool{
		return $this->status === self::STATUS_PREPAIRING;
	}

	/**
	 * @return bool
	 *
	 * Determines if the event is awaiting players.
	 */
	public function isAwaitingParties() : bool{
		return $this->status === self::STATUS_AWAITING_PARTIES;
	} // need to do

	/**
	 * @return EventArena
	 *
	 * Gets the arena.
	 */
	public function getArena() : EventArena{
		return $this->arena;
	}

	/**
	 * @param MineceitParty $party
	 * @param bool          $message
	 * @param bool          $eliminate
	 * @param bool          $broadcast
	 */
	public function removeParty(MineceitParty $party, bool $message = true, bool $eliminate = true, bool $broadcast = true) : void{
		$name = $party->getName();
		// $displayname = $player->getDisplayName();
		$partyCopy = $party;

		$alreadyEliminated = (bool) isset($this->eliminated[$name]);

		if(isset($this->parties[$name])){
			/** @var MineceitParty $player */
			// $player = $this->players[$name];
			unset($this->parties[$name]);

			if($this->status === self::STATUS_IN_PROGESS && $eliminate){

				$previousCount = count($this->eliminated);

				if(!isset($this->eliminated[$name])){
					$this->eliminated[$name] = count($this->eliminated) + 1;
				}

				$count = count($this->eliminated);

				if($previousCount !== $count){
					$count = strval($count);
					foreach($this->eventPlayers as $player){
						if($player->isOnline()){
							$lang = $player->getLanguage();
							$pMessage = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_ELIMINATED, ['num' => $count]);
							$player->updateLineOfScoreboard(4, $pMessage);
						}
					}
				}
			}

			$players = $partyCopy->getPlayers();
			foreach($players as $p){
				if($p->isOnline()){

					if($message){
						$lang = $p->getLanguageInfo()->getLanguage();
						$message = $lang->getMessage(Language::EVENTS_MESSAGE_LEAVE_EVENT_SENDER);
						$p->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
					}

					$p->getKitHolder()->clearKit();
					$p->reset(true, true);
					$p->setNormalNameTag();

					$itemHandler = MineceitCore::getItemHandler();
					$itemHandler->spawnHubItems($p);

					$pSb = $p->getScoreboardInfo();
					if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
						$pSb->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
					}
				}
			}
		}

		if($broadcast){

			$playersLine = $this->isAwaitingParties() ? 3 : 1;

			$numParties = strval(count($this->parties));

			$partiesLeft = strval($this->numPartiesLeft());

			foreach($this->eventPlayers as $player){

				if($player->isOnline()){

					$lang = $player->getLanguage();

					if(!$player->isInEventDuel() || !$player->isInEventBoss()){
						$playersLabel = $lang->getMessage(Language::PLAYERS_LABEL);
						$msg = " " . $playersLabel . ": " . TextFormat::LIGHT_PURPLE . "{$numPlayers} ";

						$player->updateLineOfScoreboard($playersLine, $msg);
					}

					$leaveEvent = $lang->getMessage(Language::EVENTS_MESSAGE_LEAVE_EVENT_RECEIVER, ["name" => $name]);
					$message = $leaveEvent;

					if($this->status === self::STATUS_IN_PROGESS && $eliminate && !$alreadyEliminated){
						$left = TextFormat::RESET . TextFormat::DARK_GRAY . '(' . TextFormat::RED . $lang->getMessage(Language::EVENT_MESSAGE_PLAYERS_LEFT, ['num' => $playersLeft]) . TextFormat::DARK_GRAY . ")";
						$message = $leaveEvent . " {$left}";
					}

					$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
				}
			}
		}
	}

	/**
	 * @return int
	 */
	public function numPartiesLeft() : int{
		return count($this->getPartiessLeft());
	}

	/**
	 * Updates the event.
	 */
	public function update() : void{

		if($this->status === self::STATUS_AWAITING_PARTIES){

			$partiesCount = count($this->parties);

			$minutes = MineceitUtil::ticksToMinutes($this->awaitingPlayersTick);
			$seconds = MineceitUtil::ticksToSeconds($this->awaitingPlayersTick);

			if($this->awaitingPlayersTick % 20 === 0){

				// Updates the time until event starts.
				foreach($this->eventPlayers as $player){

					if($player->isOnline()){
						$lang = $player->getLanguage();
						$startingTime = $this->getTimeUntilStart();
						$startingLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_STARTING_IN, ["time" => $startingTime]) . " ";
						$player->updateLineOfScoreboard(1, $startingLine);
					}
				}

				$maxSeconds = MineceitUtil::ticksToSeconds(MineceitUtil::minutesToTicks(self::MINUTES_AWAITING_PARTIES));
				$seconds = $maxSeconds - $seconds;
				if($seconds <= 5 && $seconds >= 0){

					if($seconds === 5){

						foreach($this->eventPlayers as $player){
							if($player->isOnline()){
								$msg = $this->getCountdownMessage($player->getLanguage(), $seconds);
								$player->sendTitle($msg, '', 5, 20, 5);
							}
						}
					}elseif($seconds !== 0){

						foreach($this->eventPlayers as $player){
							if($player->isOnline()){
								$msg = $this->getJustCountdown($player->getLanguage(), $seconds);
								$player->sendTitle($msg, '', 5, 20, 5);
							}
						}
					}
				}
			}

			if($minutes >= self::MINUTES_AWAITING_PARTIES){

				$this->awaitingPlayersTick = 0;

				if($partiesCount < self::MIN_PARTIES){

					foreach($this->eventPlayers as $player){
						if($player->isOnline()){
							$lang = $player->getLanguage();
							$msg = $lang->getMessage(Language::EVENTS_MESSAGE_CANCELED);
							$this->removePlayer($player, false, false, false);
							$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
						}
					}

					$this->status = self::STATUS_PREPAIRING;
					$this->resetEverything();
					$this->currentTick++;
					return;
				}

				$this->status = self::STATUS_IN_PROGESS;
				$this->maxNumberOfParties = $playersCount;

				// Removes the scoreboard lines when the event starts.
				foreach($this->eventPlayers as $player){
					if($player->isOnline()){
						$player->reloadScoreboard();
						$lang = $player->getLanguage();
						$title = $lang->getMessage(Language::EVENTS_MESSAGE_STARTING_NOW);
						$player->sendTitle($title, "", 5, 20, 5);
					}
				}

				$this->awaitingPlayersTick++;
				$this->currentTick++;
				return;
			}

			$this->awaitingPlayersTick++;
		}elseif($this->status === self::STATUS_IN_PROGESS){

			if($this->startingDelay > 0){
				if($this->currentTick % 20 === 0){
					$this->startingDelay--;
					if($this->startingDelay < 0){
						$this->startingDelay = 0;
					}
				}
				$this->currentTick++;
				return;
			}


			if($this->currentDelay > 0){
				if($this->currentTick % 20 === 0){
					$this->currentDelay--;
					if($this->currentDelay < 0){
						$this->currentDelay = 0;
					}
				}
				$this->currentTick++;
				return;
			}

			if($this->current1vs1 === null){

				$partiesLeft = $this->getPartiesLeft();
				$partiesLeftKeys = array_keys($partiesLeft);
				$count = count($partiesLeftKeys);

				if($this->checkWinner()){
					$this->currentTick++;
					return;
				}

				$p1Key = $partiesLeft[mt_rand(0, $count - 1)];
				$p2Key = $partiesLeft[mt_rand(0, $count - 1)];

				// TODO TEST
				// Ensures that p2 and p1 are not the same.
				while($p2Key === $p1Key || ($count >= 3 && $this->lastWinnerOfDuel !== null && $this->lastWinnerOfDuel === $p2Key)){
					$p2Key = $partiesLeft[mt_rand(0, $count - 1)];
				}

				/** @var MineceitParty $party1 */
				$party1 = $partiesLeft[$p1Key];
				/** @var MineceitParty $party2 */
				$party2 = $partiesLeft[$p2Key];

				$this->createNewDuel($party1, $party2);
			}else{

				$this->current1vs1->update();

				if($this->current1vs1->getStatus() === MineceitPartyTournamentDuel::STATUS_ENDED){

					$results = $this->current1vs1->getResults();
					$winner = $results['winner'];
					$loser = $results['loser'];

					$this->lastWinnerOfDuel = $winner;

					if($winner !== null && $loser !== null){

						$alreadyEliminated = (bool) isset($this->eliminated[$loser]);

						if(!isset($this->eliminated[$loser])){
							$this->eliminated[$loser] = count($this->eliminated) + 1;
						}

						// if(($temp = MineceitUtil::getPlayerExact($loser)) !== null)
						//     $loser = $temp->getDisplayName();
						$loser = $loser->getName();

						$eliminated = strval(count($this->eliminated));

						$partiesLeft = strval($this->numPartiesLeft());

						if(!$alreadyEliminated){

							foreach($this->eventPlayers as $player){

								if($player->isOnline()){

									$lang = $player->getLanguage();
									$eliminatedLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_ELIMINATED, ["num" => $eliminated]) . " ";
									$player->updateLineOfScoreboard(4, $eliminatedLine);

									$eliminatedMsg = $lang->getMessage(Language::EVENTS_MESSAGE_ELIMINATED, ["name" => $loser]);
									$left = TextFormat::RESET . TextFormat::DARK_GRAY . '(' . TextFormat::RED . $lang->getMessage(Language::EVENT_MESSAGE_PLAYERS_LEFT, ['num' => $playersLeft]) . TextFormat::DARK_GRAY . ")";
									$msg = $eliminatedMsg . " {$left}";

									$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
								}
							}
						}
					}

					$this->current1vs1 = null;

					if($this->checkWinner()){
						$this->currentTick++;
						return;
					}

					$this->currentDelay = self::MAX_DELAY_SECONDS;
				}
			}
		}elseif($this->status === self::STATUS_ENDING){

			if($this->endingDelay > 0){
				if($this->currentTick % 20 === 0){
					$this->endingDelay--;
					if($this->endingDelay < 0){
						$this->endingDelay = 0;
					}
				}
			}elseif($this->endingDelay === 0){

				$this->end();

				$this->status = self::STATUS_PREPAIRING;
			}
		}


		$this->currentTick++;
	}

	/**
	 * @return string
	 */
	public function getTimeUntilStart() : string{

		$minutes = MineceitUtil::minutesToTicks(self::MINUTES_AWAITING_PARTIES);
		$minutes = MineceitUtil::ticksToSeconds($minutes);
		$time = $minutes - MineceitUtil::ticksToSeconds($this->awaitingPlayersTick);

		$seconds = $time % 60;
		$minutes = intval($time / 60);

		$result = '%min%:%sec%';

		$secStr = "$seconds";
		$minStr = "$minutes";

		if($seconds < 10)
			$secStr = '0' . $seconds;

		if($minutes < 10)
			$minStr = '0' . $minutes;

		return str_replace('%min%', $minStr, str_replace('%sec%', $secStr, $result));
	}

	/**
	 * @param Language $lang
	 * @param int      $countdown
	 *
	 * @return string
	 */
	private function getCountdownMessage(Language $lang, int $countdown) : string{
		return $lang->generalMessage(Language::EVENTS_MESSAGE_COUNTDOWN, ["num" => strval($countdown)]);
	}

	/**
	 * @param Language $lang
	 * @param int      $countdown
	 *
	 * @return string
	 */
	private function getJustCountdown(Language $lang, int $countdown) : string{
		$color = MineceitUtil::getThemeColor();
		$message = TextFormat::BOLD . $color . "$countdown...";
		return $message;
	}

	/**
	 * Resets everything back to their original state.
	 */
	private function resetEverything() : void{

		$this->type = $type;
		$this->arena = $arena;
		$this->parties = [];
		$this->status = self::STATUS_PREPAIRING;
		$this->prize = false;
		$this->currentTick = 0;
		$this->currentEventTick = 0;
		$this->awaitingPlayersTick = 0;
		$this->currentDelay = 0;
		$this->endingDelay = 5;
		$this->eliminated = [];
		$this->current1vs1 = null;
		$this->startingDelay = 4;
		$this->maxNumberOfParties = 0;
		$this->lastWinnerOfDuel = null;
		$this->eventPlayers = [];
	}

	/**
	 * @return array|MineceitPlayer[]
	 *
	 * Gets the players left.
	 */
	protected function getPartiesLeft(){
		return array_diff_key($this->parties, $this->eliminated);
	}

	/**
	 * @return bool
	 *
	 * Checks for a winner of the event.
	 */
	private function checkWinner() : bool{

		$partiesLeft = $this->getPartiesLeft();
		$partiesLeftKeys = array_keys($partiesLeft);
		$count = count($partiesLeftKeys);

		if($count === 1){
			$this->status = self::STATUS_ENDING;
			$this->winner = (string) $partiesLeftKeys[0];
			return true;
		}elseif($count === 0){
			$minimum = null;
			$winner = null;
			foreach($this->eliminated as $name => $place){
				if($minimum === null || $place < $minimum){
					$minimum = $place;
					$winner = $name;
				}
			}
			if($winner !== null){
				$this->status = self::STATUS_ENDING;
				$this->winner = (string) $winner;
			}
			return true;
		}

		return false;
	}

	/**
	 * @param MineceitPlayer $p1
	 * @param MineceitPlayer $p2
	 *
	 * Creates a new duel.
	 */
	public function createNewDuel(MineceitParty $p1, MineceitParty $p2) : void{
		$this->current1vs1 = new MineceitTournamentDuel($p1, $p2, $this);
	}

	/**
	 *
	 * Ends the event.
	 */
	public function end() : void{

		$itemHandler = MineceitCore::getItemHandler();

		$eliminated = count($this->eliminated);

		/** @var MineceitPlayer[] $onlinePlayers */
		$onlinePlayers = Server::getInstance()->getOnlinePlayers();

		foreach($onlinePlayers as $player){

			$name = $player->getName();
			$lang = $player->getLanguageInfo()->getLanguage();
			$winner = $this->winner ?? $lang->getMessage(Language::NONE);

			if(isset($this->eventPlayers[$name])){

				$place = 1;

				if(isset($this->eliminated[$name])){
					$number = (int) $this->eliminated[$name];
					$place = ($eliminated - $number) + 2;
				}

				if(($temp = MineceitUtil::getPlayerExact($winner)) !== null)
					$winner = $temp->getDisplayName();

				$winnerMessage = $lang->getMessage(Language::DUELS_MESSAGE_WINNER, ["name" => $winner]);

				$separator = '--------------------------';

				$postfix = MineceitUtil::getOrdinalPostfix($place, $lang);
				$num = $postfix;
				if($lang->doesShortenOrdinals()){
					$num = strval($place) . $postfix;
				}

				$resultMessage = $lang->getMessage(Language::EVENTS_MESSAGE_RESULT, ["place" => $num]);

				$placeMessage = $resultMessage;

				$array = [$separator, $winnerMessage, $placeMessage, $separator];

				foreach($array as $message){
					$player->sendMessage($message);
				}

				$player->getKitHolder()->clearKit();
				$player->reset(true, true);
				$player->setNormalNameTag();
				$itemHandler->spawnHubItems($player, false);

				$pSb = $player->getScoreboardInfo();
				if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
					$pSb->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
				}
			}else{
				$eventName = $this->getName();
				$message = $lang->getMessage(Language::EVENT_WINNER_ANNOUNCEMENT, ['winner' => $winner, 'event' => $eventName]);
				$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
			}
		}

		$winner = $this->winner ?? null;

		if($winner !== null && $this->prize === true){
			if(($temp = MineceitUtil::getPlayerExact($winner)) !== null){
				$tag = 'None';
				$coins_gain = 0;
				switch($this->type){
					case self::TYPE_SUMO:
						$tag = TextFormat::GREEN . TextFormat::BOLD . 'WRESTLER';
						$coins_gain = 500;
						break;
					case self::TYPE_GAPPLE:
						$tag = TextFormat::YELLOW . TextFormat::BOLD . 'EATER';
						$coins_gain = 500;
						break;
					case self::TYPE_NODEBUFF:
						$tag = TextFormat::RED . TextFormat::BOLD . 'POTTER';
						$coins_gain = 500;
						break;
				}
				$temp->getStatsInfo()->addCoins($coins_gain);
				$temp->getStatsInfo()->addExp(300);
				$temp->setValidTags($tag);
			}
		}

		$this->resetEverything();
	}

	/**
	 * @return string
	 */
	public function getName() : string{

		switch($this->type){

			case self::TYPE_SUMO:
				return "Sumo";
			case self::TYPE_GAPPLE:
				return "Gapple";
			case self::TYPE_NODEBUFF:
				return "NoDebuff";
			case self::TYPE_FIST:
				return "Fist";
		}

		return "";
	}

	/**
	 * @param MineceitParty $party
	 *
	 * @return bool
	 *
	 * Determines if the player is in the event.
	 */
	public function isParty(MineceitParty $party) : bool{
		return isset($this->parties[$party->getName()]);
	}

	/**
	 * @param MineceitParty $party
	 *
	 * @return bool
	 */
	public function isEliminated(MineceitParty $party) : bool{
		return isset($this->eliminated[$party->getName()]);
	}

	/**
	 * @return MineceitPartyTournamentDuel|null
	 */
	public function getCurrentDuel(){
		return $this->current1vs1;
	}

	/**
	 * @return int
	 *
	 * Gets the type of event.
	 */
	public function getType() : int{
		return $this->type;
	}

	/**
	 * @param bool $count
	 *
	 * @return array|int
	 *
	 * Gets the eliminated players.
	 */
	public function getEliminated(bool $count = false){
		return $count ? count($this->eliminated) : array_keys($this->eliminated);
	}

	/**
	 * @return bool
	 *
	 * Determines if the event is awaiting players.
	 */
	public function setOpened(bool $prize = false) : void{
		$this->prize = $prize;
		$this->status = self::STATUS_AWAITING_PARTIES;
	}

	/**
	 * @param Language $lang
	 *
	 * @return string
	 */
	public function formatForForm(Language $lang) : string{

		$name = $lang->getMessage(Language::EVENT_FORM_EVENT_FORMAT, ['name' => $this->getName()]);

		$in_progress = $lang->getMessage(Language::EVENT_FORM_LABEL_IN_PROGRESS);
		$ending = $lang->getMessage(Language::EVENT_FORM_LABEL_ENDING);

		$partyCount = $this->getParties(true);

		$open = $lang->getMessage(Language::OPEN);
		$closed = $lang->getMessage(Language::CLOSED);
		$parties = $lang->getMessage(Language::PLAYERS_LABEL);

		$status = TextFormat::GRAY . "{$parties}: " . TextFormat::WHITE . strval($this->getParties(true)) . TextFormat::GRAY . " Start in: " . TextFormat::WHITE . $this->getTimeUntilStart();

		if($this->hasOpened()){

			$status = TextFormat::RED . TextFormat::BOLD . $closed;
		}elseif($this->hasStarted()){

			$status = TextFormat::RED . TextFormat::BOLD . $in_progress;
		}elseif($this->hasEnded()){
			$status = TextFormat::GOLD . TextFormat::BOLD . $ending;
		}

		return $name . "\n" . $status;
	}

	/**
	 * @return bool
	 *
	 * Determines if the event has started.
	 */
	public function hasStarted() : bool{
		return $this->status === self::STATUS_IN_PROGESS;
	}

	/**
	 * @return bool
	 *
	 * Determines if the event has ended.
	 */
	public function hasEnded() : bool{
		return $this->status === self::STATUS_ENDING;
	}

	/**
	 * @return int
	 *
	 * Gets the number of players at the start of the game.
	 */
	public function getMaxNumberParties() : int{
		return $this->maxNumberOfParties;
	}
}
