<?php

declare(strict_types=1);

namespace mineceit\parties\events\types;

use mineceit\arenas\DuelArena;
use mineceit\kits\KitsManager;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\parties\events\PartyEvent;
use mineceit\parties\events\types\match\data\MineceitTeam;
use mineceit\parties\MineceitParty;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PartyDuel extends PartyEvent{

	private const MAX_DURATION_SECONDS = 60 * 10;

	private const MLG_MAX_DURATION_SECONDS = 60 * 3;

	/* @var MineceitParty */
	private $party1;

	/* @var MineceitParty */
	private $party2;

	/* @var MineceitTeam */
	private $team1;

	/* @var MineceitTeam */
	private $team2;

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

	/* @var string|null */
	private $loser;

	/* @var MineceitPlayer[]|array */
	private $spectators;

	/* @var Server */
	private $server;

	/* @var int */
	private $currentTicks;

	/* @var int|null */
	private $endTick;

	/* @var string|null */
	private $queue;

	/* @var Position|null */
	private $centerPosition;

	/* @var DuelArena|null */
	private $arena;

	/* @var int[]|array */
	private $mlgscore;

	/* @var int */
	private $mlground;

	/* @var int[]|array */
	private $mlgblock;

	public function __construct(int $worldId, MineceitParty $p1, MineceitParty $p2, string $queue, DuelArena $arena){
		parent::__construct(self::EVENT_DUEL);
		$this->party1 = $p1;
		$this->party2 = $p2;
		$colors = [];
		$this->team1 = new MineceitTeam();
		if($this->team1 instanceof MineceitTeam)
			$colors[] = $this->team1->getTeamColor();
		$this->team2 = new MineceitTeam($colors);
		$this->arena = $arena;
		$this->worldId = $worldId;
		$this->kit = MineceitCore::getKits()->getKit($queue);
		$this->queue = $queue;
		$this->server = Server::getInstance();
		$this->level = $this->server->getLevelByName("party$worldId");
		$this->centerPosition = null;

		$this->started = false;
		$this->ended = false;
		$this->countdownSeconds = 5;
		$this->durationSeconds = 0;
		$this->currentTicks = 0;

		$this->mlgscore = [$this->party1->getName() => 0, $this->party2->getName() => 0];

		$this->mlground = 1;

		$this->mlgblock = [];

		$this->spectators = [];

		$this->endTick = null;

		$this->winner = null;
		$this->loser = null;
	}

	/**
	 * Updates the party event each tick.
	 */
	public function update() : void{
		$this->currentTicks++;

		$checkSeconds = $this->currentTicks % 20 === 0;

		if($this->currentTicks > 5 && !$this->hasEnded() && (count($this->team1->getPlayers()) === 0 || count($this->team2->getPlayers()) === 0)){

			if(count($this->team1->getPlayers()) === 0){
				$this->setEnded($this->party2);
			}elseif(count($this->team2->getPlayers()) === 0){
				$this->setEnded($this->party1);
			}
		}

		if($this->isCountingDown()){

			if($this->currentTicks === 5) $this->setInDuel();

			if($checkSeconds){

				$members1 = $this->team1->getPlayers();
				$members2 = $this->team2->getPlayers();

				foreach($members1 as $player){
					if($player->isOnline()){
						$Sb = $player->getScoreboardInfo();
						if($Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE && $Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_DUEL){
							$Sb->setScoreboard(Scoreboard::SCOREBOARD_DUEL);
						}
					}
				}

				foreach($members2 as $player){
					if($player->isOnline()){
						$Sb = $player->getScoreboardInfo();
						if($Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE && $Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_DUEL){
							$Sb->setScoreboard(Scoreboard::SCOREBOARD_DUEL);
						}
					}
				}

				if($this->countdownSeconds === 5){

					foreach($members1 as $player){
						if($player->isOnline()){
							$lang = $player->getLanguageInfo()->getLanguage();
							$msg = $this->getCountdownMessage(true, $lang, $this->countdownSeconds);
							$player->sendTitle($msg, '', 5, 20, 5);
						}
					}

					foreach($members2 as $player){
						if($player->isOnline()){
							$lang = $player->getLanguageInfo()->getLanguage();
							$msg = $this->getCountdownMessage(true, $lang, $this->countdownSeconds);
							$player->sendTitle($msg, '', 5, 20, 5);
						}
					}
				}elseif($this->countdownSeconds !== 0){

					foreach($members1 as $player){
						if($player->isOnline()){
							$lang = $player->getLanguageInfo()->getLanguage();
							$msg = $this->getJustCountdown($lang, $this->countdownSeconds);
							$player->sendTitle($msg, '', 5, 20, 5);
						}
					}

					foreach($members2 as $player){
						if($player->isOnline()){
							$lang = $player->getLanguageInfo()->getLanguage();
							$msg = $this->getJustCountdown($lang, $this->countdownSeconds);
							$player->sendTitle($msg, '', 5, 20, 5);
						}
					}
				}else{

					foreach($members1 as $player){
						if($player->isOnline()){
							$msg = $player->getLanguageInfo()->getLanguage()->generalMessage(Language::DUELS_MESSAGE_STARTING);
							$player->sendTitle($msg, '', 5, 20, 5);
						}
					}

					foreach($members2 as $player){
						if($player->isOnline()){
							$msg = $player->getLanguageInfo()->getLanguage()->generalMessage(Language::DUELS_MESSAGE_STARTING);
							$player->sendTitle($msg, '', 5, 20, 5);
						}
					}
				}

				if($this->countdownSeconds <= 0){
					$this->started = true;

					foreach($members1 as $player){
						if($player->isOnline()){
							$player->setImmobile(false);
							$player->setCombatNameTag();
						}
					}

					foreach($members2 as $player){
						if($player->isOnline()){
							$player->setImmobile(false);
							$player->setCombatNameTag();
						}
					}
				}

				$this->countdownSeconds--;
			}
		}elseif($this->isRunning()){
			$queue = strtolower($this->queue);

			if($queue === KitsManager::SUMO){

				$spawnPos = $this->arena->getP1SpawnPos();
				$minY = $spawnPos->getY() - 5;

				$members1 = $this->team1->getPlayers();
				$members2 = $this->team2->getPlayers();

				foreach($members1 as $member){
					if($member->isOnline()){
						$pos = $member->getPosition();
						$y = $pos->y;
						if($y < $minY){
							$this->addSpectator($member);
						}
					}
				}

				foreach($members2 as $member){
					if($member->isOnline()){
						$pos = $member->getPosition();
						$y = $pos->y;
						if($y < $minY){
							$this->addSpectator($member);
						}
					}
				}
			}elseif($queue === KitsManager::MLGRUSH){

				$spawnPos1 = $this->arena->getP1SpawnPos();
				$spawnPos2 = $this->arena->getP2SpawnPos();
				$minY = $spawnPos1->getY() - 15;

				$members1 = $this->team1->getPlayers();
				$members2 = $this->team2->getPlayers();

				foreach($members1 as $member){
					if($member->isOnline()){
						$member->sendPopup(TextFormat::BLUE . $this->party1->getName() . TextFormat::WHITE . " " . $this->mlgscore[$this->party1->getName()] . TextFormat::GRAY . "\n" . TextFormat::RED . $this->party2->getName() . TextFormat::WHITE . " " . $this->mlgscore[$this->party2->getName()]);
						$pos = $member->getPosition();
						$y = $pos->y;
						if($y < $minY){
							$p1Pos = new Position($spawnPos1->getX(), $spawnPos1->getY(), $spawnPos1->getZ(), $this->level);
							$member->teleport($p1Pos);
							$member->getKitHolder()->setKit($this->kit);
						}
					}
				}

				foreach($members2 as $member){
					if($member->isOnline()){
						$member->sendPopup(TextFormat::RED . $this->party2->getName() . TextFormat::WHITE . " " . $this->mlgscore[$this->party2->getName()] . TextFormat::GRAY . "\n" . TextFormat::BLUE . $this->party1->getName() . TextFormat::WHITE . " " . $this->mlgscore[$this->party1->getName()]);
						$pos = $member->getPosition();
						$y = $pos->y;
						if($y < $minY){
							$p2Pos = new Position($spawnPos2->getX(), $spawnPos2->getY(), $spawnPos2->getZ(), $this->level);
							$member->teleport($p2Pos);
							$member->getKitHolder()->setKit($this->kit);
						}
					}
				}
			}

			if($checkSeconds){

				$members1 = $this->team1->getPlayers();
				$members2 = $this->team2->getPlayers();

				foreach($members1 as $player){
					if($player->isOnline()){
						$Duration = $player->getLanguageInfo()->getLanguage()->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
						$DurationStr = TextFormat::WHITE . ' ' . $Duration . ': ' . $this->getDuration();
						$player->getScoreboardInfo()->updateLineOfScoreboard(1, $DurationStr);
					}
				}

				foreach($members2 as $player){
					if($player->isOnline()){
						$Duration = $player->getLanguageInfo()->getLanguage()->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
						$DurationStr = TextFormat::WHITE . ' ' . $Duration . ': ' . $this->getDuration();
						$player->getScoreboardInfo()->updateLineOfScoreboard(1, $DurationStr);
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

				if($queue === KitsManager::MLGRUSH && $this->durationSeconds >= self::MLG_MAX_DURATION_SECONDS){
					$this->mlground++;
					if($this->mlground !== 10){
						$this->clearBlock();
						foreach($members1 as $player){
							if($player->isOnline())
								$player->getScoreboardInfo()->setScoreboard(
									Scoreboard::SCOREBOARD_DUEL
								);
						}
						foreach($members2 as $player){
							if($player->isOnline())
								$player->getScoreboardInfo()->setScoreboard(
									Scoreboard::SCOREBOARD_DUEL
								);
						}
						$this->started = false;
						$this->ended = false;
						$this->countdownSeconds = 5;
						$this->durationSeconds = 0;
						$this->setInDuel();
						return;
					}else{
						$this->EndMLG();
						return;
					}
				}

				if($this->durationSeconds >= self::MAX_DURATION_SECONDS && $queue !== KitsManager::MLGRUSH){
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
	public function setEnded(MineceitParty $winner = null) : void{
		$this->winner = $winner;
		$loser = $this->getOpponent($winner->getName());
		$this->loser = $loser;

		$this->ended = true;
		$this->endTick = $this->currentTicks;
	}

	/**
	 * @param MineceitParty|string $party
	 *
	 * @return MineceitParty|null
	 */
	public function getOpponent($party) : ?MineceitParty{
		$result = null;
		$name = $party instanceof MineceitParty ? $party->getName() : $party;
		if($this->isParty($party)){
			if($name === $this->party1->getName())
				$result = $this->party2;
			else $result = $this->party1;
		}
		return $result;
	}

	/**
	 * @param MineceitParty|string $party
	 *
	 * @return bool
	 */
	public function isParty($party) : bool{
		$name = $party instanceof MineceitParty ? $party->getName() : $party;
		return $this->party1->getName() === $name || $this->party2->getName() === $name;
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
		$members1 = $this->party1->getPlayers();
		$members2 = $this->party2->getPlayers();

		$spawnPos = $this->arena->getP1SpawnPos();
		$x = $spawnPos->getX();
		$y = $spawnPos->getY();
		$z = $spawnPos->getZ();
		$level = $this->level;

		$p1Pos = new Position($x, $y, $z, $level);

		foreach($members1 as $player){
			if($player->isOnline()){
				$player->setGamemode(0);
				$player->getExtensions()->enableFlying(false);
				$player->setImmobile(true);

				$player->getExtensions()->clearAll();
				MineceitUtil::onChunkGenerated($level, $x >> 4, $z >> 4, function() use ($p1Pos, $player){
					$player->teleport($p1Pos);
				});
				$this->team1->addToTeam($player);
				$player->getKitHolder()->setKit($this->kit);
			}
		}

		$spawnPos = $this->arena->getP2SpawnPos();
		$x = $spawnPos->getX();
		$y = $spawnPos->getY();
		$z = $spawnPos->getZ();

		$p2Pos = new Position($x, $y, $z, $level);

		foreach($members2 as $player){
			if($player->isOnline()){
				$player->setGamemode(0);
				$player->getExtensions()->enableFlying(false);
				$player->setImmobile(true);

				$player->getExtensions()->clearAll();
				MineceitUtil::onChunkGenerated($level, $x >> 4, $z >> 4, function() use ($p2Pos, $player){
					$player->teleport($p2Pos);
				});
				$this->team2->addToTeam($player);
				$player->getKitHolder()->setKit($this->kit);
			}
		}

		foreach($members1 as $player){
			if($player->isOnline()){
				$plevel = $player->getLevel();
				if($plevel->getName() !== $level->getName())
					$player->teleport($p1Pos);
			}
		}

		foreach($members2 as $player){
			if($player->isOnline()){
				$plevel = $player->getLevel();
				if($plevel->getName() !== $level->getName())
					$player->teleport($p2Pos);
			}
		}

		$p2x = $p2Pos->x;
		$p2z = $p2Pos->z;

		$p1x = $p1Pos->x;
		$p1z = $p1Pos->z;

		$this->centerPosition = new Position(intval((($p2x + $p1x) / 2)), intval($p1Pos->y), intval((($p2z + $p1z) / 2)), $this->level);
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
	 * @return bool
	 */
	public function isRunning() : bool{
		return $this->started && !$this->ended;
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function addSpectator(MineceitPlayer $player) : void{
		$name = $player->getDisplayName();
		$local = strtolower($name);
		$team = $this->getTeam($player);
		if($team instanceof MineceitTeam && $team->isInTeam($player)){
			$color = $team->getTeamColor();
			$members1 = $this->party1->getPlayers();
			$members2 = $this->party2->getPlayers();
			foreach($members1 as $member){
				if($member->isOnline()){
					$member->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member->getLanguageInfo()
							->getLanguage()->generalMessage(Language::PARTIES_DUEL_ELIMINATED, ["name" => $color . $player->getDisplayName()]));
				}
			}
			foreach($members2 as $member){
				if($member->isOnline()){
					$member->sendMessage(MineceitUtil::getPrefix() . ' ' .
						TextFormat::RESET . $member->getLanguageInfo()->getLanguage()->generalMessage(
							Language::PARTIES_DUEL_ELIMINATED, ["name" => $color . $player->getDisplayName()]));
				}
			}
			$team->removeFromTeam($player);
		}
		$player->getExtensions()->clearAll();
		$this->spectators[$local] = $player;
		$player->getExtensions()->setFakeSpectator();
		$player->getScoreboardInfo()->setScoreboard(
			Scoreboard::SCOREBOARD_SPECTATOR
		);
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return MineceitTeam|null
	 */
	public function getTeam($player) : ?MineceitTeam{
		$result = null;
		if($this->team1->isInTeam($player)){
			$result = $this->team1;
		}elseif($this->team2->isInTeam($player)){
			$result = $this->team2;
		}
		return $result;
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

	/**
	 *
	 * Tracks when a block is set.
	 */
	public function clearBlock() : void{
		foreach($this->mlgblock as $deleteblock){
			$pos = explode(':', $deleteblock);
			$this->level->setBlock(new Vector3($pos[0], $pos[1], $pos[2]), Block::get(Block::AIR));
		}

		$this->mlgblock = [];
	}

	public function EndMLG() : void{
		if($this->mlgscore[$this->party1->getName()] > $this->mlgscore[$this->party2->getName()]){
			$this->setEnded($this->party1);
		}elseif($this->mlgscore[$this->party2->getName()] > $this->mlgscore[$this->party1->getName()]){
			$this->setEnded($this->party2);
		}elseif($this->mlgscore[$this->party1->getName()] == $this->mlgscore[$this->party2->getName()]){
			$this->setEnded();
		}
	}

	private function endDuel() : void{

		$this->ended = true;

		if($this->endTick === null) $this->endTick = $this->currentTicks;

		$itemHandler = MineceitCore::getItemHandler();

		$members1 = $this->party1->getPlayers();
		$members2 = $this->party2->getPlayers();

		foreach($members1 as $player){
			if($player->isOnline()){

				$this->sendFinalMessage($player);
				$pSb = $player->getScoreboardInfo();
				if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
					$pSb->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
				}
				$player->reset(true, $player->isAlive());
				$player->isInParty() ? $itemHandler->spawnPartyItems($player) : $itemHandler->spawnHubItems($player);
				$player->setNormalNameTag();
			}
		}

		foreach($members2 as $player){
			if($player->isOnline()){

				$this->sendFinalMessage($player);
				$pSb = $player->getScoreboardInfo();
				if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
					$pSb->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
				}
				$player->reset(true, $player->isAlive());
				$player->isInParty() ? $itemHandler->spawnPartyItems($player) : $itemHandler->spawnHubItems($player);
				$player->setNormalNameTag();
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
			$loser = $this->loser ?? $none;

			$winnerMessage = $lang->generalMessage(Language::DUELS_MESSAGE_WINNER, ["name" => $winner->getName()]);
			$loserMessage = $lang->generalMessage(Language::DUELS_MESSAGE_LOSER, ["name" => $loser->getName()]);

			$separator = '--------------------------';

			$result = ['%', $winnerMessage, $loserMessage, '%'];

			$keys = array_keys($result);

			foreach($keys as $key){
				$str = $result[$key];
				if($str === '%') $result[$key] = $separator;
			}

			foreach($result as $res)
				$playerToSendMessage->sendMessage($res);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Block          $block
	 * @param bool           $break
	 *
	 * @return bool
	 */
	public function canPlaceBlock(MineceitPlayer $player, Block $block, bool $break = false) : bool{

		if(($team = $this->getTeam($player)) instanceof MineceitTeam){
			$queue = strtolower($this->queue);

			if(!$this->isRunning() || ($queue !== KitsManager::MLGRUSH && $queue !== KitsManager::BUILDUHC))
				return false;

			$blocks = [
				Block::COBBLESTONE => true,
				Block::WOODEN_PLANKS => true,
				Block::STONE => true,
			];

			if($queue === KitsManager::MLGRUSH){
				if($block->getId() === Block::BED_BLOCK){
					if($team === $this->team2){
						if(in_array($block->getDamage(), [2, 3, 10, 11])){
							$lang = $player->getLanguageInfo()->getLanguage();
							$msg = $lang->generalMessage(Language::BREAK_UR_WOOL);
							$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
						}else{
							$this->mlgscore[$this->party2->getName()]++;
							$this->mlground++;
							if($this->mlgscore[$this->party2->getName()] !== 5){
								$this->clearBlock();
								$members1 = $this->team1->getPlayers();
								$members2 = $this->team2->getPlayers();
								foreach($members1 as $player){
									if($player->isOnline()){
										$player->getScoreboardInfo()->setScoreboard(
											Scoreboard::SCOREBOARD_DUEL
										);
									}
								}
								foreach($members2 as $player){
									if($player->isOnline()){
										$player->getScoreboardInfo()->setScoreboard(
											Scoreboard::SCOREBOARD_DUEL
										);
									}
								}
								$this->started = false;
								$this->ended = false;
								$this->countdownSeconds = 5;
								$this->durationSeconds = 0;
								$this->setInDuel();
							}else{
								$this->setEnded($this->party2);
							}
						}
					}else{
						if(in_array($block->getDamage(), [0, 1, 8, 9])){
							$lang = $player->getLanguageInfo()->getLanguage();
							$msg = $lang->generalMessage(Language::BREAK_UR_WOOL);
							$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
						}else{
							$this->mlgscore[$this->party1->getName()]++;
							$this->mlground++;
							if($this->mlgscore[$this->party1->getName()] !== 5){
								$this->clearBlock();
								$members1 = $this->team1->getPlayers();
								$members2 = $this->team2->getPlayers();
								foreach($members1 as $player){
									if($player->isOnline()){
										$player->getScoreboardInfo()->setScoreboard(
											Scoreboard::SCOREBOARD_DUEL
										);
									}
								}
								foreach($members2 as $player){
									if($player->isOnline()){
										$player->getScoreboardInfo()->setScoreboard(
											Scoreboard::SCOREBOARD_DUEL
										);
									}
								}
								$this->started = false;
								$this->ended = false;
								$this->countdownSeconds = 5;
								$this->durationSeconds = 0;
								$this->setInDuel();
							}else{
								$this->setEnded($this->party1);
							}
						}
					}
				}elseif($block->getId() === Block::SANDSTONE){
					if(!$break){
						$x = $block->x;
						$y = $block->y;
						$z = $block->z;
						$this->mlgblock[] = $x . ':' . $y . ':' . $z;
					}
					return true;
				}else{
					return false;
				}
			}

			return isset($blocks[$block->getId()]);
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function cantDamagePlayers() : bool{
		return !$this->kit->getMiscKitInfo()->canDamagePlayers();
	}

	/**
	 * @return string
	 */
	public function getQueue() : string{
		return $this->queue;
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
	 * @param MineceitPlayer $player
	 */
	public function removeFromEvent(MineceitPlayer $player) : void{
		$team = $this->getTeam($player);
		if($team instanceof MineceitTeam && $team->isInTeam($player)){
			$color = $team->getTeamColor();
			$members1 = $this->party1->getPlayers();
			$members2 = $this->party2->getPlayers();
			foreach($members1 as $member){
				if($member->isOnline()) $member->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member->getLanguageInfo()->getLanguage()->generalMessage(Language::PARTIES_DUEL_ELIMINATED, ["name" => $color . $player->getDisplayName()]));
			}
			foreach($members2 as $member){
				if($member->isOnline()) $member->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member->getLanguageInfo()->getLanguage()->generalMessage(Language::PARTIES_DUEL_ELIMINATED, ["name" => $color . $player->getDisplayName()]));
			}
			$team->removeFromTeam($player);
		}
	}
}
