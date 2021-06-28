<?php

declare(strict_types=1);

namespace mineceit\duels\groups;

use mineceit\arenas\DuelArena;
use mineceit\kits\DefaultKit;
use mineceit\kits\KitsManager;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\info\ClientInfo;
use mineceit\player\info\duels\DuelInfo;
use mineceit\player\info\duels\duelreplay\data\PlayerReplayData;
use mineceit\player\info\duels\duelreplay\data\ReplayData;
use mineceit\player\info\duels\duelreplay\data\WorldReplayData;
use mineceit\player\info\duels\duelreplay\info\DuelReplayInfo;
use mineceit\player\info\stats\StatsInfo;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use mineceit\utils\Math;
use pocketmine\block\Block;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\item\ProjectileItem;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MineceitDuel{

	private const MAX_DURATION_SECONDS = 60 * 10;

	private const MLG_MAX_DURATION_SECONDS = 60 * 3;

	private const BOXING_DURATION = 90;

	/* @var MineceitPlayer */
	private $player1;

	/* @var MineceitPlayer */
	private $player2;

	/* @var int */
	private $worldId;

	/* @var DefaultKit */
	private $kit;

	/* @var bool */
	private $ranked;

	/* @var Level */
	private $level;

	/* @var int */
	private $durationSeconds;

	/* @var int */
	private $countdownSeconds;

	/* @var bool */
	private $started;

	/* @var bool */
	private $ended, $sentReplays;

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

	private $queue;

	/* @var int[]|array */
	private $numHits;

	/* @var int[]|array */
	private $mlgscore;

	/* @var int */
	private $mlground;

	/* @var Block[]|array */
	private $mlgblock;

	/* @var int[]|array */
	private $boxingscore;

	/* @var int[]|array */
	private $boxingcombo;

	/* @var int[]|array */
	private $boxingComboScore;

	/* @var Position|null */
	private $centerPosition;

	/* @var DuelArena */
	private $arena;

	/* @var array|ReplayData[]
	 * The data stored for replay.
	 */
	private $replayData;

	/** @var int */
	private $player1Elo, $player2Elo;
	/** @var ClientInfo|null */
	private $player1ClientInfo, $player2ClientInfo;

	/** @var string */
	private $player1Name, $player2Name, $player1DisplayName, $player2DisplayName;

	public function __construct(int $worldId, MineceitPlayer $p1, MineceitPlayer $p2, string $queue, bool $ranked, DuelArena $arena){
		$this->player1 = $p1;
		$this->player2 = $p2;
		$this->player1Name = $p1->getName();
		$this->player2Name = $p2->getName();
		$this->player1DisplayName = $p1->getDisplayName();
		$this->player2DisplayName = $p2->getDisplayName();
		$this->arena = $arena;
		$this->worldId = $worldId;
		$this->kit = MineceitCore::getKits()->getKit($queue);
		$this->queue = $queue;
		$this->ranked = $ranked;
		$this->server = Server::getInstance();
		$this->level = $this->server->getLevelByName("duel$worldId");
		$this->centerPosition = null;
		$this->player1Elo = $p1->getEloInfo()->getEloFromKit($this->queue);
		$this->player2Elo = $p2->getEloInfo()->getEloFromKit($this->queue);

		$this->player1ClientInfo = $p1->getClientInfo();
		$this->player2ClientInfo = $p2->getClientInfo();

		$this->started = false;
		$this->ended = false;
		$this->countdownSeconds = 5;
		$this->durationSeconds = 0;
		$this->currentTicks = 0;
		$this->sentReplays = false;

		$this->endTick = null;
		$this->winner = null;
		$this->loser = null;

		$this->spectators = [];
		$this->numHits = [$this->player1Name => 0, $this->player2Name => 0];
		$this->mlgscore = [$this->player1Name => 0, $this->player2Name => 0];
		$this->mlground = 1;
		$this->mlgblock = [];
		$this->replayData = [];
		$this->boxingscore = [$this->player1Name => 0, $this->player2Name => 0];
		$this->boxingcombo = [$this->player1Name => 0, $this->player2Name => 0];
		$this->boxingComboScore = [[10, 7], [5, 3], [3, 2], [-1, 1]];

		if($this->kit->getMiscKitInfo()->isReplaysEnabled()){
			$this->replayData = ['world' => new WorldReplayData($arena, $ranked)];
		}
	}

	/**
	 * Updates the duel.
	 */
	public function update(){

		$this->currentTicks++;

		$checkSeconds = $this->currentTicks % 20 === 0;

		if(!$this->player1->isOnline() || !$this->player2->isOnline()){
			if($this->ended) $this->endDuel();
			return;
		}

		$p1Lang = $this->player1->getLanguageInfo()->getLanguage();
		$p2Lang = $this->player2->getLanguageInfo()->getLanguage();

		if($this->isCountingDown()){

			if($this->currentTicks === 5) $this->setInDuel();

			if($checkSeconds){

				$p1Sb = $this->player1->getScoreboardInfo();
				$p2Sb = $this->player2->getScoreboardInfo();

				if($this->countdownSeconds === 5){

					$p1Msg = $this->getCountdownMessage(true, $p1Lang, $this->countdownSeconds);
					$p2Msg = $this->getCountdownMessage(true, $p2Lang, $this->countdownSeconds);
					$this->player1->sendTitle($p1Msg, '', 5, 20, 5);
					$this->player2->sendTitle($p2Msg, '', 5, 20, 5);
				}elseif($this->countdownSeconds !== 0){

					$p1Msg = $this->getJustCountdown($p1Lang, $this->countdownSeconds);
					$p2Msg = $this->getJustCountdown($p2Lang, $this->countdownSeconds);
					$this->player1->sendTitle($p1Msg, '', 5, 20, 5);
					$this->player2->sendTitle($p2Msg, '', 5, 20, 5);
				}else{

					$p1Msg = $p1Lang->generalMessage(Language::DUELS_MESSAGE_STARTING);
					$p2Msg = $p2Lang->generalMessage(Language::DUELS_MESSAGE_STARTING);
					$this->player1->sendTitle($p1Msg, '', 5, 10, 5);
					$this->player2->sendTitle($p2Msg, '', 5, 10, 5);
				}

				if($p1Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE && $p1Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_DUEL){
					$p1Sb->setScoreboard(Scoreboard::SCOREBOARD_DUEL);
				}

				if($p2Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE && $p2Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_DUEL){
					$p2Sb->setScoreboard(Scoreboard::SCOREBOARD_DUEL);
				}

				if($this->countdownSeconds <= 0){
					$this->started = true;
					$this->player1->setImmobile(false);
					$this->player2->setImmobile(false);
					$this->player1->setCombatNameTag();
					$this->player2->setCombatNameTag();
				}

				$this->countdownSeconds--;
			}
		}elseif($this->isRunning()){

			$queue = strtolower($this->queue);

			if($queue === KitsManager::SUMO){

				$spawnPos = $this->arena->getP1SpawnPos();
				$minY = $spawnPos->getY() - 5;

				$p1Pos = $this->player1->getPosition();
				$p2Pos = $this->player2->getPosition();

				$p1Y = $p1Pos->y;
				$p2Y = $p2Pos->y;

				if($p1Y < $minY){
					$this->setEnded($this->player2);
					return;
				}

				if($p2Y < $minY){
					$this->setEnded($this->player1);
					return;
				}
			}elseif($queue === KitsManager::MLGRUSH){

				$this->player1->sendPopup(TextFormat::BLUE . $this->player1DisplayName . TextFormat::WHITE . " " . $this->mlgscore[$this->player1Name] . TextFormat::GRAY . " | " . TextFormat::RED . $this->player2DisplayName . TextFormat::WHITE . " " . $this->mlgscore[$this->player2Name]);
				$this->player2->sendPopup(TextFormat::RED . $this->player2DisplayName . TextFormat::WHITE . " " . $this->mlgscore[$this->player2Name] . TextFormat::GRAY . " | " . TextFormat::BLUE . $this->player1DisplayName . TextFormat::WHITE . " " . $this->mlgscore[$this->player1Name]);

				$spawnPos = $this->arena->getP1SpawnPos();
				$minY = $spawnPos->getY() - 15;

				$p1Pos = $this->player1->getPosition();
				$p2Pos = $this->player2->getPosition();

				$p1Y = $p1Pos->y;
				$p2Y = $p2Pos->y;

				if($p1Y < $minY){
					$p1Pos = new Position($spawnPos->getX(), $spawnPos->getY(), $spawnPos->getZ(), $this->level);
					$this->player1->teleport($p1Pos);
					$this->player1->getKitHolder()->setKit($this->kit);
				}

				if($p2Y < $minY){
					$spawnPos = $this->arena->getP2SpawnPos();
					$p2Pos = new Position($spawnPos->getX(), $spawnPos->getY(), $spawnPos->getZ(), $this->level);
					$this->player2->teleport($p2Pos);
					$this->player2->getKitHolder()->setKit($this->kit);
				}
			}

			if($checkSeconds){

				$p1Duration = TextFormat::WHITE . $p1Lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
				$p2Duration = TextFormat::WHITE . $p2Lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);

				$p1DurationStr = ' ' . $p1Duration . ': ' . TextFormat::LIGHT_PURPLE . $this->getDuration();
				$p2DurationStr = ' ' . $p2Duration . ': ' . TextFormat::LIGHT_PURPLE . $this->getDuration();

				$this->player1->getScoreboardInfo()->updateLineOfScoreboard(
					1,
					$p1DurationStr
				);
				$this->player2->getScoreboardInfo()->updateLineOfScoreboard(
					1,
					$p2DurationStr
				);

				foreach($this->spectators as $spec){

					if($spec->isOnline()){
						$specLang = $spec->getLanguageInfo()->getLanguage();
						$specDuration = TextFormat::WHITE . $specLang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
						$specDurationStr = ' ' . $specDuration . ': ' . TextFormat::LIGHT_PURPLE . $this->getDuration();
						$spec->getScoreboardInfo()->updateLineOfScoreboard(
							1,
							$specDurationStr
						);
					}
				}

				$queue = strtolower($this->queue);

				if($queue === KitsManager::BOXING && $this->durationSeconds >= self::BOXING_DURATION){
					$this->EndBoxing();
					return;
				}

				if($queue === KitsManager::MLGRUSH && $this->durationSeconds >= self::MLG_MAX_DURATION_SECONDS){
					$this->mlground++;
					if($this->mlground !== 10){
						$this->clearBlock();
						$this->player1->getScoreboardInfo()->setScoreboard(
							Scoreboard::SCOREBOARD_DUEL
						);
						$this->player2->getScoreboardInfo()->setScoreboard(
							Scoreboard::SCOREBOARD_DUEL
						);
						$this->started = false;
						$this->ended = false;
						$this->countdownSeconds = 5;
						$this->durationSeconds = 0;
						$this->setInDuel(true);
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

		$this->updateReplayData();
	}

	private function endDuel() : void{
		$this->ended = true;
		if(!$this->sentReplays){

			$this->sentReplays = true;
			if($this->endTick === null){
				$this->endTick = $this->currentTicks;
			}
			$itemHandler = MineceitCore::getItemHandler();
			$info = null;
			if(isset($this->replayData['player1'])
				&& isset($this->replayData['player2'])
				&& isset($this->replayData['world'])){
				$worldReplayData = $this->replayData['world'];
				$info = new DuelReplayInfo($this->endTick, $this->replayData['player1'],
					$this->replayData['player2'], $worldReplayData, $this->kit);
			}

			// The elo results calculated and generated.
			$fillerMessages = new \stdClass();
			if($this->isRanked()){
				$calculatedElo = new \stdClass();
				$this->calculateAndSetElo($calculatedElo);
				$this->generateMessages($fillerMessages, $calculatedElo);
			}else{
				$this->generateMessages($fillerMessages);
			}

			if($this->player1 !== null && $this->player1->isOnline()){
				if($info !== null){
					$this->player1->addReplayDataToDuelHistory($info);
				}

				$this->sendFinalMessage($this->player1, $fillerMessages);
				$p1Sb = $this->player1->getScoreboardInfo();
				if($p1Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
					$p1Sb->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
				}
				$this->player1->reset(true, $this->player1->isAlive());
				$itemHandler->spawnHubItems($this->player1);
				$this->player1->setNormalNameTag();
			}

			if($this->player2 !== null && $this->player2->isOnline()){

				if($info !== null){
					$this->player2->addReplayDataToDuelHistory($info);
				}

				$this->sendFinalMessage($this->player2, $fillerMessages);
				$p2Sb = $this->player2->getScoreboardInfo();
				if($p2Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
					$p2Sb->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
				}
				$this->player2->reset(true, $this->player2->isAlive());
				$itemHandler->spawnHubItems($this->player2);
				$this->player2->setNormalNameTag();
			}

			$clearInventory = false;
			$spectatorsCount = count($this->spectators);
			$spectatorsKeys = array_keys($this->spectators);
			for($key = $spectatorsCount - 1; $key >= 0; $key--){
				$spectator = $this->spectators[$spectatorsKeys[$key]];
				if($spectator !== null && $spectator->isOnline()){
					$this->sendFinalMessage($spectator, $fillerMessages);
					$specSB = $spectator->getScoreboardInfo();
					if($specSB->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
						$specSB->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
					}
					$spectator->reset(true, true);

					$itemHandler->spawnHubItems($spectator, $clearInventory);
					$clearInventory = true;

					$spectator->setNormalNameTag();

				}
			}
			$this->spectators = [];

			MineceitUtil::deleteLevel($this->level);
			MineceitCore::getDuelHandler()->removeDuel($this->worldId);
		}
	}

	/**
	 * @return bool
	 */
	public function isRanked() : bool{
		return $this->ranked;
	}

	/**
	 * Calculates the elo for the players.
	 *
	 * @param \stdClass $result
	 */
	private function calculateAndSetElo(\stdClass &$result) : void{
		if($this->winner === $this->player1DisplayName){
			$winnerStartElo = $this->player1Elo;
			$winnerInfo = $this->player1ClientInfo;
			$loserStartElo = $this->player2Elo;
			$loserInfo = $this->player2ClientInfo;
		}else{
			$winnerStartElo = $this->player2Elo;
			$winnerInfo = $this->player2ClientInfo;
			$loserStartElo = $this->player1Elo;
			$loserInfo = $this->player1ClientInfo;
		}

		$result = StatsInfo::calculateElo($winnerStartElo, $loserStartElo, $winnerInfo, $loserInfo);
		$loserElo = $loserStartElo + $result->winnerEloChange;
		$winnerElo = $winnerStartElo + $result->loserEloChange;
		$result->winnerElo = $winnerElo;
		$result->loserElo = $loserElo;
		MineceitCore::getPlayerHandler()->setElo(
			$winnerInfo, $loserInfo, $winnerElo, $loserElo, strtolower($this->queue));
	}

	/**
	 * @param \stdClass      $fillerMessages - The filler messages.
	 * @param \stdClass|null $eloResults - The elo results.
	 *
	 * Generates the messages that doesn't require a language for the post duel.
	 */
	private function generateMessages(\stdClass &$fillerMessages, ?\stdClass $eloResults = null) : void{
		// Generates the Elo Changes extension string.
		if($this->winner !== null && $this->loser !== null && $eloResults !== null){
			$winnerString = TextFormat::GRAY . "$this->winner" . TextFormat::GRAY . '(' . TextFormat::GREEN .
				'+' . $eloResults->winnerEloChange . TextFormat::GRAY . ')';
			$loserString = TextFormat::GRAY . "$this->loser" . TextFormat::GRAY . '(' . TextFormat::RED .
				'-' . $eloResults->loserEloChange . TextFormat::GRAY . ')';
			$fillerMessages->eloChangesExtension = $winnerString . TextFormat::RESET . TextFormat::DARK_GRAY . ', ' . $loserString;
		}

		// Generates the spectators extension string.
		if(($spectatorsSize = count($this->spectators)) > 0){
			$spectatorsString = "";
			$currentSpectatorIndex = 0;
			$loopedSpectatorsSize = Math::ceil($spectatorsSize, 3);

			foreach($this->spectators as $key => $spectator){
				// Ends the loop after 3 spectators.
				if($currentSpectatorIndex >= $loopedSpectatorsSize){
					break;
				}
				$comma = ($currentSpectatorIndex === ($loopedSpectatorsSize - 1))
					? '' : TextFormat::DARK_GRAY . ', ';
				$spectatorsString .= TextFormat::LIGHT_PURPLE . $spectator->getDisplayName() . $comma;
				$currentSpectatorIndex++;
			}

			if($spectatorsSize <= $loopedSpectatorsSize){
				return;
			}
			$subtractedSize = $spectatorsSize - $loopedSpectatorsSize;
			$spectatorsString .= TextFormat::GRAY . ' (' . TextFormat::LIGHT_PURPLE . "+{$subtractedSize}"
				. TextFormat::GRAY . ')';
			$fillerMessages->spectatorString = $spectatorsString;
		}
	}

	/**
	 * @param MineceitPlayer|null $playerToSendMessage
	 * @param \stdClass           $extensionMessages - The extension messages pregenerated.
	 */
	public function sendFinalMessage(?MineceitPlayer $playerToSendMessage, \stdClass $extensionMessages) : void{
		if($playerToSendMessage !== null && $playerToSendMessage->isOnline()){

			$lang = $playerToSendMessage->getLanguageInfo()->getLanguage();
			$none = $lang->generalMessage(Language::NONE);
			$winner = $this->winner ?? $none;
			$loser = $this->loser ?? $none;
			$winnerMessage = $lang->generalMessage(Language::DUELS_MESSAGE_WINNER, ["name" => $winner]);
			$loserMessage = $lang->generalMessage(Language::DUELS_MESSAGE_LOSER, ["name" => $loser]);
			$separator = '--------------------------';

			$result = [$separator, $winnerMessage, $loserMessage, $separator];

			if($this->ranked && isset($extensionMessages->eloChangesExtension)){
				$eloChange = $lang->generalMessage(
					Language::DUELS_MESSAGE_ELOCHANGES, ["num" => $extensionMessages->eloChangesExtension]);
				$result[] = $eloChange;
				$result[] = $separator;
			}

			// The Spectators extension methods.
			$spectatorsExtensionMessage = isset($extensionMessages->spectatorsExtension) ?
				$extensionMessages->spectatorsExtension : $none;
			$result[] = $lang->generalMessage(Language::DUELS_MESSAGE_SPECTATORS, [
				"list" => TextFormat::LIGHT_PURPLE . $spectatorsExtensionMessage
			]);

			$result[] = $separator;
			foreach($result as $res){
				$playerToSendMessage->sendMessage($res);
			}
		}
	}

	/**
	 * @return bool
	 */
	public function isCountingDown() : bool{
		return !$this->started && !$this->ended;
	}

	/**
	 * Sets the players in the duel.
	 *
	 * @param bool $mlg
	 */
	private function setInDuel(bool $mlg = false) : void{
		$this->player1->setGamemode(0);
		$this->player2->setGamemode(0);

		$this->player1->getExtensions()->enableFlying(false);
		$this->player2->getExtensions()->enableFlying(false);

		$this->player1->setImmobile(true);
		$this->player2->setImmobile(true);

		$this->player1->getExtensions()->clearAll();
		$this->player2->getExtensions()->clearAll();

		$level = $this->level;

		$spawnPos = $this->arena->getP1SpawnPos();
		$x = $spawnPos->getX();
		$y = $spawnPos->getY();
		$z = $spawnPos->getZ();

		$p1Pos = new Position($x, $y, $z, $level);

		MineceitUtil::onChunkGenerated($level, $x >> 4, $z >> 4, function() use ($p1Pos){
			$this->player1->teleport($p1Pos);
		});

		$spawnPos = $this->arena->getP2SpawnPos();
		$x = $spawnPos->getX();
		$y = $spawnPos->getY();
		$z = $spawnPos->getZ();

		$p2Pos = new Position($x, $y, $z, $level);

		$p2x = $p2Pos->x;
		$p2z = $p2Pos->z;

		$p1x = $p1Pos->x;
		$p1z = $p1Pos->z;

		$this->centerPosition = new Position(intval((($p2x + $p1x) / 2)), intval($p1Pos->y), intval((($p2z + $p1z) / 2)), $this->level);

		MineceitUtil::onChunkGenerated($level, $x >> 4, $z >> 4, function() use ($p2Pos){
			$this->player2->teleport($p2Pos);
		});

		$this->player1->getKitHolder()->setKit($this->kit);
		$this->player2->getKitHolder()->setKit($this->kit);

		$p1Level = $this->player1->getLevel();
		$p2Level = $this->player2->getLevel();

		if($p1Level->getName() !== $level->getName())
			$this->player1->teleport($p1Pos);

		if($p2Level->getName() !== $level->getName())
			$this->player2->teleport($p2Pos);

		if($this->kit->getMiscKitInfo()->isReplaysEnabled() && !$mlg){
			$this->replayData['player1'] = new PlayerReplayData($this->player1);
			$this->replayData['player2'] = new PlayerReplayData($this->player2);
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
		return TextFormat::BOLD . MineceitUtil::getThemeColor() . "$countdown...";
	}

	/**
	 * @return bool
	 */
	public function isRunning() : bool{
		return $this->started && !$this->ended;
	}

	/**
	 * @param MineceitPlayer|null $winner
	 * @param bool                $logDuelHistory
	 *
	 * Sets the duel as ended.
	 */
	public function setEnded(MineceitPlayer $winner = null, bool $logDuelHistory = true) : void{
		if(!$this->ended){
			$online = $this->player1 !== null && $this->player1->isOnline() && $this->player2 !== null && $this->player2->isOnline();

			if($winner !== null && $this->isPlayer($winner) && $logDuelHistory){
				$this->winner = $winner->getDisplayName();
				$loser = $this->getOpponent($winner->getName());
				$this->loser = $loser->getDisplayName();

				$this->setDeathTime($this->loser);

				$winnerInfo = new DuelInfo($winner, $this->queue, $this->ranked, $this->numHits[$winner->getName()]);
				$loserInfo = new DuelInfo($loser, $this->queue, $this->ranked, $this->numHits[$loser->getName()]);

				$winner->addToDuelHistory($winnerInfo, $loserInfo);
				$loser->addToDuelHistory($winnerInfo, $loserInfo);

			}elseif($winner === null && $online && $logDuelHistory){
				$p1Info = new DuelInfo($this->player1, $this->queue, $this->ranked, $this->numHits[$this->player1Name]);
				$p2Info = new DuelInfo($this->player2, $this->queue, $this->ranked, $this->numHits[$this->player2Name]);

				$this->player1->addToDuelHistory($p1Info, $p2Info, true);
				$this->player2->addToDuelHistory($p2Info, $p1Info, true);
			}
			$this->ended = true;
			$this->endTick = $this->currentTicks;
		}
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return bool
	 */
	public function isPlayer($player) : bool{
		$name = $player instanceof MineceitPlayer ? $player->getName() : $player;
		return $this->player1Name === $name || $this->player2Name === $name;
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return MineceitPlayer|null
	 */
	public function getOpponent($player) : ?MineceitPlayer{
		$name = $player instanceof MineceitPlayer ? $player->getName() : $player;
		if($this->isPlayer($player)){
			return $name === $this->player1Name
				? $this->player2 : $this->player1;
		}
		return null;
	}

	/**
	 * @param string $player
	 *
	 * Sets the death time for the player.
	 */
	private function setDeathTime(string $player) : void{

		$name = ($player === $this->player1Name) ? 'player1' : 'player2';

		if(isset($this->replayData[$name])){
			/* @var PlayerReplayData $replayData */
			$replayData = $this->replayData[$name];
			$replayData->setDeathTime($this->currentTicks);
		}
	}

	/**
	 * @return string
	 */
	public function getDuration() : string{
		$seconds = $this->durationSeconds % 60;
		$minutes = intval($this->durationSeconds / 60);
		$secStr = $seconds < 10 ? "0{$seconds}" : "{$seconds}";
		$minStr = $minutes < 10 ? "0{$minutes}" : "{$minutes}";
		return "{$minStr}:{$secStr}";
	}

	public function EndBoxing() : void{
		if($this->boxingscore[$this->player1Name] > $this->boxingscore[$this->player2Name]){
			$this->setEnded($this->player1);
		}elseif($this->boxingscore[$this->player2Name] > $this->boxingscore[$this->player1Name]){
			$this->setEnded($this->player2);
		}elseif($this->boxingscore[$this->player1Name] == $this->boxingscore[$this->player2Name]){
			$this->setEnded();
		}
	}

	/**
	 *
	 * Tracks when a block is set.
	 */
	public function clearBlock() : void{
		if(isset($this->replayData['world'])){

			/* @var WorldReplayData $replayData */
			$replayData = $this->replayData['world'];

			foreach($this->mlgblock as $deleteblock){
				$block = Block::get(Block::SANDSTONE);
				$pos = explode(':', $deleteblock);
				$block->x = $x = $pos[0];
				$block->y = $y = $pos[1];
				$block->z = $z = $pos[2];
				$replayData->setBlockAt($this->currentTicks, $block, true);
				$this->level->setBlock(new Vector3($x, $y, $z), Block::get(Block::AIR));
			}

			$this->mlgblock = [];
		}
	}

	public function EndMLG() : void{
		if($this->mlgscore[$this->player1Name] > $this->mlgscore[$this->player2Name]){
			$this->setEnded($this->player1);
		}elseif($this->mlgscore[$this->player2Name] > $this->mlgscore[$this->player1Name]){
			$this->setEnded($this->player2);
		}elseif($this->mlgscore[$this->player1Name] == $this->mlgscore[$this->player2Name]){
			$this->setEnded();
		}
	}

	/**
	 * @return bool
	 */
	public function hasEnded() : bool{
		return $this->ended;
	}

	/**
	 * Updates the replay data of the duel.
	 */
	private function updateReplayData() : void{

		if(isset($this->replayData['world'])){

			// /* @var WorldReplayData $replayData */
			// $replayData = $this->replayData['world'];
			// $replayData->update($this->currentTicks, $this->level);

			if($this->player1 !== null && $this->player2 !== null && $this->player1->isOnline() && $this->player2->isOnline()){

				/* @var PlayerReplayData|null $p1ReplayData */
				$p1ReplayData = isset($this->replayData['player1']) ? $this->replayData['player1'] : null;
				/* @var PlayerReplayData|null $p2ReplayData */
				$p2ReplayData = isset($this->replayData['player2']) ? $this->replayData['player2'] : null;

				$p1Inv = $this->player1->getInventory();
				$p2Inv = $this->player2->getInventory();

				$p1ArmorInv = $this->player1->getArmorInventory();
				$p2ArmorInv = $this->player2->getArmorInventory();

				if($p1ArmorInv !== null){

					$p1ArmorArr = $p1ArmorInv->getContents(true);
					$p1Armor = ['helmet' => $p1ArmorArr[0], 'chest' => $p1ArmorArr[1], 'pants' => $p1ArmorArr[2], 'boots' => $p1ArmorArr[3]];

					if($p1ReplayData !== null) $p1ReplayData->updateArmor($this->currentTicks, $p1Armor);
				}

				if($p2ArmorInv !== null){

					$p2ArmorArr = $p2ArmorInv->getContents(true);
					$p2Armor = ['helmet' => $p2ArmorArr[0], 'chest' => $p2ArmorArr[1], 'pants' => $p2ArmorArr[2], 'boots' => $p2ArmorArr[3]];
					if($p2ReplayData !== null) $p2ReplayData->updateArmor($this->currentTicks, $p2Armor);
				}

				$p1ItemInHand = $p1Inv->getItemInHand();
				$p2ItemInHand = $p2Inv->getItemInHand();

				if($p1ReplayData !== null){
					$p1ReplayData->setItemAt($this->currentTicks, $p1ItemInHand);
					$p1ReplayData->setPositionAt($this->currentTicks, $this->player1->asVector3());
					$p1ReplayData->setMotionAt($this->currentTicks, $this->player1->getMotion());
					$p1ReplayData->setRotationAt($this->currentTicks, ['yaw' => $this->player1->yaw, 'pitch' => $this->player1->pitch]);
					$p1ReplayData->setNameTagAt($this->currentTicks, $this->player1->getNameTag());
				}

				if($p2ReplayData !== null){
					$p2ReplayData->setItemAt($this->currentTicks, $p2ItemInHand);
					$p2ReplayData->setPositionAt($this->currentTicks, $this->player2->asVector3());
					$p2ReplayData->setMotionAt($this->currentTicks, $this->player2->getMotion());
					$p2ReplayData->setRotationAt($this->currentTicks, ['yaw' => $this->player2->yaw, 'pitch' => $this->player2->pitch]);
					$p2ReplayData->setNameTagAt($this->currentTicks, $this->player2->getNameTag());
				}
			}
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param float          $force
	 *
	 * Function used for replays.
	 */
	public function setReleaseBow(MineceitPlayer $player, float $force) : void{

		$name = ($player->equals($this->player1)) ? 'player1' : 'player2';

		if(isset($this->replayData[$name])){

			/* @var PlayerReplayData $replayData */
			$replayData = $this->replayData[$name];
			$replayData->setReleaseBowAt($this->currentTicks, $force);
		}
	}

	/**
	 * @param string $player
	 * @param int    $animation
	 *
	 * Function used for replays.
	 */
	public function setAnimationFor(string $player, int $animation) : void{

		$name = ($player === $this->player1Name) ? 'player1' : 'player2';

		if(isset($this->replayData[$name])){

			/* @var PlayerReplayData $replayData */
			$replayData = $this->replayData[$name];
			$replayData->setAnimationAt($this->currentTicks, $animation);
		}
	}

	/**
	 * @param Block|Vector3 $block
	 * @param bool          $air
	 *
	 * Tracks when a block is set.
	 */
	public function setBlockAt(Block $block, bool $air = false) : void{
		if(isset($this->replayData['world'])){

			/* @var WorldReplayData $replayData */
			$replayData = $this->replayData['world'];
			$replayData->setBlockAt($this->currentTicks, $block, $air);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Vector3        $pos
	 *
	 * Tracks when a teleport is set.
	 */
	public function setTeleportAt(MineceitPlayer $player, Vector3 $pos) : void{
		$p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
		if(isset($this->replayData[$p])){
			/* @var PlayerReplayData $replayData */
			$replayData = $this->replayData[$p];
			$replayData->setTeleportAt($this->currentTicks, $pos);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param ProjectileItem $item
	 *
	 * Tracks when a player throws a pearl/potion.
	 */
	public function setThrowFor(MineceitPlayer $player, ProjectileItem $item) : void{
		$p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
		if(isset($this->replayData[$p])){
			/* @var PlayerReplayData $replayData */
			$replayData = $this->replayData[$p];
			$replayData->setThrowAt($this->currentTicks, $item);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $drink
	 *
	 * Tracks when a player eats food.
	 */
	public function setConsumeFor(MineceitPlayer $player, bool $drink) : void{
		$p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
		if(isset($this->replayData[$p])){
			/* @var PlayerReplayData $replayData */
			$replayData = $this->replayData[$p];
			$replayData->setConsumeAt($this->currentTicks, $drink);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param EffectInstance $effect
	 *
	 * Sets the effect for the player -> for replays.
	 */
	public function setEffectFor(MineceitPlayer $player, EffectInstance $effect) : void{
		$p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
		if(isset($this->replayData[$p])){
			/* @var PlayerReplayData $replayData */
			$replayData = $this->replayData[$p];
			$replayData->setEffectAt($this->currentTicks, $effect);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $fishing
	 *
	 * Sets when the player is fishing.
	 */
	public function setFishingFor(MineceitPlayer $player, bool $fishing) : void{
		$p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
		if(isset($this->replayData[$p])){
			/* @var PlayerReplayData $replayData */
			$replayData = $this->replayData[$p];
			$replayData->setFishingAt($this->currentTicks, $fishing);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool|int       $variable
	 *
	 * Sets the player on fire for a certain amount of ticks.
	 */
	public function setOnFire(MineceitPlayer $player, $variable) : void{
		$p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
		if(isset($this->replayData[$p])){
			/* @var PlayerReplayData $replayData */
			$replayData = $this->replayData[$p];
			$replayData->setFireAt($this->currentTicks, $variable);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Item           $item
	 * @param Vector3        $motion
	 */
	public function setDropItem(MineceitPlayer $player, Item $item, Vector3 $motion) : void{
		$p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
		if(isset($this->replayData[$p])){
			/* @var PlayerReplayData $replayData */
			$replayData = $this->replayData[$p];
			$replayData->setDropAt($this->currentTicks, $item, $motion);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Item           $item
	 */
	public function setPickupItem(MineceitPlayer $player, Item $item) : void{
		$p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
		if(isset($this->replayData[$p])){
			/* @var PlayerReplayData $replayData */
			$replayData = $this->replayData[$p];
			$replayData->setPickupAt($this->currentTicks, $item);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $sneak
	 */
	public function setSneakingFor(MineceitPlayer $player, bool $sneak) : void{
		$p = $player->equalsPlayer($this->player1) ? 'player1' : 'player2';
		if(isset($this->replayData[$p])){
			/* @var PlayerReplayData $replayData */
			$replayData = $this->replayData[$p];
			$replayData->setSneakingAt($this->currentTicks, $sneak);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function addSpectator(MineceitPlayer $player) : void{
		if(!$this->hasEnded()){
			$name = $player->getDisplayName();
			$local = strtolower($name);
			$this->spectators[$local] = $player;
			$player->getExtensions()->setFakeSpectator();
			$itemHandler = MineceitCore::getItemHandler();
			$itemHandler->giveLeaveDuelItem($player);
			$player->teleport($this->centerPosition);
			$this->broadcastMessageFromLang(Language::DUELS_SPECTATOR_ADD, ["name" => $name]);
			$player->getScoreboardInfo()->setScoreboard(Scoreboard::SCOREBOARD_SPECTATOR);
		}
	}

	/**
	 * @param string       $langVal
	 * @param array|string $values
	 */
	private function broadcastMessageFromLang(string $langVal, $values = []) : void{

		$prefix = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET;

		if($this->player1 !== null && $this->player1->isOnline()){
			$language = $this->player1->getLanguageInfo()->getLanguage();
			$message = $language->generalMessage($langVal, $values);
			$this->player1->sendMessage($prefix . $message);
		}

		if($this->player2 !== null && $this->player2->isOnline()){
			$language = $this->player2->getLanguageInfo()->getLanguage();
			$message = $language->generalMessage($langVal, $values);
			$this->player2->sendMessage($prefix . $message);
		}

		foreach($this->spectators as $spectator){
			if($spectator->isOnline()){
				$language = $spectator->getLanguageInfo()->getLanguage();
				$message = $language->generalMessage($langVal, $values);
				$spectator->sendMessage($prefix . $message);
			}
		}
	}

	/**
	 * @param MineceitPlayer|string $player
	 * @param bool                  $teleportToSpawn
	 * @param bool                  $sendMessage
	 */
	public function removeSpectator($player, bool $teleportToSpawn = true, bool $sendMessage = true) : void{
		$name = $player instanceof MineceitPlayer ? $player->getDisplayName() : $player;
		$local = strtolower($name);
		if(isset($this->spectators[$local])){
			$player = $this->spectators[$local];
			if($player->isOnline()){
				$player->reset(false, $teleportToSpawn);
				$player->setNormalNameTag();
				if($teleportToSpawn) MineceitCore::getItemHandler()->spawnHubItems($player);
			}
			unset($this->spectators[$local]);
			if($sendMessage) $this->broadcastMessageFromLang(Language::DUELS_SPECTATOR_LEAVE, ['name' => $name]);
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

		$name = ($player->equals($this->player1)) ? 'player1' : 'player2';

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
				if($name === 'player2'){
					if(in_array($block->getDamage(), [2, 3, 10, 11])){
						$lang = $this->player2->getLanguageInfo()->getLanguage();
						$msg = $lang->generalMessage(Language::BREAK_UR_WOOL);
						$this->player2->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
					}else{
						$this->mlgscore[$this->player2Name]++;
						$this->mlground++;
						if($this->mlgscore[$this->player2Name] !== 5){
							$this->clearBlock();
							$this->player1->getScoreboardInfo()->setScoreboard(
								Scoreboard::SCOREBOARD_DUEL
							);
							$this->player2->getScoreboardInfo()->setScoreboard(
								Scoreboard::SCOREBOARD_DUEL
							);
							$this->started = false;
							$this->ended = false;
							$this->countdownSeconds = 5;
							$this->durationSeconds = 0;
							$this->setInDuel(true);
						}else{
							$this->setEnded($this->player2);
						}
					}
				}else{
					if(in_array($block->getDamage(), [0, 1, 8, 9])){
						$lang = $this->player1->getLanguageInfo()->getLanguage();
						$msg = $lang->generalMessage(Language::BREAK_UR_WOOL);
						$this->player1->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
					}else{
						$this->mlgscore[$this->player1Name]++;
						$this->mlground++;
						if($this->mlgscore[$this->player1Name] !== 5){
							$this->clearBlock();
							$this->player1->getScoreboardInfo()->setScoreboard(
								Scoreboard::SCOREBOARD_DUEL
							);
							$this->player2->getScoreboardInfo()->setScoreboard(
								Scoreboard::SCOREBOARD_DUEL
							);
							$this->started = false;
							$this->ended = false;
							$this->countdownSeconds = 5;
							$this->durationSeconds = 0;
							$this->setInDuel(true);
						}else{
							$this->setEnded($this->player1);
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

	/**
	 * @return bool
	 */
	public function cantDamagePlayers() : bool{
		return !$this->kit->getMiscKitInfo()->canDamagePlayers();
	}

	/**
	 * @param MineceitPlayer|string $player
	 * @param float                 $damage
	 */
	public function addHitTo($player, float $damage) : void{

		$name = $player instanceof MineceitPlayer ? $player->getName() : $player;

		if(isset($this->numHits[$name]) && isset($this->replayData['player1'], $this->replayData['player2'])){
			/* @var PlayerReplayData $playerReplayData */
			$playerReplayData = ($name === $this->player1Name) ? $this->replayData['player2'] : $this->replayData['player1'];
			$playerReplayData->setDamagedAt($this->currentTicks, $damage);

			$hits = $this->numHits[$name];
			$this->numHits[$name] = $hits + 1;

			$this->boxingcombo[$name]++;

			$nowCombo = $this->boxingcombo[$name];
			foreach($this->boxingComboScore as $s)
				if($nowCombo >= $s[0]){
					$this->boxingscore[$name] += $s[1];
					break;
				}

			if($name === $this->player1Name) $this->boxingcombo[$this->player2Name] = 0;
			else $this->boxingcombo[$this->player1Name] = 0;

			$this->player1->sendPopup(TextFormat::GOLD . "Combo " . TextFormat::WHITE . $this->boxingcombo[$this->player1Name] . "\n" . TextFormat::BLUE . $this->player1DisplayName . TextFormat::WHITE . " " . $this->boxingscore[$this->player1Name] . TextFormat::GRAY . " | " . TextFormat::RED . $this->player2DisplayName . TextFormat::WHITE . " " . $this->boxingscore[$this->player2Name]);
			$this->player2->sendPopup(TextFormat::GOLD . "Combo " . TextFormat::WHITE . $this->boxingcombo[$this->player2Name] . "\n" . TextFormat::RED . $this->player2DisplayName . TextFormat::WHITE . " " . $this->boxingscore[$this->player2Name] . TextFormat::GRAY . " | " . TextFormat::BLUE . $this->player1DisplayName . TextFormat::WHITE . " " . $this->boxingscore[$this->player1Name]);

			if($this->boxingscore[$name] >= 150) $this->EndBoxing();
		}
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
	 * @return string
	 */
	public function getP1DisplayName() : string{
		return $this->player1DisplayName;
	}

	/**
	 * @return string
	 */
	public function getP2DisplayName() : string{
		return $this->player2DisplayName;
	}

	/**
	 * @param MineceitDuel $duel
	 *
	 * @return bool
	 */
	public function equals(MineceitDuel $duel) : bool{
		return $duel->isRanked() === $this->ranked && $duel->getP1Name() === $this->player1Name
			&& $duel->getP2Name() === $this->player2Name && $duel->getQueue() === $this->queue
			&& $duel->getWorldId() === $this->worldId;
	}

	/**
	 * @return string
	 */
	public function getP1Name() : string{
		return $this->player1Name;
	}

	/**
	 * @return string
	 */
	public function getP2Name() : string{
		return $this->player2Name;
	}

	/**
	 * @return string
	 */
	public function getQueue() : string{
		return $this->queue;
	}

	/**
	 * @return int
	 */
	public function getWorldId() : int{
		return $this->worldId;
	}
}
