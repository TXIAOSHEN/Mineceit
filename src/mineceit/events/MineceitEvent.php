<?php

declare(strict_types=1);

namespace mineceit\events;

use mineceit\arenas\EventArena;
use mineceit\bossbar\BossBarUtil;
use mineceit\events\duels\MineceitEventBoss;
use mineceit\events\duels\MineceitEventDuel;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MineceitEvent{

	const TYPE_SUMO = 0;
	const TYPE_GAPPLE = 1;
	const TYPE_FIST = 2;
	const TYPE_NODEBUFF = 3;
	const TYPE_BOSS = 4;

	const STATUS_PREPAIRING = 0;
	const STATUS_AWAITING_PLAYERS = 1;
	const STATUS_IN_PROGESS = 2;
	const STATUS_ENDING = 3;

	const MINUTES_AWAITING_PLAYERS = 3;

	const MAX_PLAYERS = 30;
	const MIN_PLAYERS = 2;

	const MAX_DELAY_SECONDS = 3;

	/** @var int */
	protected $type;

	/** @var EventArena */
	protected $arena;

	/** @var int */
	protected $status;

	/** @var array|MineceitPlayer[] */
	protected $players;

	/** @var MineceitPlayer */
	protected $boss;

	/** @var int */
	protected $currentTick;

	/** @var int */
	protected $currentEventTick;

	/** @var int */
	protected $awaitingPlayersTick;

	/** @var int */
	protected $currentDelay;

	/** @var int */
	protected $endingDelay;

	/** @var array */
	protected $eliminated;

	/** @var MineceitEventDuel|null */
	protected $current1vs1;

	/** @var MineceitEventBoss|null */
	protected $currentboss;

	/** @var string|null */
	protected $winner;

	/** @var bool */
	protected $prize;

	/** @var int */
	protected $startingDelay;

	/** @var int */
	private $maxNumberOfPlayers;

	/** @var string|null */
	private $lastWinnerOfDuel;

	public function __construct(int $type, EventArena $arena){
		$this->type = $type;
		$this->arena = $arena;
		$this->players = [];
		$this->status = self::STATUS_PREPAIRING;
		$this->prize = false;
		$this->boss = null;
		$this->currentTick = 0;
		$this->currentEventTick = 0;
		$this->awaitingPlayersTick = 0;
		$this->currentDelay = 0;
		$this->endingDelay = 5;
		$this->eliminated = [];
		$this->current1vs1 = null;
		$this->currentboss = null;
		$this->startingDelay = 4;
		$this->maxNumberOfPlayers = 0;
		$this->lastWinnerOfDuel = null;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * Adds a player to the list of players.
	 */
	public function addPlayer(MineceitPlayer $player) : void{

		$playerCount = $this->getPlayers(true);

		if(!$this->canJoin()){

			$stop = true;

			$msg = null;

			$lang = $player->getLanguageInfo()->getLanguage();

			if($this->hasOpened()){
				$msg = $lang->getMessage(Language::EVENTS_MESSAGE_JOIN_FAIL_STARTED);
			}elseif($this->isAwaitingPlayers()){
				if($playerCount < self::MAX_PLAYERS){
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

		$duelHandler = MineceitCore::getDuelHandler();
		if($player->isInQueue()){
			$duelHandler->removeFromQueue($player, false);
		}

		$player->getKitHolder()->clearKit();
		$player->getExtensions()->enableFlying(false);
		$player->setGamemode(0);

		$arena = $this->getArena();
		$arena->teleportPlayer($player);

		$name = $player->getName();
		$displayname = $player->getDisplayName();
		if(!isset($this->players[$name])){
			$this->players[$name] = $player;
		}

		$pSb = $player->getScoreboardInfo();
		if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
			$pSb->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
		}

		$numPlayers = strval(count($this->players));
		$playersLine = $this->isAwaitingPlayers() ? 3 : 1;

		foreach($this->players as $pName => $player){
			if($player->isOnline()){

				$lang = $player->getLanguageInfo()->getLanguage();

				if($pName !== $name){
					$playersLineValue = $lang->getMessage(Language::PLAYERS_LABEL);
					$players = " " . $playersLineValue . ": " . TextFormat::LIGHT_PURPLE . strval($numPlayers) . " ";
					$pSb->updateLineOfScoreboard($playersLine, $players);
				}

				$message = $lang->getMessage(Language::EVENTS_MESSAGE_JOIN_SUCCESS, ["name" => $displayname]);
				$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
			}
		}
	}

	/**
	 * @param bool $intval
	 *
	 * @return array|int|MineceitPlayer[]
	 */
	public function getPlayers(bool $intval = false){
		return $intval ? count($this->players) : $this->players;
	}

	/**
	 * @return bool
	 *
	 * Determines if the player can join the event.
	 */
	public function canJoin() : bool{
		return $this->hasOpened() ? false : $this->isAwaitingPlayers();
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
	public function isAwaitingPlayers() : bool{
		return $this->status === self::STATUS_AWAITING_PLAYERS;
	}

	/**
	 * @return EventArena
	 *
	 * Gets the arena.
	 */
	public function getArena() : EventArena{
		return $this->arena;
	}

	/**
	 * Updates the event.
	 */
	public function update() : void{

		if($this->status === self::STATUS_AWAITING_PLAYERS){

			$playersCount = count($this->players);

			// if($playersCount <= 0) {
			//     /* if($this->awaitingPlayersTick > 0) {
			//         $this->awaitingPlayersTick = 0;
			//     } */
			//     //$this->awaitingPlayersTick++;
			//     $this->currentTick++;
			//     return;
			// }

			$minutes = MineceitUtil::ticksToMinutes($this->awaitingPlayersTick);
			$seconds = MineceitUtil::ticksToSeconds($this->awaitingPlayersTick);

			if($this->awaitingPlayersTick % 20 === 0){

				// Updates the time until event starts.
				foreach($this->players as $player){

					if($player->isOnline()){

						$lang = $player->getLanguageInfo()->getLanguage();
						$startingTime = $this->getTimeUntilStart();
						$startingLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_STARTING_IN, ["time" => $startingTime]) . " ";
						$player->getScoreboardInfo()->updateLineOfScoreboard(1, $startingLine);
					}
				}

				$maxSeconds = MineceitUtil::ticksToSeconds(MineceitUtil::minutesToTicks(self::MINUTES_AWAITING_PLAYERS));
				$seconds = $maxSeconds - $seconds;
				if($seconds <= 5 && $seconds >= 0){

					if($seconds === 5){

						foreach($this->players as $player){
							if($player->isOnline()){
								$msg = $this->getCountdownMessage($player->getLanguageInfo()->getLanguage(), $seconds);
								$player->sendTitle($msg, '', 5, 20, 5);
							}
						}
					}elseif($seconds !== 0){

						foreach($this->players as $player){
							if($player->isOnline()){
								$msg = $this->getJustCountdown($player->getLanguageInfo()->getLanguage(), $seconds);
								$player->sendTitle($msg, '', 5, 20, 5);
							}
						}
					}
				}
			}

			if($minutes >= self::MINUTES_AWAITING_PLAYERS){

				$this->awaitingPlayersTick = 0;

				if($playersCount < self::MIN_PLAYERS){

					foreach($this->players as $player){
						if($player->isOnline()){
							$lang = $player->getLanguageInfo()->getLanguage();
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
				$this->maxNumberOfPlayers = $playersCount;

				// Removes the scoreboard lines when the event starts.
				foreach($this->players as $player){
					if($player->isOnline()){
						$pSb = $player->getScoreboardInfo();
						$pSb->reloadScoreboard();
						$lang = $player->getLanguageInfo()->getLanguage();
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

			if($this->type === self::TYPE_BOSS){
				if($this->currentboss === null){
					$playersLeft = $this->getPlayersLeft();
					$playersLeftKeys = array_keys($playersLeft);
					$count = count($playersLeftKeys);
					$pKey = $playersLeftKeys[mt_rand(0, $count - 1)];
					$this->boss = $playersLeft[$pKey];
					unset($playersLeft[$pKey]);
					$this->createNewBoss($playersLeft, $this->boss);
				}else{

					$this->currentboss->update();

					if($this->currentboss->getStatus() === MineceitEventBoss::STATUS_IN_PROGRESS){

						$percentage = $this->boss->getHealth() / $this->boss->getMaxHealth();

						BossBarUtil::updateBossBar($this->players, $percentage);

						$loser = $this->currentboss->getEliminated();
						if($loser !== null){
							$this->currentboss->resetEliminated();
							$alreadyEliminated = (bool) isset($this->eliminated[$loser]);

							if(!isset($this->eliminated[$loser])){
								$this->eliminated[$loser] = count($this->eliminated) + 1;
							}

							if(($temp = MineceitUtil::getPlayerExact($loser)) !== null)
								$loser = $temp->getDisplayName();

							$eliminated = strval(count($this->eliminated));

							$playersLeft = strval($this->numPlayersLeft() - 1);

							if(!$alreadyEliminated){

								foreach($this->players as $player){

									if($player->isOnline() && $this->isEliminated($player)){

										$lang = $player->getLanguageInfo()->getLanguage();
										$eliminatedLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_ELIMINATED, ["num" => $eliminated]) . " ";
										$player->getScoreboardInfo()->updateLineOfScoreboard(4, $eliminatedLine);
										$eliminatedMsg = $lang->getMessage(Language::EVENTS_MESSAGE_ELIMINATED, ["name" => $loser]);
										$left = TextFormat::RESET . TextFormat::DARK_GRAY . '(' . TextFormat::RED . $lang->getMessage(Language::EVENT_MESSAGE_PLAYERS_LEFT, ['num' => $playersLeft]) . TextFormat::DARK_GRAY . ")";
										$msg = $eliminatedMsg . " {$left}";
										$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
									}
								}
							}
						}
					}elseif($this->currentboss->getStatus() === MineceitEventBoss::STATUS_ENDED){
						$results = $this->currentboss->getResults();
						$this->winner = $results['winner'];

						$this->status = self::STATUS_ENDING;
					}
				}
			}else{
				if($this->current1vs1 === null){

					$playersLeft = $this->getPlayersLeft();
					$playersLeftKeys = array_keys($playersLeft);
					$count = count($playersLeftKeys);

					if($this->checkWinner()){
						$this->currentTick++;
						return;
					}

					$p1Key = $playersLeftKeys[mt_rand(0, $count - 1)];
					$p2Key = $playersLeftKeys[mt_rand(0, $count - 1)];

					// TODO TEST
					// Ensures that p2 and p1 are not the same.
					while($p2Key === $p1Key || ($count >= 3 && $this->lastWinnerOfDuel !== null && $this->lastWinnerOfDuel === $p2Key)){
						$p2Key = $playersLeftKeys[mt_rand(0, $count - 1)];
					}

					/** @var MineceitPlayer $player1 */
					$player1 = $playersLeft[$p1Key];
					/** @var MineceitPlayer $player2 */
					$player2 = $playersLeft[$p2Key];

					$this->createNewDuel($player1, $player2);
				}else{

					$this->current1vs1->update();

					if($this->current1vs1->getStatus() === MineceitEventDuel::STATUS_ENDED){

						$results = $this->current1vs1->getResults();
						$winner = $results['winner'];
						$loser = $results['loser'];

						$this->lastWinnerOfDuel = $winner;

						if($winner !== null && $loser !== null){

							$alreadyEliminated = (bool) isset($this->eliminated[$loser]);

							if(!isset($this->eliminated[$loser])){
								$this->eliminated[$loser] = count($this->eliminated) + 1;
							}

							if(($temp = MineceitUtil::getPlayerExact($loser)) !== null)
								$loser = $temp->getDisplayName();

							$eliminated = strval(count($this->eliminated));

							$playersLeft = strval($this->numPlayersLeft());

							if(!$alreadyEliminated){

								foreach($this->players as $player){

									if($player->isOnline()){

										$lang = $player->getLanguageInfo()->getLanguage();
										$eliminatedLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_ELIMINATED, ["num" => $eliminated]) . " ";
										$player->getScoreboardInfo()->updateLineOfScoreboard(4, $eliminatedLine);
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

				if($this->type === self::TYPE_BOSS) $this->endBoss();
				else $this->end();

				$this->status = self::STATUS_PREPAIRING;
			}
		}


		$this->currentTick++;
	}

	/**
	 * @return string
	 */
	public function getTimeUntilStart() : string{

		$minutes = MineceitUtil::minutesToTicks(self::MINUTES_AWAITING_PLAYERS);
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
		return TextFormat::BOLD . MineceitUtil::getThemeColor() . "$countdown...";
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $message
	 * @param bool           $eliminate
	 * @param bool           $broadcast
	 */
	public function removePlayer(MineceitPlayer $player, bool $message = true, bool $eliminate = true, bool $broadcast = true) : void{
		$name = $player->getName();
		$displayname = $player->getDisplayName();
		$p = $player;
		$p->getExtensions()->getBossBar()
			->setEnabled(false);

		$alreadyEliminated = (bool) isset($this->eliminated[$name]);

		if(isset($this->players[$name])){
			/** @var MineceitPlayer $player */
			// $player = $this->players[$name];
			unset($this->players[$name]);

			if($this->status === self::STATUS_IN_PROGESS && $eliminate){

				$previousCount = count($this->eliminated);

				if(!isset($this->eliminated[$name])){
					$this->eliminated[$name] = count($this->eliminated) + 1;
				}

				$count = count($this->eliminated);

				if($previousCount !== $count){
					$count = strval($count);
					foreach($this->players as $player){
						if($player->isOnline()){
							$lang = $player->getLanguageInfo()->getLanguage();
							$pMessage = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_ELIMINATED, ['num' => $count]);
							$player->getScoreboardInfo()->updateLineOfScoreboard(
								4,
								$pMessage
							);
						}
					}
				}
			}

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

				$pSb = $player->getScoreboardInfo();
				if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
					$pSb->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
				}
			}
		}

		if($broadcast){

			$playersLine = $this->isAwaitingPlayers() ? 3 : 1;

			$numPlayers = strval(count($this->players));

			$playersLeft = strval($this->numPlayersLeft());

			foreach($this->players as $player){

				if($player->isOnline()){

					$lang = $player->getLanguageInfo()->getLanguage();

					if(!$player->isInEventDuel() || !$player->isInEventBoss()){
						$playersLabel = $lang->getMessage(Language::PLAYERS_LABEL);
						$msg = " " . $playersLabel . ": " . TextFormat::LIGHT_PURPLE . "{$numPlayers} ";
						$player->getScoreboardInfo()->updateLineOfScoreboard($playersLine, $msg);
					}

					$leaveEvent = $lang->getMessage(Language::EVENTS_MESSAGE_LEAVE_EVENT_RECEIVER, ["name" => $displayname]);
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
	public function numPlayersLeft() : int{
		return count($this->getPlayersLeft());
	}

	/**
	 * @return array|MineceitPlayer[]
	 *
	 * Gets the players left.
	 */
	protected function getPlayersLeft() : array{
		return array_diff_key($this->players, $this->eliminated);
	}

	/**
	 * Resets everything back to their original state.
	 */
	private function resetEverything() : void{

		$this->eliminated = [];
		$this->players = [];
		$this->current1vs1 = null;
		$this->currentboss = null;
		$this->currentDelay = 0;
		$this->endingDelay = 5;
		$this->prize = false;
		$this->winner = null;
		$this->boss = null;
		$this->currentEventTick = 0;
		$this->awaitingPlayersTick = 0;
		$this->startingDelay = 5;
		$this->maxNumberOfPlayers = 0;
		$this->lastWinnerOfDuel = null;
	}

	/**
	 * @param array|MineceitPlayer[] $players
	 * @param MineceitPlayer         $boss
	 *
	 * Creates a new boss.
	 */
	public function createNewBoss(array $players, MineceitPlayer $boss) : void{
		$this->currentboss = new MineceitEventBoss($players, $boss, $this);
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return bool
	 */
	public function isEliminated(MineceitPlayer $player) : bool{
		return isset($this->eliminated[$player->getName()]);
	}

	/**
	 * @return bool
	 *
	 * Checks for a winner of the event.
	 */
	private function checkWinner() : bool{

		$playersLeft = $this->getPlayersLeft();
		$playersLeftKeys = array_keys($playersLeft);
		$count = count($playersLeftKeys);

		if($count === 1){
			$this->status = self::STATUS_ENDING;
			$this->winner = (string) $playersLeftKeys[0];
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
	public function createNewDuel(MineceitPlayer $p1, MineceitPlayer $p2) : void{
		$this->current1vs1 = new MineceitEventDuel($p1, $p2, $this);
	}

	/**
	 *
	 * Ends the event.
	 */
	public function endBoss() : void{

		$itemHandler = MineceitCore::getItemHandler();

		$eliminated = count($this->eliminated);

		/** @var MineceitPlayer[] $onlinePlayers */
		$onlinePlayers = Server::getInstance()->getOnlinePlayers();

		foreach($onlinePlayers as $player){

			$name = $player->getName();
			$lang = $player->getLanguageInfo()->getLanguage();
			$winner = $this->winner ?? $lang->getMessage(Language::NONE);

			if(isset($this->players[$name])){

				$place = 1;

				if(isset($this->eliminated[$name])){
					$number = (int) $this->eliminated[$name];
					$place = ($eliminated - $number) + 2;
				}

				if($winner === 'Challenger'){
					$winner = $player->getDisplayName();
					if($this->boss === $player) $winner = 'Challenger';
				}else{
					if(($temp = MineceitUtil::getPlayerExact($winner)) !== null)
						$winner = $temp->getDisplayName();
				}

				$winnerMessage = $lang->getMessage(Language::DUELS_MESSAGE_WINNER, ["name" => $winner]);

				$separator = '--------------------------';

				$postfix = MineceitUtil::getOrdinalPostfix($place, $lang);
				$num = $postfix;
				if($lang->doesShortenOrdinals()){
					$num = strval($place) . $postfix;
				}

				$array = [$separator, $winnerMessage, $separator];

				foreach($array as $message){
					$player->sendMessage($message);
				}

				$player->getExtensions()->getBossBar()
					->setEnabled(false);
				$player->getKitHolder()->clearKit();
				$player->reset(true, true);
				$player->setNormalNameTag();
				$itemHandler->spawnHubItems($player, false);

				$pSb = $player->getScoreboardInfo();
				if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
					$pSb->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
				}
			}else{

				if($this->winner === null){
					continue;
				}

				$eventName = $this->getName();
				$message = $lang->getMessage(Language::EVENT_WINNER_ANNOUNCEMENT, ['winner' => $winner, 'event' => $eventName]);
				$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
			}
		}

		$winner = $this->winner ?? null;

		if($winner !== null && $this->prize === true){
			if($winner === 'Challenger'){
				foreach($this->players as $player){
					if($player === $this->boss) continue;
					$player->getStatsInfo()->addCoins(50);
					$player->getStatsInfo()->addExp(30);
				}
				$this->boss->getExtensions()
					->getBossBar()->setEnabled(false);
			}else{
				$this->boss->getExtensions()->getBossBar()->setEnabled(false);
				$this->boss->getStatsInfo()->addCoins(500);
				$this->boss->getStatsInfo()->addExp(300);
				$this->boss->setValidTags(TextFormat::RED . TextFormat::BOLD . 'BOSSY');
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
			case self::TYPE_BOSS:
				return "Boss";
		}

		return "";
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

			if(isset($this->players[$name])){

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
	 * @param MineceitPlayer $player
	 *
	 * @return bool
	 *
	 * Determines if the player is in the event.
	 */
	public function isPlayer(MineceitPlayer $player) : bool{
		return isset($this->players[$player->getName()]);
	}

	/**
	 * @return MineceitEventDuel|null
	 */
	public function getCurrentDuel() : ?MineceitEventDuel{
		return $this->current1vs1;
	}

	/**
	 * @return MineceitEventBoss|null
	 */
	public function getCurrentBoss() : ?MineceitEventBoss{
		return $this->currentboss;
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
	 * @param bool $prize
	 *
	 * Determines if the event is awaiting players.
	 */
	public function setOpened(bool $prize = false) : void{
		$this->prize = $prize;
		$this->status = self::STATUS_AWAITING_PLAYERS;
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

		$open = $lang->getMessage(Language::OPEN);
		$closed = $lang->getMessage(Language::CLOSED);
		$players = $lang->getMessage(Language::PLAYERS_LABEL);

		$status = TextFormat::GRAY . "{$players}: " . TextFormat::WHITE . strval($this->getPlayers(true)) . TextFormat::GRAY . " Start in: " . TextFormat::WHITE . $this->getTimeUntilStart();

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
	public function getNumberPlayersAtStart() : int{
		return $this->maxNumberOfPlayers;
	}
}
