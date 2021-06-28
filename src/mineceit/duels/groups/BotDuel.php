<?php

declare(strict_types=1);

namespace mineceit\duels\groups;

use mineceit\arenas\DuelArena;
use mineceit\game\entities\bots\AbstractBot;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class BotDuel{

	private const MAX_DURATION_SECONDS = 60 * 10;

	/* @var MineceitPlayer */
	private $player;

	/* @var AbstractBot */
	private $bot;

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

	/* @var Server */
	private $server;

	/* @var int */
	private $currentTicks;

	/* @var int|null */
	private $endTick;

	/* @var Block[]|array */
	private $block;

	/* @var Position|null */
	private $centerPosition;

	/* @var DuelArena */
	private $arena;

	/** @var string */
	private $playerName, $botName, $playerDisplayName, $botDisplayName;

	public function __construct(int $worldId, MineceitPlayer $player, AbstractBot $bot, DuelArena $arena){
		$this->player = $player;
		$this->playerName = $player->getName();
		$this->playerDisplayName = $player->getDisplayName();
		$this->bot = $bot;
		$this->botName = $bot->getName();
		$this->botDisplayName = $bot->getNameTag();
		$this->arena = $arena;
		$this->worldId = $worldId;
		$this->kit = MineceitCore::getKits()->getKit("NoDebuff");
		$this->server = Server::getInstance();
		$this->block = [];
		$this->level = $this->server->getLevelByName("bot$worldId");
		$this->centerPosition = null;

		$this->started = false;
		$this->ended = false;
		$this->countdownSeconds = 5;
		$this->durationSeconds = 0;
		$this->currentTicks = 0;

		$this->endTick = null;

		$this->winner = null;
		$this->loser = null;
	}

	/**
	 * Updates the duel.
	 */
	public function update(){

		$this->currentTicks++;

		$checkSeconds = $this->currentTicks % 20 === 0;

		if(!$this->player->isOnline()){
			if($this->ended) $this->endDuel();
			return;
		}

		$pLang = $this->player->getLanguageInfo()->getLanguage();

		if($this->isCountingDown()){

			if($this->currentTicks === 5) $this->setInDuel();

			if($checkSeconds){

				$pSbInfo = $this->player->getScoreboardInfo();
				if($this->countdownSeconds === 5){
					$pMsg = $this->getCountdownMessage(true, $pLang, $this->countdownSeconds);
					$this->player->sendTitle($pMsg, '', 5, 20, 5);
				}elseif($this->countdownSeconds !== 0){

					$pMsg = $this->getJustCountdown($pLang, $this->countdownSeconds);
					$this->player->sendTitle($pMsg, '', 5, 20, 5);
				}else{

					$pMsg = $pLang->generalMessage(Language::DUELS_MESSAGE_STARTING);
					$this->player->sendTitle($pMsg, '', 5, 10, 5);
					if($this->bot->isAlive()) $this->bot->setCanMove(true);
				}

				$scoreboardType = $pSbInfo->getScoreboardType();
				if($scoreboardType !== Scoreboard::SCOREBOARD_NONE && $scoreboardType !== Scoreboard::SCOREBOARD_BOT_DUEL){
					$pSbInfo->setScoreboard(Scoreboard::SCOREBOARD_BOT_DUEL);
				}

				if($this->countdownSeconds <= 0){
					$this->started = true;
					$this->player->setImmobile(false);
				}

				$this->countdownSeconds--;
			}
		}elseif($this->isRunning()){

			if($this->botName === 'ClutchBot'){

				$spawnPos = $this->arena->getP1SpawnPos();
				$minY = $spawnPos->getY() - 5;

				$this->player->sendPopup(TextFormat::LIGHT_PURPLE . 'Distance: ' . TextFormat::WHITE . ceil(abs($this->player->distance($spawnPos))) . TextFormat::GRAY . " | " . TextFormat::LIGHT_PURPLE . 'Block Placed: ' . TextFormat::WHITE . count($this->block));

				if($this->bot->isAlive() && $this->bot !== null && $this->bot->getLevel() !== null){
					$pPos = $this->player->getPosition();
					if($pPos->y < $minY){
						$pPos = new Position($spawnPos->getX(), $spawnPos->getY(), $spawnPos->getZ(), $this->level);
						$this->player->teleport($pPos);
						foreach($this->block as $deleteblock){
							$pos = explode(':', $deleteblock);
							$this->level->setBlock(new Vector3($pos[0], $pos[1], $pos[2]), Block::get(Block::AIR));
						}
						$this->block = [];
						$spawnPos = $this->arena->getP2SpawnPos();
						$p2Pos = new Position($spawnPos->getX(), $spawnPos->getY(), $spawnPos->getZ(), $this->level);
						if($this->bot->isAlive() && $this->bot !== null && $this->bot->getLevel() !== null){
							$this->bot->teleport($p2Pos);
							$this->bot->setCanMove(false);
						}
						$itemManager = MineceitCore::getItemHandler();
						$itemManager->spawnBotItems($this->player);
					}elseif($this->bot->canMove()){
						$this->player->getInventory()->setItem(0, Item::get(Item::SANDSTONE, 0, 64));
					}
				}
			}

			if($checkSeconds){

				$pDuration = TextFormat::WHITE . $pLang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);

				$pDurationStr = TextFormat::WHITE . ' ' . $pDuration . ': ' . $this->getDuration();

				$pSb = $this->player->getScoreboardInfo();
				$pSb->updateLineOfScoreboard(1, $pDurationStr);

				if($this->durationSeconds >= self::MAX_DURATION_SECONDS){
					$this->setEnded(false);
					return;
				}

				$this->durationSeconds++;
			}
		}elseif($this->hasEnded()){
			$diff = $this->currentTicks - $this->endTick;
			if($diff >= 10){
				$this->endDuel();
				return;
			}
		}
	}

	private function endDuel() : void{

		$this->ended = true;

		if($this->endTick === null) $this->endTick = $this->currentTicks;

		$itemHandler = MineceitCore::getItemHandler();

		if($this->player !== null && $this->player->isOnline()){
			$this->sendFinalMessage($this->player);
			$pSbInfo = $this->player->getScoreboardInfo();
			if($pSbInfo->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
				$pSbInfo->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
			}
			$this->player->reset(true, $this->player->isAlive());
			$itemHandler->spawnHubItems($this->player);
			$this->player->setNormalNameTag();
		}

		MineceitUtil::deleteLevel($this->level);
		MineceitCore::getBotHandler()->removeDuel($this->worldId);
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

			$winnerMessage = $lang->generalMessage(Language::DUELS_MESSAGE_WINNER, ["name" => $winner]);
			$loserMessage = $lang->generalMessage(Language::DUELS_MESSAGE_LOSER, ["name" => $loser]);

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
	 * @return bool
	 */
	public function isCountingDown() : bool{
		return !$this->started && !$this->ended;
	}

	/**
	 * Sets the players in the duel.
	 */
	private function setInDuel() : void{
		$this->player->setGamemode(0);
		$this->player->getExtensions()->enableFlying(false);
		$this->player->setImmobile(true);

		$this->player->getExtensions()->clearAll();

		$level = $this->level;
		$spawnPos = $this->arena->getP1SpawnPos();
		$x = $spawnPos->getX();
		$y = $spawnPos->getY();
		$z = $spawnPos->getZ();

		$p1Pos = new Position($x, $y, $z, $level);

		MineceitUtil::onChunkGenerated($level, $x >> 4, $z >> 4, function() use ($p1Pos){
			$this->player->teleport($p1Pos);
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
			$this->bot->spawnToAll();
			$this->bot->teleport($p2Pos);
		});

		if($this->botName === 'ClutchBot'){
			$itemManager = MineceitCore::getItemHandler();
			$itemManager->givePauseBotItem($this->player);
		}else{
			$this->player->getKitHolder()->setKit($this->kit);
		}

		$this->bot->giveItems();
		$this->bot->setTarget($this->player);

		$pLevel = $this->player->getLevel();

		if($pLevel->getName() !== $level->getName())
			$this->player->teleport($p1Pos);
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
	 * @param bool $win
	 *
	 * Sets the duel as ended.
	 */
	public function setEnded(bool $win) : void{
		if($win){
			if($this->botName === 'HackerBot') $this->player->setValidTags(TextFormat::BOLD . TextFormat::RED . 'DEM' . TextFormat::DARK_RED . 'ONIC');
			$this->winner = $this->playerDisplayName;
			$this->loser = TextFormat::GRAY . $this->botName;
		}else{
			$this->winner = TextFormat::GRAY . $this->botName;
			$this->loser = $this->playerDisplayName;
		}

		$this->ended = true;
		$this->endTick = $this->currentTicks;
	}

	/**
	 * @return bool
	 */
	public function hasEnded() : bool{
		return $this->ended;
	}

	public function clutchStart() : void{
		$this->started = false;
		$this->ended = false;
		$this->countdownSeconds = 3;
		$itemManager = MineceitCore::getItemHandler();
		$itemManager->givePauseBotItem($this->player);
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return bool
	 */
	public function isPlayer($player) : bool{
		$name = $player instanceof MineceitPlayer ? $player->getName() : $player;
		return $this->playerName === $name;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Block          $block
	 *
	 * @return bool
	 */
	public function canPlaceBlock(MineceitPlayer $player, Block $block) : bool{

		$x = $block->x;
		$y = $block->y;
		$z = $block->z;
		$this->block[] = $x . ':' . $y . ':' . $z;

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
	public function getTexture() : string{
		return $this->kit !== null ? $this->kit
			->getMiscKitInfo()->getTexture() : '';
	}

	/**
	 * @return AbstractBot
	 */
	public function getBot() : AbstractBot{
		return $this->bot;
	}

	/**
	 * @return string
	 */
	public function getBName() : string{
		return $this->botName;
	}

	/**
	 * @return DuelArena
	 */
	public function getArena() : DuelArena{
		return $this->arena;
	}

	/**
	 * @param BotDuel $duel
	 *
	 * @return bool
	 */
	public function equals(BotDuel $duel) : bool{
		return $duel->getPName() === $this->playerName
			&& $duel->botName === $this->botName
			&& $duel->getWorldId() === $this->worldId;
	}

	/**
	 * @return string
	 */
	public function getPName() : string{
		return $this->playerName;
	}

	/**
	 * @return int
	 */
	public function getWorldId() : int{
		return $this->worldId;
	}
}
