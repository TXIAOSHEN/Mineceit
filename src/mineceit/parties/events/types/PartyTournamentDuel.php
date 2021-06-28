<?php

declare(strict_types=1);

namespace mineceit\events\duels;

use mineceit\arenas\EventArena;
use mineceit\events\MineceitEvent;
use mineceit\MineceitUtil;
use mineceit\parties\events\types\match\data\MineceitTeam;
use mineceit\parties\events\types\PartyTournament;
use mineceit\parties\MineceitParty;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\utils\TextFormat;

class MineceitPartyTournamentDuel{

	const STATUS_STARTING = 0;
	const STATUS_IN_PROGRESS = 1;
	const STATUS_ENDING = 2;
	const STATUS_ENDED = 3;

	/** @var MineceitParty */
	private $p1;
	/** @var string */
	private $p1Name;

	/** @var MineceitParty */
	private $p2;
	/** @var string */
	private $p2Name;

	/** @var int */
	private $currentTick;

	/** @var int */
	private $countdownSeconds;

	/** @var int */
	private $durationSeconds;

	/** @var PartyTournament */
	private $event;

	/** @var int */
	private $status;

	/** @var string|null */
	private $winner;

	/** @var string|null */
	private $loser;

	public function __construct(MineceitParty $p1, MineceitParty $p2, PartyTournament $event){
		$this->p1 = $p1;
		$this->p2 = $p2;
		$this->currentTick = 0;
		$this->durationSeconds = 0;
		$this->countdownSeconds = 5;
		$this->event = $event;
		$this->status = self::STATUS_STARTING;
		$this->p1Name = $p1->getName();
		$this->p2Name = $p2->getName();
		$color = [];
		$this->team1 = new MineceitTeam($color);
		$colors[] = $this->team1->getTeamColor();
		$this->team1->addPartyToTeam($this->$p1);
		$this->team2 = new MineceitTeam($color);
		$this->team2->addPartyToTeam($this->$p2);
		// $this->p1DisplayName  = $p1->getDisplayName();
		// $this->p2DisplayName  = $p2->getDisplayName();
	}

	/**
	 * Updates the duel.
	 */
	public function update() : void{

		$this->currentTick++;

		$checkSeconds = $this->currentTick % 20 === 0;

		$p1Players = $this->team1->getPlayers();
		$p2Players = $this->team2->getPlayers();
		/** @var MineceitPlayer[] $allPlayers */
		$allPlayers = array_merge($p1Players, $p2Players);

		// TODO PARTIES LEAVE
		if(count($p1Players) === 0 || count($p2Players) === 0){
			if(count($p2Players) === 0){

				$this->winner = $this->p1Name;
				$this->loser = $this->p2Name;

				if($this->status !== self::STATUS_ENDED){
					foreach($p1Players as $player){
						$player->getKitHolder()->clearKit();
						$player->reset(true, false);
						$arena = $this->event->getArena();
						$arena->teleportPlayer($player);
						$player->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
						$player->setNormalNameTag();
					}
				}

			}elseif(count($p1Players) === 0){

				$this->winner = $this->p2Name;
				$this->loser = $this->p1Name;

				if($this->status !== self::STATUS_ENDED){
					foreach($p2Players as $player){
						$player->getKitHolder()->clearKit();
						$player->reset(true, false);
						$arena = $this->event->getArena();
						$arena->teleportPlayer($player);
						$player->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
						$player->setNormalNameTag();
					}
				}
			}

			$this->status = self::STATUS_ENDED;
			return;
		}

		if($this->status === self::STATUS_STARTING){

			if($this->currentTick === 4){
				$this->setPartiesInDuel();
			}

			if($checkSeconds){

				foreach($allPlayers as $player){
					$pb = $player->getScoreboardInfo()->getScoreboardType();
					if($pb !== Scoreboard::SCOREBOARD_NONE && $pb !== Scoreboard::SCOREBOARD_EVENT_DUEL){
						$player->getScoreboardInfo()->setScoreboard(
							Scoreboard::SCOREBOARD_EVENT_DUEL);
					}

					$pLang = $player->getLanguageInfo()->getLanguage();
					// Countdown messages.
					if($this->countdownSeconds === 5){
						$pMsg = $this->getCountdownMessage(true, $pLang, $this->countdownSeconds);
						$player->sendTitle($pMsg, '', 5, 20, 5);
					}elseif($this->countdownSeconds !== 0){
						$pMsg = $this->getJustCountdown($pLang, $this->countdownSeconds);
						$player->sendTitle($pMsg, '', 5, 20, 5);
					}else{
						$pMsg = $pLang->generalMessage(Language::DUELS_MESSAGE_STARTING);
						$player->sendTitle($pMsg, '', 5, 10, 5);
					}

					if($this->countdownSeconds === 0){
						$this->status = self::STATUS_IN_PROGRESS;
						$player->setImmobile(false);
						$player->setCombatNameTag();
						return;
					}
				}

				$this->countdownSeconds--;
			}

		}elseif($this->status === self::STATUS_IN_PROGRESS){

			$arena = $this->event->getArena();

			$centerMinY = $arena->getCenter()->getY() - 4;

			foreach($p1Players as $player){
				$pLang = $player->getLanguageInfo()->getLanguage();
				$pPos = $player->getPosition();
				$pY = $pPos->getY();
				if($this->event->getType() === MineceitEvent::TYPE_SUMO){
					if($pY < $centerMinY){
						$this->setEliminatedPlayer($player, $this->team1);
					}
				}

				// Used for updating scoreboards.
				if($checkSeconds){
					$pDuration = TextFormat::WHITE . $pLang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
					$pDurationStr = TextFormat::WHITE . ' ' . $pDuration . ': ' . $this->getDuration();
					$player->updateLineOfScoreboard(1, $pDurationStr);
					// Add a maximum duel duration if necessary.
				}
			}

			foreach($p2Players as $player){
				$pLang = $player->getLanguageInfo()->getLanguage();
				$pPos = $player->getPosition();
				$pY = $pPos->getY();
				if($this->event->getType() === MineceitEvent::TYPE_SUMO){
					if($pY < $centerMinY){
						$this->setEliminatedPlayer($player, $this->team2);
					}
				}

				// Used for updating scoreboards.
				if($checkSeconds){
					$pDuration = TextFormat::WHITE . $pLang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
					$pDurationStr = TextFormat::WHITE . ' ' . $pDuration . ': ' . $this->getDuration();
					$player->updateLineOfScoreboard(1, $pDurationStr);
					// Add a maximum duel duration if necessary.
				}
			}

			if($checkSeconds){
				$this->durationSeconds++;
			}
		}elseif($this->status === self::STATUS_ENDING){

			foreach($allPlayers as $player){
				$player->getKitHolder()->clearKit();
				$player->reset(true, false);
				$arena = $this->event->getArena();
				$arena->teleportPlayer($player);
				$player->getScoreboardInfo()
					->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
				$player->setNormalNameTag();
			}

			$this->status = self::STATUS_ENDED;
		}
	}

	/**
	 * Sets the players in a duel/
	 */
	protected function setPartiesInDuel() : void{

		$arena = $this->event->getArena();

		$p1Players = $this->team1->getPlayers();
		$p2Players = $this->team2->getPlayers();

		foreach($p1Players as $player){
			$player->setGamemode(0);
			$player->setPlayerFlying(false);
			$player->setImmobile();
			$player->clearInventory();
			$arena->teleportPlayer($player, EventArena::P1);
			$p1Lang = $player->getLanguageInfo()->getLanguage();
			$p1Message = $p1Lang->getMessage(Language::EVENTS_MESSAGE_DUELS_MATCHED, ["name" => $this->p2Name]);
			$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $p1Message);
		}

		foreach($p2Players as $player){
			$player->setGamemode(0);
			$player->setPlayerFlying(false);
			$player->setImmobile();
			$player->clearInventory();
			$arena->teleportPlayer($player, EventArena::P1);
			$p2Lang = $player->getLanguageInfo()->getLanguage();
			$p2Message = $p2Lang->getMessage(Language::EVENTS_MESSAGE_DUELS_MATCHED, ["name" => $this->p1Name]);
			$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $p2Message);
		}
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
		$color = MineceitUtil::getThemeColor();
		$message = TextFormat::BOLD . $color . "$countdown...";
		return $message;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param MineceitParty  $party
	 *
	 * @return bool
	 */
	public function setEliminatedPlayer(MineceitPlayer $player, MineceitParty $party) : bool{
		// TODO
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

		if($seconds < 10){
			$secStr = '0' . $seconds;
		}
		if($minutes < 10){
			$minStr = '0' . $minutes;
		}
		return str_replace('%min%', $minStr, str_replace('%sec%', $secStr, $result));
	}

	/**
	 *
	 * @param MineceitParty $party
	 *
	 * @return MineceitParty|null
	 */
	public function getOpponent(MineceitParty $party){
		if($this->p1->equalsParty($party)){
			return $this->p2;
		}elseif($this->p2->equalsParty($party)){
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
	 * @param MineceitParty|null $winner
	 */
	public function setResults(MineceitParty $winner = null) : void{

		if($winner !== null){

			$name = $winner->getName();

			if($this->isParty($winner)){
				$loser = $name === $this->p1Name ? $this->p2Name : $this->p1Name;
				$this->winner = $name;
				$this->loser = $loser;
			}
		}

		$this->status = self::STATUS_ENDING;
	}

	/**
	 * @param MineceitParty $party
	 *
	 * @return bool
	 */
	public function isParty(MineceitParty $party) : bool{
		return $this->p1->equalsParty($party) || $this->p2->equalsParty($party);
	}
}