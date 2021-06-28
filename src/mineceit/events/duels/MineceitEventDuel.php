<?php

declare(strict_types=1);

namespace mineceit\events\duels;

use mineceit\arenas\EventArena;
use mineceit\events\MineceitEvent;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\utils\TextFormat;

class MineceitEventDuel{

	const STATUS_STARTING = 0;
	const STATUS_IN_PROGRESS = 1;
	const STATUS_ENDING = 2;
	const STATUS_ENDED = 3;

	/** @var MineceitPlayer */
	private $p1;
	/** @var string */
	private $p1Name;
	/** @var string */
	private $p1DisplayName;

	/** @var MineceitPlayer */
	private $p2;
	/** @var string */
	private $p2Name;
	/** @var string */
	private $p2DisplayName;

	/** @var int */
	private $currentTick;

	/** @var int */
	private $countdownSeconds;

	/** @var int */
	private $durationSeconds;

	/** @var MineceitEvent */
	private $event;

	/** @var int */
	private $status;

	/** @var string|null */
	private $winner;

	/** @var string|null */
	private $loser;

	public function __construct(MineceitPlayer $p1, MineceitPlayer $p2, MineceitEvent $event){
		$this->p1 = $p1;
		$this->p2 = $p2;
		$this->currentTick = 0;
		$this->durationSeconds = 0;
		$this->countdownSeconds = 5;
		$this->event = $event;
		$this->status = self::STATUS_STARTING;
		$this->p1Name = $p1->getName();
		$this->p2Name = $p2->getName();
		$this->p1DisplayName = $p1->getDisplayName();
		$this->p2DisplayName = $p2->getDisplayName();
	}

	/**
	 * Updates the duel.
	 */
	public function update() : void{

		$this->currentTick++;

		$checkSeconds = $this->currentTick % 20 === 0;

		if(!$this->p1->isOnline() || !$this->p2->isOnline()){

			if($this->p1->isOnline()){

				$this->winner = $this->p1Name;
				$this->loser = $this->p2Name;

				if($this->status !== self::STATUS_ENDED){

					$this->p1->getKitHolder()->clearKit();
					$this->p1->reset(true, false);
					$arena = $this->event->getArena();
					$arena->teleportPlayer($this->p1);
					$this->p1->getScoreboardInfo()->setScoreboard(
						Scoreboard::SCOREBOARD_EVENT_SPEC
					);
					$this->p1->setNormalNameTag();
				}
			}elseif($this->p2->isOnline()){

				$this->winner = $this->p2Name;
				$this->loser = $this->p1Name;

				if($this->status !== self::STATUS_ENDED){

					$this->p2->getKitHolder()->clearKit();
					$this->p2->reset(true, false);
					$arena = $this->event->getArena();
					$arena->teleportPlayer($this->p2);
					$this->p2->getScoreboardInfo()->setScoreboard(
						Scoreboard::SCOREBOARD_EVENT_SPEC
					);
					$this->p2->setNormalNameTag();
				}
			}

			$this->status = self::STATUS_ENDED;
			return;
		}

		$p1Lang = $this->p1->getLanguageInfo()->getLanguage();
		$p2Lang = $this->p2->getLanguageInfo()->getLanguage();

		if($this->status === self::STATUS_STARTING){

			if($this->currentTick === 4){
				$this->setPlayersInDuel();
			}

			if($checkSeconds){

				$p1Sb = $this->p1->getScoreboardInfo();
				$p2Sb = $this->p2->getScoreboardInfo();

				if($p1Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE && $p1Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_EVENT_DUEL){
					$p1Sb->setScoreboard(Scoreboard::SCOREBOARD_EVENT_DUEL);
				}

				if($p2Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE && $p2Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_EVENT_DUEL){
					$p2Sb->setScoreboard(Scoreboard::SCOREBOARD_EVENT_DUEL);
				}

				// Countdown messages.
				if($this->countdownSeconds === 5){

					$p1Msg = $this->getCountdownMessage(true, $p1Lang, $this->countdownSeconds);
					$p2Msg = $this->getCountdownMessage(true, $p2Lang, $this->countdownSeconds);
					$this->p1->sendTitle($p1Msg, '', 5, 20, 5);
					$this->p2->sendTitle($p2Msg, '', 5, 20, 5);
				}elseif($this->countdownSeconds !== 0){

					$p1Msg = $this->getJustCountdown($p1Lang, $this->countdownSeconds);
					$p2Msg = $this->getJustCountdown($p2Lang, $this->countdownSeconds);
					$this->p1->sendTitle($p1Msg, '', 5, 20, 5);
					$this->p2->sendTitle($p2Msg, '', 5, 20, 5);
				}else{

					$p1Msg = $p1Lang->generalMessage(Language::DUELS_MESSAGE_STARTING);
					$p2Msg = $p2Lang->generalMessage(Language::DUELS_MESSAGE_STARTING);
					$this->p1->sendTitle($p1Msg, '', 5, 10, 5);
					$this->p2->sendTitle($p2Msg, '', 5, 10, 5);
				}

				if($this->countdownSeconds === 0){
					$this->status = self::STATUS_IN_PROGRESS;
					$this->p1->setImmobile(false);
					$this->p2->setImmobile(false);
					$this->p1->setCombatNameTag();
					$this->p2->setCombatNameTag();
					return;
				}

				$this->countdownSeconds--;
			}
		}elseif($this->status === self::STATUS_IN_PROGRESS){

			$arena = $this->event->getArena();

			$centerMinY = $arena->getCenter()->getY() - 4;

			$p1Pos = $this->p1->getPosition();
			$p2Pos = $this->p2->getPosition();

			$p1Y = $p1Pos->getY();
			$p2Y = $p2Pos->getY();

			if($this->event->getType() === MineceitEvent::TYPE_SUMO){

				if($p1Y < $centerMinY){
					$this->winner = $this->p2Name;
					$this->loser = $this->p1Name;
					$this->status = self::STATUS_ENDING;
					return;
				}

				if($p2Y < $centerMinY){
					$this->winner = $this->p1Name;
					$this->loser = $this->p2Name;
					$this->status = self::STATUS_ENDING;
					return;
				}
			}

			// Used for updating scoreboards.
			if($checkSeconds){

				$p1Duration = TextFormat::WHITE . $p1Lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
				$p2Duration = TextFormat::WHITE . $p2Lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);

				$p1DurationStr = TextFormat::WHITE . ' ' . $p1Duration . ': ' . $this->getDuration();
				$p2DurationStr = TextFormat::WHITE . ' ' . $p2Duration . ': ' . $this->getDuration();

				$p1Sb = $this->p1->getScoreboardInfo();
				$p2Sb = $this->p2->getScoreboardInfo();
				$p1Sb->updateLineOfScoreboard(1, $p1DurationStr);
				$p2Sb->updateLineOfScoreboard(1, $p2DurationStr);

				// Add a maximum duel duration if necessary.

				$this->durationSeconds++;
			}
		}elseif($this->status === self::STATUS_ENDING){

			if($this->p1->isOnline()){
				$this->p1->getKitHolder()->clearKit();
				$this->p1->reset(true, false);
				$arena = $this->event->getArena();
				$arena->teleportPlayer($this->p1);
				$this->p1->getScoreboardInfo()->setScoreboard(
					Scoreboard::SCOREBOARD_EVENT_SPEC
				);
				$this->p1->setNormalNameTag();
			}

			if($this->p2->isOnline()){
				$this->p2->getKitHolder()->clearKit();
				$this->p2->reset(true, false);
				$arena = $this->event->getArena();
				$arena->teleportPlayer($this->p2);
				$this->p2->getScoreboardInfo()->setScoreboard(
					Scoreboard::SCOREBOARD_EVENT_SPEC
				);
				$this->p2->setNormalNameTag();
			}

			$this->status = self::STATUS_ENDED;
		}
	}

	/**
	 * Sets the players in a duel/
	 */
	protected function setPlayersInDuel() : void{

		$arena = $this->event->getArena();

		$this->p1->setGamemode(0);
		$this->p2->setGamemode(0);

		$this->p1->getExtensions()->enableFlying(false);
		$this->p2->getExtensions()->enableFlying(false);

		$this->p1->setImmobile();
		$this->p2->setImmobile();

		$this->p1->getExtensions()->clearAll();
		$this->p2->getExtensions()->clearAll();

		$arena->teleportPlayer($this->p1, EventArena::P1);
		$arena->teleportPlayer($this->p2, EventArena::P2);

		$p1Lang = $this->p1->getLanguageInfo()->getLanguage();
		$p2Lang = $this->p2->getLanguageInfo()->getLanguage();

		$p1Message = $p1Lang->getMessage(Language::EVENTS_MESSAGE_DUELS_MATCHED, ["name" => $this->p2DisplayName]);
		$p2Message = $p2Lang->getMessage(Language::EVENTS_MESSAGE_DUELS_MATCHED, ["name" => $this->p1DisplayName]);

		$this->p1->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $p1Message);
		$this->p2->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $p2Message);
	}

	/**
	 * @param bool     $title
	 * @param Language $lang
	 * @param int      $countdown
	 *
	 * @return string
	 */
	private function getCountdownMessage(bool $title, Language $lang, int $countdown) : string{

		$message = $lang->generalMessage(Language::DUELS_MESSAGE_COUNTDOWN);

		$color = MineceitUtil::getThemeColor();

		if(!$title)
			$message .= TextFormat::BOLD . $color . $countdown . '...';
		else{
			$message = $message . "\n" . $color . TextFormat::BOLD . "$countdown...";
		}

		return $message;
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
	 * @return string
	 *
	 * Gets the duration of the duel for scoreboard;
	 */
	public function getDuration() : string{

		$seconds = $this->durationSeconds % 60;
		$minutes = intval($this->durationSeconds / 60);

		$color = MineceitUtil::getThemeColor();

		$result = $color . '%min%:%sec%';

		$secStr = "$seconds";
		$minStr = "$minutes";

		if($seconds < 10)
			$secStr = '0' . $seconds;

		if($minutes < 10)
			$minStr = '0' . $minutes;

		return str_replace('%min%', $minStr, str_replace('%sec%', $secStr, $result));
	}

	/**
	 *
	 * @param MineceitPlayer $player
	 *
	 * @return MineceitPlayer|null
	 */
	public function getOpponent(MineceitPlayer $player) : ?MineceitPlayer{
		if($this->p1->equalsPlayer($player)){
			return $this->p2;
		}elseif($this->p2->equalsPlayer($player)){
			return $this->p1;
		}

		return null;
	}

	/**
	 * @return int
	 *
	 * Gets the status of the duel.
	 */
	public function getStatus() : int{
		return $this->status;
	}

	/**
	 * @return array
	 *
	 * Gets the results of the duel.
	 */
	public function getResults() : array{
		return ['winner' => $this->winner, 'loser' => $this->loser];
	}

	/**
	 * @param MineceitPlayer|null $winner
	 */
	public function setResults(MineceitPlayer $winner = null) : void{

		if($winner !== null){

			$name = $winner->getName();

			if($this->isPlayer($winner)){
				$loser = $name === $this->p1Name ? $this->p2Name : $this->p1Name;
				$this->winner = $name;
				$this->loser = $loser;
			}
		}

		$this->status = self::STATUS_ENDING;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return bool
	 */
	public function isPlayer(MineceitPlayer $player) : bool{
		return $this->p1->equalsPlayer($player) || $this->p2->equalsPlayer($player);
	}
}
