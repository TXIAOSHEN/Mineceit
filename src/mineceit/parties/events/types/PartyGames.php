<?php

declare(strict_types=1);

namespace mineceit\parties\events\types;

use mineceit\arenas\GamesArena;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\parties\events\PartyEvent;
use mineceit\parties\events\types\match\data\MineceitTeam;
use mineceit\parties\MineceitParty;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PartyGames extends PartyEvent{

	private const MAX_DURATION_SECONDS = 60 * 10;

	/* @var int */
	private $currentTick;

	/* @var int */
	private $playersPerTeam;

	/* @var MineceitParty */
	private $party;

	/* @var MineceitPlayer[]|MineceitTeam[] */
	private $participants;

	/* @var MineceitPlayer[] */
	private $players;

	/* @var int */
	private $worldId;

	/* @var AbstractKit */
	private $kit;

	/* @var Level */
	private $level;

	/* @var int */
	private $durationSeconds;

	/* @var int */
	private $countdownSeconds;

	/* @var bool */
	private $started;

	/* @var bool */
	private $ended;

	/* @var string|null */
	private $winner;

	/* @var MineceitPlayer[]|array */
	private $spectators;

	/* @var Server */
	private $server;

	/* @var int */
	private $currentTicks;

	/* @var int|null */
	private $endTick;

	/* @var Position|null */
	private $centerPosition;

	/* @var GamesArena|null */
	private $arena;

	public function __construct(int $worldId, MineceitParty $party, int $playersPerTeam, GamesArena $arena){
		parent::__construct(self::EVENT_GAMES);
		$this->playersPerTeam = $playersPerTeam;
		$this->party = $party;
		$this->worldId = $worldId;
		$this->participants = [];
		$this->spectators = [];

		$this->countdownSeconds = 5;
		$this->durationSeconds = 0;
		$this->currentTicks = 0;

		$this->arena = $arena;
		$this->server = Server::getInstance();
		$this->level = $this->server->getLevelByName("party$worldId");

		$this->started = false;
		$this->ended = false;

		$this->kit = $arena->getKit();

		$this->players = $party->getPlayers();

		shuffle($this->players);

		$count = 0;

		$size = count($this->players);

		$colors = [];

		if($playersPerTeam > 1){

			$team = new MineceitTeam();

			foreach($this->players as $player){

				$count++;
				$team->addToTeam($player);

				if($count % $this->playersPerTeam === 0 || $count === $size){
					$this->participants[] = $team;
					$colors[] = $team->getTeamColor();
					$team = new MineceitTeam($colors);
				}
			}
		}else $this->participants = $this->players;
	}

	/**
	 * Updates the party event each tick.
	 */
	public function update() : void{
		$this->currentTicks++;

		$checkSeconds = $this->currentTicks % 20 === 0;

		if($this->currentTicks > 5 && !$this->hasEnded() && count($this->participants) <= 1){
			if(count($this->participants) === 0){
				$this->setEnded();
				return;
			}
			foreach($this->participants as $key => $winner){
				$this->setEnded($winner);
				unset($this->participants[$key]);
				return;
			}
		}

		if($this->isCountingDown()){

			if($this->currentTicks === 5) $this->setInDuel();

			if($checkSeconds){

				$participants = $this->participants;
				foreach($participants as $p){
					if($p instanceof MineceitTeam){
						$players = $p->getPlayers();
						foreach($players as $player){
							$Sb = $player->getScoreboardInfo();
							if(
								$Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE &&
								$Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_EVENT_DUEL
							)
								$Sb->setScoreboard(Scoreboard::SCOREBOARD_EVENT_DUEL);
						}
					}elseif($p instanceof MineceitPlayer){
						$Sb = $p->getScoreboardInfo();
						if(
							$Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE &&
							$Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_EVENT_DUEL
						)
							$Sb->setScoreboard(Scoreboard::SCOREBOARD_EVENT_DUEL);
					}
				}

				if($this->countdownSeconds === 5){

					foreach($participants as $p){
						if($p instanceof MineceitTeam){
							$players = $p->getPlayers();
							foreach($players as $player){
								$lang = $player->getLanguageInfo()->getLanguage();
								$msg = $this->getCountdownMessage(true, $lang, $this->countdownSeconds);
								$player->sendTitle($msg, '', 5, 20, 5);
							}
						}elseif($p instanceof MineceitPlayer){
							$lang = $p->getLanguageInfo()->getLanguage();
							$msg = $this->getCountdownMessage(true, $lang, $this->countdownSeconds);
							$p->sendTitle($msg, '', 5, 20, 5);
						}
					}
				}elseif($this->countdownSeconds !== 0){

					foreach($participants as $p){
						if($p instanceof MineceitTeam){
							$players = $p->getPlayers();
							foreach($players as $player){
								$lang = $player->getLanguageInfo()->getLanguage();
								$msg = $this->getJustCountdown($lang, $this->countdownSeconds);
								$player->sendTitle($msg, '', 5, 20, 5);
							}
						}elseif($p instanceof MineceitPlayer){
							$lang = $p->getLanguageInfo()->getLanguage();
							$msg = $this->getJustCountdown($lang, $this->countdownSeconds);
							$p->sendTitle($msg, '', 5, 20, 5);
						}
					}
				}else{
					foreach($participants as $p){
						if($p instanceof MineceitTeam){
							$players = $p->getPlayers();
							foreach($players as $player){
								$msg = $player->getLanguageInfo()->getLanguage()
									->generalMessage(Language::DUELS_MESSAGE_STARTING);
								$player->sendTitle($msg, '', 5, 20, 5);
							}
						}elseif($p instanceof MineceitPlayer){
							$msg = $p->getLanguageInfo()->getLanguage()
								->generalMessage(Language::DUELS_MESSAGE_STARTING);
							$p->sendTitle($msg, '', 5, 20, 5);
						}
					}
				}

				if($this->countdownSeconds <= 0){
					$this->started = true;

					foreach($participants as $p){
						if($p instanceof MineceitTeam){
							$players = $p->getPlayers();
							foreach($players as $player){
								$player->setImmobile(false);
								$player->setCombatNameTag();
							}
						}elseif($p instanceof MineceitPlayer){
							$p->setImmobile(false);
							$p->setCombatNameTag();
						}
					}
				}

				$this->countdownSeconds--;
			}
		}elseif($this->isRunning()){

			if($this->getKit() === 'Knock'){
				foreach($this->participants as $p){
					if($p instanceof MineceitTeam){
						$players = $p->getPlayers();
						foreach($players as $player){
							if($player->getFloorY() <= 0){
								$this->addSpectator($player);
							}
						}
					}elseif($p instanceof MineceitPlayer && $p->getFloorY() <= 0){
						$this->addSpectator($p);
					}
				}
			}elseif($this->getKit() === 'Build'){
				foreach($this->participants as $p){
					if($p instanceof MineceitTeam){
						$players = $p->getPlayers();
						foreach($players as $player){
							if($player->getFloorY() <= 57 || $player->getFloorY() >= 87){
								$this->addSpectator($player);
							}
						}
					}elseif($p instanceof MineceitPlayer && ($p->getFloorY() <= 57 || $p->getFloorY() >= 87)){
						$this->addSpectator($p);
					}
				}
			}elseif($this->getKit() === 'Sumo'){
				foreach($this->participants as $p){
					if($p instanceof MineceitTeam){
						$players = $p->getPlayers();
						foreach($players as $player){
							if($player->getFloorY() <= 50){
								$this->addSpectator($player);
							}
						}
					}elseif($p instanceof MineceitPlayer && $p->getFloorY() <= 50){
						$this->addSpectator($p);
					}
				}
			}

			if($checkSeconds){

				foreach($this->participants as $p){
					if($p instanceof MineceitTeam){
						$players = $p->getPlayers();
						foreach($players as $player){
							$Duration = $player->getLanguageInfo()->getLanguage()
								->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
							$DurationStr = TextFormat::WHITE . ' ' . $Duration . ': ' . $this->getDuration();
							$player->getScoreboardInfo()->updateLineOfScoreboard(1, $DurationStr);
						}
					}elseif($p instanceof MineceitPlayer){
						$Duration = $p->getLanguageInfo()->getLanguage()
							->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
						$DurationStr = TextFormat::WHITE . ' ' . $Duration . ': ' . $this->getDuration();
						$p->getScoreboardInfo()->updateLineOfScoreboard(1, $DurationStr);
					}
				}

				foreach($this->spectators as $spec){

					if($spec->isOnline()){
						$specLang = $spec->getLanguageInfo()->getLanguage();
						$specDuration = TextFormat::WHITE . $specLang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
						$specDurationStr = TextFormat::WHITE . ' ' . $specDuration . ': ' . $this->getDuration();
						$spec->getScoreboardInfo()->updateLineOfScoreboard(1, $specDurationStr);
					}
				}

				if($this->durationSeconds >= self::MAX_DURATION_SECONDS){
					$this->setEnded();
					return;
				}

				$this->durationSeconds++;
			}
		}elseif($this->hasEnded()){
			$diff = $this->currentTicks - $this->endTick;
			if($diff >= 30){
				$this->endDuel();
				return;
			}
		}
	}

	/**
	 * @return bool
	 */
	public function hasEnded() : bool{
		return $this->ended;
	}

	/**
	 * @param MineceitParty|null $winner
	 *
	 * Sets the duel as ended.
	 */
	public function setEnded($winner = null) : void{
		$this->winner = null;
		if($winner instanceof MineceitTeam){
			$this->winner = $winner;
		}elseif($winner instanceof MineceitPlayer){
			$this->winner = $winner;
		}
		$this->ended = true;
		$this->endTick = $this->currentTicks;
	}

	/**
	 * @return bool
	 */
	public function isCountingDown() : bool{
		return !$this->started && !$this->ended;
	}

	/**
	 * Sets the players in the duel.
	 */
	private function setInDuel() : void{
		$exSpawns = [];
		$spawn = $this->arena->randomSpawnExclude($exSpawns);
		$participants = $this->participants;
		foreach($participants as $p){
			if($p instanceof MineceitTeam){
				$players = $p->getPlayers();
				foreach($players as $player){
					$this->setPlayerInDuel($spawn, $player);
				}
				$exSpawns[] = $spawn;
				$spawn = $this->arena->randomSpawnExclude($exSpawns);
			}elseif($p instanceof MineceitPlayer){
				$this->setPlayerInDuel($spawn, $p);
				$exSpawns[] = $spawn;
				$spawn = $this->arena->randomSpawnExclude($exSpawns);
			}
		}
	}

	private function setPlayerInDuel(int $spawn, MineceitPlayer $player){
		if($player->isOnline()){
			$player->setGamemode(0);
			$player->getExtensions()->enableFlying(false);
			$player->setImmobile(true);
			$player->getExtensions()->clearAll();
			$this->arena->teleportPlayerByKey($player, $spawn, $this->level);
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
		$message = $lang->generalMessage(Language::GAMES_MESSAGE_COUNTDOWN);
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
	 * @return bool
	 */
	public function isRunning() : bool{
		return $this->started && !$this->ended;
	}

	/**
	 * @return string
	 */
	public function getKit() : string{
		return $this->kit->getName();
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function addSpectator(MineceitPlayer $player) : void{
		$name = $player->getDisplayName();
		$local = strtolower($name);
		if(!isset($this->spectators[$local])){

			$team = $this->getTeam($player);
			if($team instanceof MineceitTeam && $team->isInTeam($player)){
				$color = $team->getTeamColor();
				foreach($this->players as $p){
					if($p->isOnline()){
						$p->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET .
							$p->getLanguageInfo()->getLanguage()->generalMessage(
								Language::PARTIES_DUEL_ELIMINATED, ["name" => $color . $player->getDisplayName()]));
					}
				}
				$team->removeFromTeam($player);
				if(count($team->getPlayers()) === 0){
					foreach($this->participants as $key => $temp){
						if($temp === $team) unset($this->participants[$key]);
					}
				}
			}elseif($team === null){
				foreach($this->players as $p){
					if($p->isOnline()){
						$p->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET .
							$p->getLanguageInfo()->getLanguage()->generalMessage(
								Language::PARTIES_DUEL_ELIMINATED, ["name" => TextFormat::RED . $player->getDisplayName()]));
					}
				}
				foreach($this->participants as $key => $member){
					if($member->getName() === $player->getName()) unset($this->participants[$key]);
				}
			}
			$player->getExtensions()->clearAll();
			$this->spectators[$local] = $player;
			$player->getExtensions()->setFakeSpectator();
			$player->getScoreboardInfo()->setScoreboard(Scoreboard::SCOREBOARD_SPECTATOR);
		}
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return MineceitTeam|null
	 */
	public function getTeam($player) : ?MineceitTeam{
		if($this->playersPerTeam === 1) return null;
		foreach($this->participants as $team){
			if($team->isInTeam($player)) return $team;
		}
		return null;
	}

	/**
	 * @return string
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

	private function endDuel() : void{

		$this->ended = true;

		if($this->endTick === null) $this->endTick = $this->currentTicks;

		$itemHandler = MineceitCore::getItemHandler();

		if($this->party !== null){
			$members = $this->party->getPlayers();

			foreach($members as $player){
				if($player->isOnline()){
					$this->sendFinalMessage($player);
					$pSb = $player->getScoreboardInfo();
					if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
						$pSb->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
					}
					$player->setThrowPearl(true, false);
					$player->setEatGap(true, false);
					$player->setArrowCD(true, false);
					$player->reset(true, $player->isAlive());
					$player->isInParty() ? $itemHandler->spawnPartyItems($player) : $itemHandler->spawnHubItems($player);
					$player->setNormalNameTag();
				}
			}
		}

		$this->spectators = [];
		MineceitUtil::deleteLevel($this->level);
		MineceitCore::getPartyManager()->getEventManager()->removeDuel($this->worldId);
	}

	/**
	 * @param MineceitPlayer|null $playerToSendMessage
	 */
	public function sendFinalMessage(?MineceitPlayer $playerToSendMessage) : void{

		if($playerToSendMessage !== null && $playerToSendMessage->isOnline()){

			$lang = $playerToSendMessage->getLanguageInfo()->getLanguage();
			$none = $lang->generalMessage(Language::NONE);
			$winner = $this->winner ?? $none;

			if($winner instanceof MineceitTeam){
				$winnerMessage = $lang->generalMessage(Language::DUELS_MESSAGE_WINNER, ["name" => $winner->getTeamColor() . "Team"]);
			}elseif($winner instanceof MineceitPlayer){
				$winnerMessage = $lang->generalMessage(Language::DUELS_MESSAGE_WINNER, ["name" => $winner->getDisplayName()]);
			}else{
				$winnerMessage = $lang->generalMessage(Language::DUELS_MESSAGE_WINNER, ["name" => 'None']);
			}

			$separator = '--------------------------';
			$result = ['%', $winnerMessage, '%'];
			$keys = array_keys($result);

			foreach($keys as $key){
				$str = $result[$key];
				if($str === '%') $result[$key] = $separator;
			}

			foreach($result as $res){
				$playerToSendMessage->sendMessage($res);
			}
		}
	}

	/**
	 * @return bool
	 */
	public function cantDamagePlayers() : bool{
		return !$this->kit->getMiscKitInfo()->canDamagePlayers();
	}

	/**
	 * @return GamesArena
	 */
	public function getArena() : GamesArena{
		return $this->arena;
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return bool
	 */
	public function isSpectator($player) : bool{
		$name = $player instanceof MineceitPlayer ? $player->getDisplayName() : $player;
		$local = strtolower($name);
		return isset($this->spectators[$local]);
	}

	/**
	 * @return string
	 */
	public function getTexture() : string{
		return $this->kit !== null ? $this->kit
			->getMiscKitInfo()->getTexture() : '';
	}

	/**
	 * @return int
	 */
	public function getWorldId() : int{
		return $this->worldId;
	}

	/**
	 * @param MineceitParty|string $party
	 *
	 * @return bool
	 */
	public function isParty($party) : bool{
		$name = $party instanceof MineceitParty ? $party->getName() : $party;
		return $this->party->getName() === $name;
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function removeFromEvent(MineceitPlayer $player) : void{
		$team = $this->getTeam($player);
		if($team instanceof MineceitTeam && $team->isInTeam($player)){
			$color = $team->getTeamColor();
			foreach($this->players as $p){
				if($p->isOnline()) $p->sendMessage(MineceitUtil::getPrefix() . ' ' .
					TextFormat::RESET . $p->getLanguageInfo()->getLanguage()->generalMessage(
						Language::PARTIES_DUEL_ELIMINATED, ["name" => $color . $player->getDisplayName()]));
			}
			$team->removeFromTeam($player);
			if(count($team->getPlayers()) === 0){
				foreach($this->participants as $key => $temp){
					if($temp === $team) unset($this->participants[$key]);
				}
			}
		}elseif($team === null){
			foreach($this->players as $p){
				if($p->isOnline()){
					$p->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET .
						$p->getLanguageInfo()->getLanguage()->generalMessage(
							Language::PARTIES_DUEL_ELIMINATED, ["name" => TextFormat::RED . $player->getDisplayName()]));
				}
			}
			foreach($this->participants as $key => $member){
				if($member instanceof MineceitPlayer && $member->getName() === $player->getName()) unset($this->participants[$key]);
			}
		}
	}
}
