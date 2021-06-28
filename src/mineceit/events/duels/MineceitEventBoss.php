<?php

declare(strict_types=1);

namespace mineceit\events\duels;

use mineceit\arenas\EventArena;
use mineceit\events\MineceitEvent;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class MineceitEventBoss{

	const STATUS_STARTING = 0;
	const STATUS_IN_PROGRESS = 1;
	const STATUS_ENDING = 2;
	const STATUS_ENDED = 3;

	/** @var MineceitPlayer[] */
	private $playersLeft;

	/** @var MineceitPlayer */
	private $boss;

	/** @var MineceitPlayer */
	private $eliminated;

	/** @var string */
	private $bossName;

	/** @var int */
	private $currentTick;

	/** @var int */
	private $countdownSeconds;

	/** @var int */
	private $durationSeconds;

	/** @var MineceitEvent */
	private $event;

	/** @var int */
	private $stat;

	/** @var int */
	private $status;

	/** @var string|null */
	private $winner;

	/** @var string|null */
	private $loser;

	/**
	 * MineceitEventBoss constructor.
	 *
	 * @param MineceitPlayer[] $players
	 * @param MineceitPlayer   $boss
	 * @param MineceitEvent    $event
	 */
	public function __construct(array $players, MineceitPlayer $boss, MineceitEvent $event){
		$this->playersLeft = $players;
		$this->stat = count($players);
		$this->boss = $boss;
		$this->eliminated = null;
		$this->currentTick = 0;
		$this->durationSeconds = 0;
		$this->countdownSeconds = 5;
		$this->event = $event;
		$this->status = self::STATUS_STARTING;
		$this->bossName = $boss->getName();
	}

	/**
	 * Updates the duel.
	 */
	public function update() : void{

		$this->currentTick++;

		$checkSeconds = $this->currentTick % 20 === 0;

		if(!$this->boss->isOnline() || count($this->playersLeft) === 0){

			if(!$this->boss->isOnline()){
				$this->winner = 'Challenger';
				$this->loser = $this->bossName;

				if($this->status !== self::STATUS_ENDED){

					foreach($this->playersLeft as $player){
						if($player->isOnline()){
							$player->getKitHolder()->clearKit();
							$player->reset(true, false);
							$arena = $this->event->getArena();
							$arena->teleportPlayer($player);
							$player->getScoreboardInfo()->setScoreboard(
								Scoreboard::SCOREBOARD_EVENT_SPEC
							);
							$player->setNormalNameTag();
							$player->getExtensions()->getBossBar()
								->setEnabled(false);
						}
					}
				}
			}else{
				$this->winner = $this->bossName;
				$this->loser = 'Challenger';

				if($this->status !== self::STATUS_ENDED){

					foreach($this->playersLeft as $player){
						if($player->isOnline()){
							$player->getKitHolder()->clearKit();
							$player->reset(true, false);
							$arena = $this->event->getArena();
							$arena->teleportPlayer($player);
							$player->getScoreboardInfo()->setScoreboard(
								Scoreboard::SCOREBOARD_EVENT_SPEC
							);
							$player->setNormalNameTag();
							$player->getExtensions()->getBossBar()->setEnabled(false);
						}
					}
				}

				if($this->boss->isOnline()){
					$this->boss->getKitHolder()->clearKit();
					$this->boss->reset(true, false);
					$arena = $this->event->getArena();
					$arena->teleportPlayer($this->boss);
					$this->boss->getScoreboardInfo()->setScoreboard(
						Scoreboard::SCOREBOARD_EVENT_SPEC
					);
					$this->boss->setNormalNameTag();
					$this->boss->setMaxHealth(20);
					$this->boss->setHealth(20);
					$this->boss->setScale(1);
					$this->boss->getExtensions()->getBossBar()
						->setEnabled(false);
				}
			}

			$this->status = self::STATUS_ENDED;
			return;
		}

		if($this->status === self::STATUS_STARTING){

			if($this->currentTick === 4){
				$this->setPlayersInDuel();
			}

			if($checkSeconds){

				foreach($this->playersLeft as $player){
					$pSb = $player->getScoreboardInfo();
					if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE && $pSb !== Scoreboard::SCOREBOARD_EVENT_DUEL){
						$pSb->setScoreboard(Scoreboard::SCOREBOARD_EVENT_DUEL);
					}
				}

				$bLang = $this->boss->getLanguageInfo()->getLanguage();
				$bSb = $this->boss->getScoreboardInfo();
				if($bSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE && $bSb->getScoreboardType() !== Scoreboard::SCOREBOARD_EVENT_DUEL){
					$bSb->setScoreboard(Scoreboard::SCOREBOARD_EVENT_DUEL);
				}

				// Countdown messages.
				if($this->countdownSeconds === 5){

					foreach($this->playersLeft as $player){
						$pLang = $player->getLanguageInfo()->getLanguage();
						$pMsg = $this->getCountdownMessage(true, $pLang, $this->countdownSeconds);
						$player->sendTitle($pMsg, '', 5, 20, 5);
					}

					$bMsg = $this->getCountdownMessage(true, $bLang, $this->countdownSeconds);
					$this->boss->sendTitle($bMsg, '', 5, 20, 5);
				}elseif($this->countdownSeconds !== 0){

					foreach($this->playersLeft as $player){
						$pLang = $player->getLanguageInfo()->getLanguage();
						$pMsg = $this->getJustCountdown($pLang, $this->countdownSeconds);
						$player->sendTitle($pMsg, '', 5, 20, 5);
					}

					$bMsg = $this->getJustCountdown($bLang, $this->countdownSeconds);
					$this->boss->sendTitle($bMsg, '', 5, 20, 5);
				}else{

					foreach($this->playersLeft as $player){
						$pLang = $player->getLanguageInfo()->getLanguage();
						$pMsg = $pLang->generalMessage(Language::DUELS_MESSAGE_STARTING);
						$player->sendTitle($pMsg, '', 5, 20, 5);
					}

					$bMsg = $bLang->generalMessage(Language::DUELS_MESSAGE_STARTING);
					$this->boss->sendTitle($bMsg, '', 5, 10, 5);
				}

				if($this->countdownSeconds === 0){
					$this->status = self::STATUS_IN_PROGRESS;
					foreach($this->playersLeft as $player){
						$player->setImmobile(false);
						$player->setCombatNameTag();
					}
					$this->boss->setImmobile(false);
					$this->boss->setCombatNameTag();
					return;
				}

				$this->countdownSeconds--;
			}
		}elseif($this->status === self::STATUS_IN_PROGRESS){

			// Used for updating scoreboards.
			if($checkSeconds){

				foreach($this->playersLeft as $player){
					$pLang = $player->getLanguageInfo()->getLanguage();
					$pDuration = TextFormat::WHITE . $pLang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
					$pDurationStr = TextFormat::WHITE . ' ' . $pDuration . ': ' . $this->getDuration();
					$player->getScoreboardInfo()->updateLineOfScoreboard(1, $pDurationStr);
				}

				$bLang = $this->boss->getLanguageInfo()->getLanguage();
				$bDuration = TextFormat::WHITE . $bLang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
				$bDurationStr = TextFormat::WHITE . ' ' . $bDuration . ': ' . $this->getDuration();
				$this->boss->getScoreboardInfo()->updateLineOfScoreboard(1, $bDurationStr);

				// Add a maximum duel duration if necessary.
				$this->durationSeconds++;
			}
		}elseif($this->status === self::STATUS_ENDING){

			foreach($this->playersLeft as $player){
				if($player->isOnline()){
					$player->getKitHolder()->clearKit();
					$player->reset(true, false);
					$arena = $this->event->getArena();
					$arena->teleportPlayer($player);
					$player->getScoreboardInfo()->setScoreboard(
						Scoreboard::SCOREBOARD_EVENT_SPEC);
					$player->setNormalNameTag();
					$player->getExtensions()->getBossBar()->setEnabled(false);
				}
			}

			if($this->boss->isOnline()){
				$this->boss->getKitHolder()->clearKit();
				$this->boss->reset(true, false);
				$arena = $this->event->getArena();
				$arena->teleportPlayer($this->boss);
				$this->boss->getScoreboardInfo()->setScoreboard(
					Scoreboard::SCOREBOARD_EVENT_SPEC
				);
				$this->boss->setNormalNameTag();
				$this->boss->setMaxHealth(20);
				$this->boss->setHealth(20);
				$this->boss->setScale(1);
				$this->boss->getExtensions()->getBossBar()->setEnabled(false);
			}

			$this->status = self::STATUS_ENDED;
		}
	}

	/**
	 * Sets the players in a duel/
	 */
	protected function setPlayersInDuel() : void{

		$arena = $this->event->getArena();

		$this->boss->setGamemode(0);
		$this->boss->getExtensions()->enableFlying(false);
		$this->boss->setImmobile();
		$this->boss->getExtensions()->clearAll();
		$this->boss->setMaxHealth(20 + (6 * $this->stat));
		$this->boss->setHealth(20 + (6 * $this->stat));
		$this->boss->setScale(2);
		$arena->teleportPlayer($this->boss, EventArena::P2);
		$sword = Item::get(Item::DIAMOND_SWORD);
		// TODO: Check if extra parameters mattered
		$protection = new EnchantmentInstance(Enchantment::getEnchantment(
			Enchantment::PROTECTION
		), (int) ($this->stat / 10) + 2);
		$unbreaking = new EnchantmentInstance(Enchantment::getEnchantment(
			Enchantment::UNBREAKING
		), 10);
		$sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName('sharpness'), 2));
		$sword->addEnchantment($unbreaking);
		$this->boss->getInventory()->setItem(0, $sword);
		$helmet = Item::get(Item::DIAMOND_HELMET);
		$helmet->addEnchantment($protection);
		$helmet->addEnchantment($unbreaking);
		$chestplate = Item::get(Item::DIAMOND_CHESTPLATE);
		$chestplate->addEnchantment($protection);
		$chestplate->addEnchantment($unbreaking);
		$leggings = Item::get(Item::DIAMOND_LEGGINGS);
		$leggings->addEnchantment($protection);
		$leggings->addEnchantment($unbreaking);
		$boots = Item::get(Item::DIAMOND_BOOTS);
		$boots->addEnchantment($protection);
		$boots->addEnchantment($unbreaking);
		$this->boss->getArmorInventory()->setHelmet($helmet);
		$this->boss->getArmorInventory()->setChestplate($chestplate);
		$this->boss->getArmorInventory()->setLeggings($leggings);
		$this->boss->getArmorInventory()->setBoots($boots);

		$bossBar = $this->boss->getExtensions()->getBossBar();
		$bossBar->setText($this->bossName);
		$bossBar->setEnabled(true);

		foreach($this->playersLeft as $player){
			$player->setGamemode(0);
			$extension = $player->getExtensions();
			$extension->enableFlying(false);
			$player->setImmobile();
			$player->getExtensions()->clearAll();
			$arena->teleportPlayer($player, EventArena::P1);
			$bossBar = $extension->getBossBar();
			$bossBar->setText($this->bossName);
			$bossBar->setEnabled(true);
		}

		foreach($this->playersLeft as $player){
			$pLang = $player->getLanguageInfo()->getLanguage();
			$pMessage = $pLang->getMessage(Language::EVENTS_MESSAGE_DUELS_MATCHED, ["name" => $this->bossName]);
			$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $pMessage);
		}

		$bLang = $this->boss->getLanguageInfo()->getLanguage();
		$bMessage = $bLang->getMessage(Language::EVENTS_MESSAGE_DUELS_MATCHED, ["name" => 'Challenger']);
		$this->boss->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $bMessage);
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
	 * @return string|null
	 */
	public function getEliminated(){
		return $this->eliminated;
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function setEliminated(MineceitPlayer $player) : void{
		if($player->isOnline()){
			$player->getKitHolder()->clearKit();
			$player->reset(true, false);
			$arena = $this->event->getArena();
			$arena->teleportPlayer($player);
			$player->getScoreboardInfo()->setScoreboard(
				Scoreboard::SCOREBOARD_EVENT_SPEC
			);
			$player->setNormalNameTag();
			if($player === $this->boss){
				$player->setScale(1);
				$player->setMaxHealth(20);
				$player->setHealth(20);
			}
		}
		if($player === $this->boss){
			$this->status = self::STATUS_ENDING;
			$this->winner = 'Challenger';
			$this->loser = $this->bossName;
		}else{
			if(($key = array_search($player, $this->playersLeft)) !== false){
				unset($this->playersLeft[$key]);
			}
			if(count($this->playersLeft) === 0){
				$this->status = self::STATUS_ENDING;
				$this->winner = $this->bossName;
				$this->loser = 'Challenger';
			}
		}
		$this->eliminated = $player->getName();
	}

	/**
	 */
	public function resetEliminated() : void{
		$this->eliminated = null;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return bool
	 */
	public function isPlayer(MineceitPlayer $player) : bool{
		if($this->boss->equalsPlayer($player)) return true;
		else{
			foreach($this->playersLeft as $temp){
				if($temp->equalsPlayer($player)) return true;
			}
		}
		return false;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return bool
	 */
	public function isBoss(MineceitPlayer $player) : bool{
		if($this->boss->equalsPlayer($player)) return true;
		else return false;
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
}
