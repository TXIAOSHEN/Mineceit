<?php

declare(strict_types=1);

namespace mineceit\player\info;

use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\parties\events\types\PartyDuel;
use mineceit\parties\events\types\PartyGames;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ScoreboardInfo{
	/** @var MineceitPlayer */
	private $parentPlayer;
	/** @var string */
	private $scoreboardType;
	/** @var Scoreboard */
	private $scoreboard;
	/** @var Server */
	private $server;
	/** @var string */
	private $title;

	/** @var int - The current line that the ping is displaying at. */
	private $currentPingLine = -1;

	public function __construct(MineceitPlayer $player){
		$this->parentPlayer = $player;
		$this->scoreboardType = Scoreboard::SCOREBOARD_NONE;
		$this->title = TextFormat::BOLD . TextFormat::LIGHT_PURPLE .
			MineceitCore::getRegion() . ' ' . TextFormat::WHITE . 'Practice';
		$this->scoreboard = null;
		$this->server = $player->getServer();
	}

	public function setScoreboard(string $scoreboardType) : void{
		// TODO: Switch parent player to a Generic Player
		if($this->parentPlayer->getSettingsInfo()->isScoreboardEnabled() && $this->scoreboardType !== $scoreboardType){
			$this->updateScoreboard($scoreboardType);
		}
	}

	private function updateScoreboard(string $scoreboardType) : void{
		$this->scoreboard = $this->scoreboard ?? new Scoreboard($this->parentPlayer, $this->title);
		$this->scoreboard->clearScoreboard();

		switch($scoreboardType){
			case Scoreboard::SCOREBOARD_NONE:
				$this->setNoScoreboard();
				break;
			case Scoreboard::SCOREBOARD_SPAWN:
				$this->setSpawnScoreboard();
				break;
			case Scoreboard::SCOREBOARD_DUEL:
				$this->setDuelScoreboard();
				break;
			case Scoreboard::SCOREBOARD_BOT_DUEL:
				$this->setBotDuelScoreboard();
				break;
			case Scoreboard::SCOREBOARD_FFA:
				$this->setFFAScoreboard();
				break;
			case Scoreboard::SCOREBOARD_SPECTATOR:
				$this->setSpectatorScoreboard();
				break;
			case Scoreboard::SCOREBOARD_REPLAY:
				$this->setReplayScoreboard();
				break;
			case Scoreboard::SCOREBOARD_EVENT_SPEC:
				$this->setEventSpectatorScoreboard();
				break;
			case Scoreboard::SCOREBOARD_EVENT_DUEL:
				$this->setEventDuelScoreboard();
				break;
		}
	}

	private function setNoScoreboard() : void{
		if($this->scoreboard !== null){
			$this->scoreboard->removeScoreboard();
		}
		$this->currentPingLine = -1;
		$this->scoreboardType = Scoreboard::SCOREBOARD_NONE;
		$this->scoreboard = null;
	}

	/**
	 * Sets the spawn scoreboard.
	 */
	private function setSpawnScoreboard() : void{
		$language = $this->getLanguage();
		$onlineStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_ONLINE);
		$inFightsStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_INFIGHTS);
		$inQueuesStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_INQUEUES);
		$deathsStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_DEATHS);
		$killsStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_KILLS);
		$pingStr = $language->scoreboard(Language::PLAYER_PING);

		$online = count($this->server->getOnlinePlayers());
		$inQueues = MineceitCore::getDuelHandler()->getEveryoneInQueues();

		$kills = $this->parentPlayer->getStatsInfo()->getKills();
		$deaths = $this->parentPlayer->getStatsInfo()->getDeaths();

		$color = MineceitUtil::getThemeColor();

		$onlineSb = " $onlineStr: " . $color . $online;
		$inFightsSb = " $inFightsStr: " . $color . '0 ';
		$inQueuesSb = " $inQueuesStr: " . $color . "$inQueues ";
		$yourPing = " $pingStr: " . $color . $this->parentPlayer->getPing();
		$killsSb = " $killsStr: " . $color . $kills;
		$deathsSb = " $deathsStr: " . $color . $deaths;

		$this->scoreboard->addLine(0, TextFormat::DARK_GRAY . ' ----------------');
		$this->scoreboard->addLine(1, $onlineSb);
		$this->scoreboard->addLine(2, $yourPing);
		$this->scoreboard->addLine(3, '');
		$this->scoreboard->addLine(4, $killsSb . TextFormat::RESET . $deathsSb);
		$this->scoreboard->addLine(5, ' ');
		$this->scoreboard->addLine(6, $inFightsSb);
		$this->scoreboard->addLine(7, $inQueuesSb);
		$this->scoreboard->addLine(8, TextFormat::DARK_GRAY . ' ---------------- ');

		$this->currentPingLine = 2;

		$duelHandler = MineceitCore::getDuelHandler();
		$this->scoreboardType = Scoreboard::SCOREBOARD_SPAWN;

		if($this->parentPlayer->isInQueue()){
			$queue = $duelHandler->getQueueOf($this->parentPlayer);
			$this->addQueueToScoreboard($queue->isRanked(), $queue->getQueue());
		}else{
			$this->scoreboard->addLine(9, '        ' . $color . 'Zeqa.net');
		}
	}

	/**
	 * @return Language|null
	 * Gets the language of the player via a helper function.
	 */
	private function getLanguage() : ?Language{
		return $this->parentPlayer->getLanguageInfo()->getLanguage();
	}

	/**
	 * @param bool   $ranked
	 * @param string $queue
	 *
	 * Adds the queue to the scoreboard.
	 */
	public function addQueueToScoreboard(bool $ranked, string $queue) : void{
		$language = $this->getLanguage();
		$queueStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_QUEUE);
		$color = MineceitUtil::getThemeColor();

		if($this->scoreboardType === Scoreboard::SCOREBOARD_SPAWN){
			$this->scoreboard->addLine(8, '  ');
			$this->scoreboard->addLine(9, " $queueStr:");
			$this->scoreboard->addLine(10, ' ' . $color . $language->getRankedStr($ranked) . ' ' . $queue . ' ');
			$this->scoreboard->addLine(11, TextFormat::DARK_GRAY . ' ---------------- ');
			$this->scoreboard->addLine(12, '        ' . $color . 'Zeqa.net');
		}
	}

	private function setDuelScoreboard() : void{
		$lang = $this->getLanguage();
		$color = MineceitUtil::getThemeColor();
		$durationStr = ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION) . ': ' . $color . '00:00';
		$yourPing = ' ' . $lang->scoreboard(Language::PLAYER_PING) . ': ' . $color . $this->parentPlayer->getPing();
		$this->scoreboard->addLine(0, TextFormat::DARK_GRAY . ' ----------------');
		$this->scoreboard->addLine(1, $durationStr);
		$this->scoreboard->addLine(2, '');
		$this->scoreboard->addLine(3, $yourPing);
		$this->scoreboard->addLine(4, TextFormat::DARK_GRAY . ' ---------------- ');
		$this->scoreboard->addLine(5, '        ' . $color . 'Zeqa.net');
		$this->scoreboardType = Scoreboard::SCOREBOARD_DUEL;
		$this->currentPingLine = 3;
	}

	private function setBotDuelScoreboard() : void{
		$lang = $this->getLanguage();
		$color = MineceitUtil::getThemeColor();
		$durationStr = ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION) . ': ' . $color . '00:00';
		$yourPing = ' ' . $lang->scoreboard(Language::PLAYER_PING) . ': ' . $color . $this->parentPlayer->getPing();
		$this->scoreboard->addLine(0, TextFormat::DARK_GRAY . ' ----------------');
		$this->scoreboard->addLine(1, $durationStr);
		$this->scoreboard->addLine(2, '');
		$this->scoreboard->addLine(3, $yourPing);
		$this->scoreboard->addLine(4, TextFormat::DARK_GRAY . ' ---------------- ');
		$this->scoreboard->addLine(5, '        ' . $color . 'Zeqa.net');
		$this->scoreboardType = Scoreboard::SCOREBOARD_BOT_DUEL;
		$this->currentPingLine = 3;
	}

	private function setFFAScoreboard() : void{
		$language = $this->getLanguage();
		$arenaStr = $language->scoreboard(Language::FFA_SCOREBOARD_ARENA);
		$color = MineceitUtil::getThemeColor();
		$arenaSb = TextFormat::WHITE . " $arenaStr: " . $color . $this->parentPlayer->getArena()->getName();
		$yourPing = ' ' . $language->scoreboard(Language::PLAYER_PING) . ': ' . $color . $this->parentPlayer->getPing();
		$this->scoreboard->addLine(0, TextFormat::DARK_GRAY . ' ----------------');
		$this->scoreboard->addLine(1, $arenaSb);
		$this->scoreboard->addLine(2, '');
		$this->scoreboard->addLine(3, $yourPing);
		$this->scoreboard->addLine(4, TextFormat::DARK_GRAY . ' ---------------- ');
		$this->scoreboard->addLine(5, '        ' . $color . 'Zeqa.net');
		$this->scoreboardType = Scoreboard::SCOREBOARD_FFA;
		$this->currentPingLine = 3;
	}

	private function setSpectatorScoreboard() : void{
		$duelHandler = MineceitCore::getDuelHandler();
		$duel = $duelHandler->getDuelFromSpec($this->parentPlayer);
		$lang = $this->getLanguage();
		$color = MineceitUtil::getThemeColor();

		if($duel !== null){
			$ranked = $duel->isRanked();
			$queue = $duel->getQueue();
			$duration = $duel->getDuration();

			$durationStr = ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION) . ': ' . $color . "$duration";
			$queueLineTop = ' ' . $lang->scoreboard(Language::SPAWN_SCOREBOARD_QUEUE) . ': ';
			$queueLineBottom = ' ' . $color . $lang->getRankedStr($ranked) . ' ' . $queue . ' ';

			$this->scoreboard->addLine(0, TextFormat::DARK_GRAY . ' ----------------');
			$this->scoreboard->addLine(1, $durationStr);
			$this->scoreboard->addLine(2, '');
			$this->scoreboard->addLine(3, $queueLineTop);
			$this->scoreboard->addLine(4, $queueLineBottom);
			$this->scoreboard->addLine(5, TextFormat::DARK_GRAY . ' ---------------- ');
			$this->scoreboard->addLine(6, '        ' . $color . 'Zeqa.net');
			$this->scoreboardType = Scoreboard::SCOREBOARD_SPECTATOR;
			$this->currentPingLine = -1;
		}elseif(($partyEvent = $this->parentPlayer->getPartyEvent()) !== null){
			if($partyEvent instanceof PartyDuel || $partyEvent instanceof PartyGames){
				$duration = $partyEvent->getDuration();
				$durationStr = ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION) . ': ' . $color . "$duration";

				$this->scoreboard->addLine(0, TextFormat::DARK_GRAY . ' ----------------');
				$this->scoreboard->addLine(1, $durationStr);
				$this->scoreboard->addLine(2, TextFormat::DARK_GRAY . ' ---------------- ');
				$this->scoreboard->addLine(3, '        ' . $color . 'Zeqa.net');

				$this->scoreboardType = Scoreboard::SCOREBOARD_SPECTATOR;
				$this->currentPingLine = -1;
			}
		}
	}

	private function setReplayScoreboard() : void{
		$lang = $this->getLanguage();
		$replay = MineceitCore::getReplayManager()->getReplayFrom($this->parentPlayer);

		if($replay !== null){
			$color = MineceitUtil::getThemeColor();
			$queue = $replay->getQueue();
			$ranked = $replay->isRanked();
			$duration = $replay->getDuration();
			$durationStrTop = ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION) . ': ';
			$durationStrBottom = ' ' . $color . "$duration" . TextFormat::WHITE . " | " . $color . $replay->getMaxDuration() . ' ';
			$queueLineTop = ' ' . $lang->scoreboard(Language::SPAWN_SCOREBOARD_QUEUE) . ': ';
			$queueLineBottom = ' ' . $color . $lang->getRankedStr($ranked) . ' ' . $queue . ' ';
			$this->scoreboard->addLine(0, TextFormat::DARK_GRAY . ' ----------------');
			$this->scoreboard->addLine(1, $durationStrTop);
			$this->scoreboard->addLine(2, $durationStrBottom);
			$this->scoreboard->addLine(3, '');
			$this->scoreboard->addLine(4, $queueLineTop);
			$this->scoreboard->addLine(5, $queueLineBottom);
			$this->scoreboard->addLine(6, TextFormat::DARK_GRAY . ' ---------------- ');
			$this->scoreboard->addLine(7, '        ' . $color . 'Zeqa.net');
			$this->scoreboardType = Scoreboard::SCOREBOARD_REPLAY;
			$this->currentPingLine = -1;
		}
	}

	private function setEventSpectatorScoreboard() : void{

		$color = MineceitUtil::getThemeColor();
		$lang = $this->getLanguage();
		$event = MineceitCore::getEventManager()->getEventFromPlayer($this->parentPlayer);

		if($event === null){
			return;
		}

		$eventType = $event->getName();
		$players = strval($event->getPlayers(true));
		$eliminated = strval($event->getEliminated(true));
		$playersLine = " " . $lang->getMessage(Language::PLAYERS_LABEL) . ": {$color}{$players} ";
		$eventTypeLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_EVENT_TYPE, ["type" => $eventType]) . " ";
		$eliminatedLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_ELIMINATED, ["num" => $eliminated]) . " ";
		$start = 1;

		$this->scoreboard->addLine(0, TextFormat::DARK_GRAY . ' ----------------');
		if(!$event->hasStarted()){
			$startingTime = $event->getTimeUntilStart();
			$startingInLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_STARTING_IN, ["time" => $startingTime]) . " ";
			$this->scoreboard->addLine(1, $startingInLine);
			$this->scoreboard->addLine(2, '   ');
			$start = 3;
		}
		$this->scoreboard->addLine($start, $playersLine);
		$this->scoreboard->addLine($start + 1, $eventTypeLine);
		$this->scoreboard->addLine($start + 2, '');
		$this->scoreboard->addLine($start + 3, $eliminatedLine);
		$this->scoreboard->addLine($start + 4, TextFormat::DARK_GRAY . ' ---------------- ');
		$this->scoreboard->addLine($start + 5, '        ' . $color . 'Zeqa.net');
		$this->scoreboardType = Scoreboard::SCOREBOARD_EVENT_SPEC;
		$this->currentPingLine = -1;
	}

	private function setEventDuelScoreboard() : void{
		$color = MineceitUtil::getThemeColor();
		$lang = $this->getLanguage();
		$durationStr = ' ' . $lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION) . ': ' . $color . '00:00';
		$yourPing = ' ' . $lang->scoreboard(Language::PLAYER_PING) . ': ' . $color . $this->parentPlayer->getPing();
		$this->scoreboard->addLine(0, TextFormat::DARK_GRAY . ' ----------------');
		$this->scoreboard->addLine(1, $durationStr);
		$this->scoreboard->addLine(2, '');
		$this->scoreboard->addLine(3, $yourPing);
		$this->scoreboard->addLine(4, TextFormat::DARK_GRAY . ' ---------------- ');
		$this->scoreboard->addLine(5, '        ' . $color . 'Zeqa.net');
		$this->scoreboardType = Scoreboard::SCOREBOARD_EVENT_DUEL;
		$this->currentPingLine = 3;
	}

	public function getScoreboardType() : string{
		return $this->scoreboardType;
	}

	public function reloadScoreboard() : void{
		$this->updateScoreboard($this->scoreboardType);
	}

	/**
	 * @return MineceitPlayer
	 *
	 * Gets the player of the scoreboard.
	 */
	public function getPlayer() : MineceitPlayer{
		return $this->parentPlayer;
	}

	public function removeQueueFromScoreboard() : void{
		$color = MineceitUtil::getThemeColor();

		if($this->scoreboardType === Scoreboard::SCOREBOARD_SPAWN){
			$this->scoreboard->removeLine(10);
			$this->scoreboard->removeLine(11);
			$this->scoreboard->removeLine(12);
			$this->scoreboard->addLine(8, TextFormat::DARK_GRAY . ' ---------------- ');
			$this->scoreboard->addLine(9, '        ' . $color . 'Zeqa.net');
		}
	}

	public function addPausedToScoreboard() : void{
		$language = $this->getLanguage();
		$paused = $language->scoreboard(Language::SCOREBOARD_REPLAY_PAUSED);
		$color = MineceitUtil::getThemeColor();

		if($this->scoreboardType === Scoreboard::SCOREBOARD_REPLAY){
			$this->scoreboard->addLine(6, ' ');
			$this->scoreboard->addLine(7, TextFormat::RED . TextFormat::BOLD . " $paused");
			$this->scoreboard->addLine(8, TextFormat::DARK_GRAY . ' ---------------- ');
			$this->scoreboard->addLine(9, '        ' . $color . 'Zeqa.net');
		}
	}

	public function removePausedFromScoreboard() : void{
		$color = MineceitUtil::getThemeColor();
		if($this->scoreboardType === Scoreboard::SCOREBOARD_REPLAY){
			$this->scoreboard->removeLine(8);
			$this->scoreboard->removeLine(9);
			$this->scoreboard->addLine(6, TextFormat::DARK_GRAY . ' ---------------- ');
			$this->scoreboard->addLine(7, '        ' . $color . 'Zeqa.net');
		}
	}

	/**
	 * @param int $ping
	 *
	 * Updates the ping of the player in the scoreboard.
	 */
	public function updatePing(int $ping){
		if($this->currentPingLine !== -1){
			$pingLine = ' ' . $this->getLanguage()->scoreboard(Language::PLAYER_PING) .
				': ' . MineceitUtil::getThemeColor() . $ping;
			$this->updateLineOfScoreboard($this->currentPingLine, $pingLine);
		}
	}

	/**
	 * @param int    $line
	 * @param string $display
	 *
	 * Updates the line of the scoreboard.
	 */
	public function updateLineOfScoreboard(int $line, string $display) : void{
		if($this->scoreboard !== null && $this->scoreboardType !== Scoreboard::SCOREBOARD_NONE){
			$this->scoreboard->addLine($line, $display);
		}
	}
}
