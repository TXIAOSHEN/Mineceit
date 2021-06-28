<?php

declare(strict_types=1);

namespace mineceit\player;

use mineceit\arenas\FFAArena;
use mineceit\data\players\AsyncSaveDonateVoteData;
use mineceit\events\duels\MineceitEventDuel;
use mineceit\game\behavior\FishingBehavior;
use mineceit\game\behavior\IFishingBehaviorEntity;
use mineceit\game\behavior\kits\IKitHolderEntity;
use mineceit\game\behavior\kits\KitHolder;
use mineceit\game\entities\bots\AbstractCombatBot;
use mineceit\game\entities\EnderPearl as EPearl;
use mineceit\maitenance\reports\data\ReportInfo;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\parties\events\PartyEvent;
use mineceit\parties\events\types\PartyDuel;
use mineceit\parties\events\types\PartyGames;
use mineceit\parties\MineceitParty;
use mineceit\player\info\clicks\ClicksInfo;
use mineceit\player\info\ClientInfo;
use mineceit\player\info\device\DeviceIds;
use mineceit\player\info\disguise\DisguiseInfo;
use mineceit\player\info\duels\DuelInfo;
use mineceit\player\info\duels\duelreplay\info\DuelReplayInfo;
use mineceit\player\info\ips\IPInfo;
use mineceit\player\info\kits\PlayerKitHolder;
use mineceit\player\info\PlayerReportsInfo;
use mineceit\player\info\ScoreboardInfo;
use mineceit\player\info\settings\LanguageInfo;
use mineceit\player\info\settings\SettingsInfo;
use mineceit\player\info\stats\EloInfo;
use mineceit\player\info\stats\StatsInfo;
use mineceit\player\language\Language;
use mineceit\player\ranks\Rank;
use mineceit\scoreboard\Scoreboard;
use pocketmine\command\CommandSender;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\form\Form;
use pocketmine\item\Consumable;
use pocketmine\item\EnderPearl;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\item\SplashPotion;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class MineceitPlayer extends Player implements
	IFishingBehaviorEntity,
	IKitHolderEntity,
	DeviceIds{

	public const TAG_COMBAT = 'tag.combat';
	public const TAG_NORMAL = 'tag.normal';

	private $tagType = self::TAG_NORMAL;

	/** @var ScoreboardInfo|null */
	private $scoreboardInfo = null;
	/** @var FishingBehavior|null */
	private $fishingBehavior = null;
	/** @var ClientInfo|null */
	private $clientInfo = null;
	/** @var ClicksInfo|null */
	private $clicksInfo = null;
	/** @var PlayerExtensions|null */
	private $extensions = null;
	/** @var IPInfo|null - Stores the info of the ip. */
	private $ipInfo = null;
	/** @var PlayerReportsInfo|null */
	private $reportsInfo = null;
	/** @var SettingsInfo|null */
	private $settingsInfo = null;
	/** @var PlayerKitHolder|null */
	private $kitHolderInfo = null;
	/** @var StatsInfo|null */
	private $statsInfo = null;
	/** @var EloInfo|null */
	private $eloInfo = null;
	/** @var DisguiseInfo|null */
	private $disguiseInfo = null;

	private $formData = [];

	/** @var bool */
	private $lookingAtForm = false;

	/* Combat values */
	private $secsInCombat = 0;
	private $combat = false;

	/* Enderpearl values */
	private $secsEnderpearl = 0;
	private $throwPearl = true;

	/* Gapple values */
	private $secsGap = 0;
	private $eatGap = true;

	/* Arrow values */
	private $secsArr = 0;
	private $arrCD = true;

	/** @var int
	 * Determines how many seconds the player has een on the server.
	 */
	private $currentSecond = 0;

	/* @var FFAArena|null */
	private $currentArena = null;

	/* @var bool */
	private $frozen = false;

	/* @var string */
	private $follower = [];
	private $following = '';

	/* @var array */
	private $duelHistory = [];

	/** @var bool */
	private $dead = false;

	private $spam = 0;
	private $tellSpam = 0;

	/** @var int */
	private $lastTimeHosted = -1;

	/** @var bool */
	private $silentStaff = false;
	/** @var string */
	private $cape = '';
	/** @var string */
	private $stuff = '';
	/** @var string */
	private $potcolor = 'default';
	/** @var string */
	private $tag = '';

	/** @var int */
	private $lastAction = -1;
	/** @var int */
	private $lastActionTime = -1;

	/** @var int */
	private $currentAction = -1;
	/** @var int */
	private $currentActionTime = -1;

	/** @var bool */
	private $loadedData = false;
	private $target = null;

	// ---------------------------------- PLAYER DATA -----------------------------------

	/** @var bool */
	private $muted = false;

	/** @var array|Rank[] */
	private $ranks = [];

	/** @var array|string[] */
	private $validtags = [];

	/** @var array|string[] */
	private $validcapes = [];

	/** @var array|string[] */
	private $validstuffs = [];

	/** @var array|string[] */
	private $bpclaimed = [];

	/** @var bool */
	private $isbuybp = false;

	/** @var string */
	private $guild = '';

	/** @var string */
	private $guildRegion = '';

	/**
	 * Occurs once this player joins the world.
	 */
	public function onJoin() : void{
	}

	/**
	 * @return IPInfo|null
	 *
	 * Gets the ip info of the player.
	 */
	public function getIPInfo() : ?IPInfo{
		$this->ipInfo = $this->ipInfo ?? MineceitCore::getPlayerHandler()
				->getIPManager()->getInfo($this->getAddress());
		return $this->ipInfo;
	}

	/**
	 * @return PlayerReportsInfo|null
	 * Gets the reports info of the player.
	 */
	public function getReportsInfo() : ?PlayerReportsInfo{
		$this->reportsInfo = $this->reportsInfo ?? new PlayerReportsInfo($this);
		return $this->reportsInfo;
	}

	/**
	 * @return Human|null
	 *
	 * Gets the kit holder entity.
	 */
	public function getKitHolderEntity() : ?Human{
		return $this;
	}

	/**
	 * @param MineceitPlayer|CommandSender $player
	 *
	 * @return bool
	 */
	public function equalsPlayer($player) : bool{
		if($player !== null && $player instanceof MineceitPlayer)
			return $player->getName() === $this->getName();
		return false;
		//return $player->getName() === $this->getName() && $player->getId() === $this->getId();
	}

	/**
	 * @return MineceitPlayer|null
	 */
	public function getPlayer() : ?MineceitPlayer{
		return $this;
	}

	public function getFishingEntity() : ?Entity{
		return $this;
	}

	/**
	 * @param bool $animate
	 *
	 * @return bool
	 *
	 * Player uses the fishing rod.
	 */
	public function useRod(bool $animate = false) : bool{

		if($this->getExtensions()->isSpectator() || $this->isImmobile()){
			return false;
		}

		$duel = MineceitCore::getDuelHandler()->getDuel($this);
		$fishingBehaviour = $this->getFishingBehavior();

		if($duel !== null){
			if($duel->isCountingDown()){
				return false;
			}
			$duel->setFishingFor($this, !$fishingBehaviour->isFishing());
		}

		if($fishingBehaviour->isFishing()){
			$fishingBehaviour->stopFishing(false, $animate);
		}else{
			$fishingBehaviour->startFishing($animate);
		}
		return true;
	}

	/**
	 * @return PlayerExtensions|null
	 *
	 * Gets the player extension methods.
	 */
	public function getExtensions() : ?PlayerExtensions{
		$this->extensions = $this->extensions ?? new PlayerExtensions($this);
		return $this->extensions;
	}

	public function getFishingBehavior() : ?FishingBehavior{
		$this->fishingBehavior = $this->fishingBehavior ?? new FishingBehavior($this);
		$this->fishingBehavior->setDamageRod(true);
		return $this->fishingBehavior;
	}

	/**
	 * @param EnderPearl $item
	 * @param bool       $animate
	 *
	 * @return bool
	 *
	 * Throws the enderpearl.
	 */
	public function throwPearl(EnderPearl $item, bool $animate = false) : bool{

		$exec = !$this->getExtensions()->isSpectator() && !$this->isImmobile();

		$duelHandler = MineceitCore::getDuelHandler();
		$duel = $duelHandler->getDuel($this);
		$botHandler = MineceitCore::getBotHandler();
		$bot = $botHandler->getDuel($this);
		$eventManager = MineceitCore::getEventManager()->getEventFromPlayer($this);

		if($exec && $duel !== null){
			$exec = !$duel->isCountingDown();
		}

		if($exec && $bot !== null){
			$exec = !$bot->isCountingDown();
		}

		if($exec && $eventManager !== null && $eventManager->getCurrentDuel() !== null){
			$exec = !($eventManager->getCurrentDuel()->getStatus() === MineceitEventDuel::STATUS_STARTING);
		}

		if($exec){

			$players = $this->getLevel()->getPlayers();

			$tag = Entity::createBaseNBT($this->add(0.0, 0.0, 0.0), $this->getDirectionVector(), (float) $this->yaw, (float) $this->pitch);
			$pearl = Entity::createEntity('EnderPearl', $this->getLevelNonNull(), $tag, $this);
			if($pearl !== null && $pearl instanceof EPearl){
				$event = new ProjectileLaunchEvent($pearl);
				$event->call();

				if(
					$event->isCancelled() ||
					($this->getExtensions()->isSpectator() && $this->isImmobile())
				){
					$pearl->kill();
				}else{
					$pearl->spawnToAll();
				}
			}

			$this->setThrowPearl(false);

			if($animate === true){
				$pkt = new AnimatePacket();
				$pkt->action = AnimatePacket::ACTION_SWING_ARM;
				$pkt->entityRuntimeId = $this->getId();
				$this->getServer()->broadcastPacket($players, $pkt);
			}

			if(!$this->isCreative()){
				$inv = $this->getInventory();
				$count = $item->getCount() - 1;
				if($count === 0) $inv->setItem($inv->getHeldItemIndex(), Item::get(0));
				else $inv->setItem($inv->getHeldItemIndex(), Item::get($item->getId(), $item->getDamage(), $count));
			}
		}

		if($exec && $duel !== null)
			$duel->setThrowFor($this, $item);

		return $exec;
	}

	/**
	 * @param bool $throw
	 * @param bool $message
	 */
	public function setThrowPearl(bool $throw = true, bool $message = true) : void{

		$language = $this->getLanguageInfo()->getLanguage();

		if(!$throw){

			$this->secsEnderpearl = 10;
			$this->getExtensions()->setXpAndProgress(10, 1.0);

			$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::SET_IN_ENDERPEARLCOOLDOWN);

			if($message && $this->throwPearl){
				$this->sendMessage($msg);
			}
		}else{

			$this->secsEnderpearl = 0;
			$this->getExtensions()->setXpAndProgress(0, 0.0);

			$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::REMOVE_FROM_ENDERPEARLCOOLDOWN);

			if($message && !$this->throwPearl){
				$this->sendMessage($msg);
			}
		}

		$this->throwPearl = $throw;
	}

	/**
	 * @return LanguageInfo|null
	 * Gets the language info of the player.
	 */
	public function getLanguageInfo() : ?LanguageInfo{
		return $this->getSettingsInfo()->getLanguageInfo();
	}

	/**
	 * @return SettingsInfo|null
	 * Gets the settings info of the player.
	 */
	public function getSettingsInfo() : ?SettingsInfo{
		$this->settingsInfo = $this->settingsInfo ?? new SettingsInfo($this);
		return $this->settingsInfo;
	}

	/**
	 * @return bool
	 *
	 * Eat the gapple.
	 */
	public function eatGap() : bool{
		if(
			!$this->getExtensions()->isSpectator()
			&& !$this->isImmobile()
		){
			$this->setEatGap(false);
			return true;
		}
		return false;
	}

	/**
	 * @param bool $eat
	 * @param bool $message
	 */
	public function setEatGap(bool $eat = true, bool $message = true) : void{

		$language = $this->getLanguageInfo()->getLanguage();

		if(!$eat){

			$this->secsGap = 7;
			$this->getExtensions()->setXpAndProgress(7, 1.0);

			$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::SET_IN_GAPPLECOOLDOWN);

			if($message && $this->eatGap){
				$this->sendMessage($msg);
			}
		}else{

			$this->secsGap = 0;
			$this->getExtensions()->setXpAndProgress(0, 0.0);

			$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::REMOVE_FROM_GAPPLECOOLDOWN);

			if($message && !$this->eatGap){
				$this->sendMessage($msg);
			}
		}

		$this->eatGap = $eat;
	}

	/**
	 * @param SplashPotion $item
	 * @param bool         $animate
	 *
	 * @return bool
	 *
	 * Throws the potion.
	 */
	public function throwPotion(SplashPotion $item, bool $animate = false) : bool{

		$exec = !$this->getExtensions()->isSpectator() && !$this->isImmobile();

		$duelHandler = MineceitCore::getDuelHandler();
		$duel = $duelHandler->getDuel($this);
		$botHandler = MineceitCore::getBotHandler();
		$bot = $botHandler->getDuel($this);
		$eventManager = MineceitCore::getEventManager()->getEventFromPlayer($this);

		if($exec && $duel !== null){
			$exec = !$duel->isCountingDown();
		}

		if($exec && $bot !== null){
			$exec = !$bot->isCountingDown();
		}

		if($exec && $eventManager !== null && $eventManager->getCurrentDuel() !== null){
			$exec = !($eventManager->getCurrentDuel()->getStatus() === MineceitEventDuel::STATUS_STARTING);
		}

		if($exec){

			$players = $this->getLevel()->getPlayers();
			$item->onClickAir($this, $this->getDirectionVector());

			if($animate){
				$pkt = new AnimatePacket();
				$pkt->action = AnimatePacket::ACTION_SWING_ARM;
				$pkt->entityRuntimeId = $this->getId();
				$this->getServer()->broadcastPacket($players, $pkt);
			}

			if(!$this->isCreative()){
				$inv = $this->getInventory();
				$inv->setItem($inv->getHeldItemIndex(), Item::get(0));
			}
		}

		if($exec && $duel !== null)
			$duel->setThrowFor($this, $item);

		return $exec;
	}

	/**
	 * Updates the tag of the player.
	 */
	public function updateNameTag() : void{
		if($this->frozen){
			$this->setFrozenNameTag();
		}elseif($this->tagType === self::TAG_NORMAL){
			$this->setNormalNameTag();
		}elseif($this->tagType === self::TAG_COMBAT){
			$this->setCombatNameTag();
		}
	}

	public function setFrozenNameTag() : void{
		$rankHandler = MineceitCore::getRankHandler();
		$rankFormat = $rankHandler->formatRanksForTag($this);
		$name = $rankFormat . TextFormat::WHITE . ' [' . TextFormat::AQUA . 'Frozen' . TextFormat::WHITE . ']' .
			"\n" . TextFormat::GRAY . $this->getClientInfo()->getDeviceOS(true) .
			TextFormat::DARK_GRAY . ' - ' . TextFormat::GRAY . $this->getClientInfo()->getInputAtLogin(true);
		$this->setNameTag($name);
	}

	/**
	 * @return ClientInfo|null
	 *
	 * Gets the player's client info.
	 */
	public function getClientInfo() : ?ClientInfo{
		$this->clientInfo = $this->clientInfo ?? new ClientInfo($this);
		return $this->clientInfo;
	}

	/**
	 * Sets the normal name tag.
	 */
	public function setNormalNameTag() : void{
		$rankHandler = MineceitCore::getRankHandler();
		$rankFormat = $rankHandler->formatRanksForTag($this);
		$name = $rankFormat . "\n" . TextFormat::GRAY . $this->getClientInfo()
				->getDeviceOS(true) . TextFormat::DARK_GRAY . ' - ' .
			TextFormat::GRAY . $this->getClientInfo()->getInputAtLogin(true);
		$this->setNameTag($name);
		$this->tagType = self::TAG_NORMAL;
	}

	/**
	 * Sets the combat name tag.
	 */
	public function setCombatNameTag() : void{
		$rankHandler = MineceitCore::getRankHandler();
		$rankFormat = $rankHandler->formatRanksForTag($this);
		$name = $rankFormat . TextFormat::WHITE . ' [' . TextFormat::LIGHT_PURPLE . (int) $this->getHealth() . TextFormat::WHITE . ']' . "\n" .
			TextFormat::LIGHT_PURPLE . 'CPS: ' . TextFormat::WHITE . $this->getClicksInfo()->getCps()
			. TextFormat::LIGHT_PURPLE . ' Ping: ' . TextFormat::WHITE . $this->getPing();
		$this->setNameTag($name);
		$this->tagType = self::TAG_COMBAT;
	}

	/**
	 * @return ClicksInfo|null
	 *
	 * Gets the clicks info of the player.
	 */
	public function getClicksInfo() : ?ClicksInfo{
		$this->clicksInfo = $this->clicksInfo ?? new ClicksInfo($this);
		return $this->clicksInfo;
	}

	/**
	 * @param Form  $form
	 * @param array $addedContent
	 *
	 * Sends the form to a player.
	 */
	public function sendFormWindow(Form $form, array $addedContent = []) : void{
		if(!$this->lookingAtForm){

			$formToJSON = $form->jsonSerialize();
			$content = [];

			if(isset($formToJSON['content']) && is_array($formToJSON['content'])){
				$content = $formToJSON['content'];
			}elseif(isset($formToJSON['buttons']) && is_array($formToJSON['buttons'])){
				$content = $formToJSON['buttons'];
			}

			if(!empty($addedContent)){
				$content = array_replace($content, $addedContent);
			}

			$this->formData = $content;
			$this->lookingAtForm = true;
			$this->sendForm($form);
		}
	}

	/**
	 * @return array
	 *
	 * Officially removes the form data from the player.
	 */
	public function removeFormData() : array{
		$data = $this->formData;
		$this->formData = [];
		return $data;
	}

	/**
	 * @param int   $formId
	 * @param mixed $responseData
	 *
	 * @return bool
	 */
	public function onFormSubmit(int $formId, $responseData) : bool{
		$this->lookingAtForm = false;
		$result = parent::onFormSubmit($formId, $responseData);
		if(isset($this->forms[$formId])){
			unset($this->forms[$formId]);
		}
		return $result;
	}

	/**
	 * @return bool
	 */
	public function isInHub() : bool{
		$level = $this->level;
		$defaultLevel = $this->getServer()->getDefaultLevel();
		$notInArena = $this->currentArena === null;
		return $defaultLevel !== null ? $level->getName() === $defaultLevel->getName() && $notInArena : $notInArena;
	}

	/**
	 * Generally updates the player within the task.
	 */
	public function update() : void{

		if(!$this->throwPearl){
			$this->removeSecInThrow();
			if($this->secsEnderpearl <= 0) $this->setThrowPearl();
		}

		if(!$this->eatGap){
			$this->removeSecInGap();
			if($this->secsGap <= 0) $this->setEatGap();
		}

		if(!$this->arrCD){
			$this->removeSecInArr();
			if($this->secsArr <= 0) $this->setArrowCD();
		}

		if($this->combat){
			$this->secsInCombat--;
			if($this->secsInCombat <= 0){
				$this->setInCombat(false);
			}
		}

		if($this->spam > 0){
			$this->spam--;
		}

		if($this->tellSpam > 0)
			$this->tellSpam--;

		if($this->currentSecond % 5 === 0){
			$this->getScoreboardInfo()->updatePing($this->getPing());
		}

		$this->currentSecond++;
	}

	/**
	 * Updates the seconds in enderpearl cooldown.
	 */
	private function removeSecInThrow() : void{
		$this->secsEnderpearl--;
		$maxSecs = 10;
		$sec = $this->secsEnderpearl;
		if($sec < 0) $sec = 0;
		$percent = floatval($this->secsEnderpearl / $maxSecs);
		if($percent < 0) $percent = 0;
		$this->getExtensions()->setXpAndProgress($sec, $percent);
	}

	/**
	 * Updates the seconds in enderpearl cooldown.
	 */
	private function removeSecInGap() : void{
		$this->secsGap--;
		$maxSecs = 7;
		$sec = $this->secsGap;
		if($sec < 0) $sec = 0;
		$percent = floatval($this->secsGap / $maxSecs);
		if($percent < 0) $percent = 0;
		$this->getExtensions()->setXpAndProgress($sec, $percent);
	}

	/**
	 * Updates the seconds in enderpearl cooldown.
	 */
	private function removeSecInArr() : void{
		$this->secsArr--;
		$maxSecs = 10;
		$sec = $this->secsArr;
		if($sec < 0) $sec = 0;
		$percent = floatval($this->secsArr / $maxSecs);
		if($percent < 0) $percent = 0;
		$this->getExtensions()->setXpAndProgress($sec, $percent);
	}

	/**
	 * @param bool $arrow
	 * @param bool $message
	 */
	public function setArrowCD(bool $arrow = true, bool $message = true) : void{

		$language = $this->getLanguageInfo()->getLanguage();

		if(!$arrow){

			$this->secsArr = 10;
			$this->getExtensions()->setXpAndProgress(10, 1.0);

			$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::SET_IN_ARROWCOOLDOWN);

			if($message && $this->arrCD){
				$this->sendMessage($msg);
			}
		}else{

			$this->secsArr = 0;
			$this->getExtensions()->setXpAndProgress(0, 0.0);

			$give = true;

			if($this->getArena() !== null && $this->getArena()->getName() === 'OITC'){
				foreach($this->getInventory()->getContents() as $item){
					if($item->getId() === 262){
						$give = false;
						break;
					}
				}
			}elseif($this->getPartyEvent() instanceof PartyGames){
				foreach($this->getInventory()->getContents() as $item){
					if($item->getId() === 262){
						$give = false;
						break;
					}
				}
			}else{
				$give = false;
			}

			if($give) $this->getInventory()->addItem(Item::get(262));

			$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::REMOVE_FROM_ARROWCOOLDOWN);

			if($message && !$this->arrCD){
				$this->sendMessage($msg);
			}
		}

		$this->arrCD = $arrow;
	}

	/**
	 * @return FFAArena|null
	 */
	public function getArena() : ?FFAArena{
		return $this->currentArena;
	}

	/**
	 * @return PartyEvent|null
	 *
	 */
	public function getPartyEvent() : ?PartyEvent{
		$eventManager = MineceitCore::getPartyManager()->getEventManager();
		$currentParty = $this->getParty();
		if($currentParty === null){
			return null;
		}
		return $eventManager->getPartyEvent($currentParty);
	}

	/**
	 * @return MineceitParty|null
	 *
	 */
	public function getParty() : ?MineceitParty{
		$partyManager = MineceitCore::getPartyManager();
		return $partyManager->getPartyFromPlayer($this);
	}

	/**
	 * @param bool $combat
	 * @param bool $sendMessage
	 */
	public function setInCombat(bool $combat = true, bool $sendMessage = true) : void{

		$language = $this->getLanguageInfo()->getLanguage();

		if($combat){
			$this->secsInCombat = 10;
			$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::SET_IN_COMBAT);
			if(!$this->combat && $sendMessage){
				$this->setCombatNameTag();
				$this->sendMessage($msg);
			}
		}else{
			$this->secsInCombat = 0;
			$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::REMOVE_FROM_COMBAT);
			if($this->combat && $sendMessage){
				$this->sendMessage($msg);
			}
			if($this->hasTarget()) $this->setnoTarget();
		}

		$this->combat = $combat;
	}

	public function setnoTarget() : void{
		$this->target = null;
		$this->setNormalNameTag();
		if($this->isOnline() && $this->hasTarget()){
			$target = $this->getTarget();
			if($target !== null && $target->isOnline()) $target->setnoTarget();
		}
	}

	public function hasTarget() : bool{
		return $this->target !== null;
	}

	public function getTarget() : ?Player{
		return Server::getInstance()->getPlayerExact((string) $this->target);
	}

	/**
	 * @return ScoreboardInfo|null
	 *
	 * Gets the player's scoreboard information.
	 */
	public function getScoreboardInfo() : ?ScoreboardInfo{
		$this->scoreboardInfo = $this->scoreboardInfo ?? new ScoreboardInfo($this);
		return $this->scoreboardInfo;
	}

	public function setTarget(string $player) : void{
		$this->target = $player;
	}

	/**
	 * Updates the cps of the player.
	 */
	public function updateCps() : void{
		$this->getClicksInfo()->updateCPS();
	}

	/**
	 * Sets the player in normal spam.
	 */
	public function setInSpam() : void{
		$this->spam = 5;
	}

	/**
	 * Sets the player in tell spam.
	 */
	public function setInTellSpam() : void{
		$this->tellSpam = 5;
	}

	/**
	 *
	 * @param bool $command
	 *
	 * @return bool
	 */
	public function canChat(bool $command = false) : bool{

		$spam = $command ? $this->tellSpam : $this->spam;
		if($spam > 0 || $this->isMuted()){
			if(!$this->hasHelperPermissions()){
				$lang = $this->getLanguageInfo()->getLanguage();
				$msg = null;
				if($spam > 0){
					$msg = $lang->getMessage(Language::NO_SPAM);
				}elseif($this->isMuted()){
					$this->sendPopup($lang->getMessage(Language::MUTED));
				}
				if($msg !== null){
					$this->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
				}
				return false;
			}
		}
		return true;
	}

	/**
	 * @return bool
	 *
	 * Determines if the player is muted or not.
	 */
	public function isMuted() : bool{
		return $this->muted;
	}

	/**
	 * @param bool $muted
	 *
	 * Sets the player as muted.
	 */
	public function setMuted(bool $muted) : void{
		if($muted !== $this->muted){
			$lang = $this->getLanguageInfo()->getLanguage();
			if($muted){
				$this->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->getMessage(Language::PLAYER_SET_MUTED));
			}else{
				$this->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->getMessage(Language::PLAYER_SET_UNMUTED));
			}
		}
		$this->muted = $muted;
	}

	/**
	 * @return bool
	 *
	 * Determines if the player has helper permissions.
	 */
	public function hasHelperPermissions() : bool{

		foreach($this->ranks as $rank){
			if($rank->getPermission() === Rank::PERMISSION_HELPER || $rank->getPermission() === Rank::PERMISSION_MOD || $rank->getPermission() === Rank::PERMISSION_ADMIN || $rank->getPermission() === Rank::PERMISSION_OWNER){
				return true;
			}
		}

		return $this->isOp();
	}

	/**
	 * @return bool
	 */
	public function canThrowPearl() : bool{
		return $this->throwPearl;
	}

	/**
	 * @return bool
	 */
	public function canEatGap() : bool{
		return $this->eatGap;
	}

	/**
	 * @return bool
	 */
	public function isInCombat() : bool{
		return $this->combat;
	}

	public function respawn() : void{
		parent::respawn();
		if($this->getSettingsInfo()->isAutoRespawnEnabled() && $this->isInArena()){
			$arena = MineceitCore::getArenas()->getArena($this->getArena()->getName());
			$this->teleportToFFAArena($arena, false);
			$this->dead = false;
			return;
		}

		$this->currentArena = null;
		if($this->getExtensions()->isSpectator()){
			$this->getExtensions()->setFakeSpectator(false);
		}

		if($this->isFlying()){
			$this->setFlying(false);
		}

		if($this->getScoreboardInfo()->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
			$this->getScoreboardInfo()->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
		}
		$level = $this->getServer()->getDefaultLevel();
		if($level !== null){
			$pos = $level->getSpawnLocation();
			$this->teleport($pos);
		}
		MineceitCore::getItemHandler()->spawnHubItems($this, true);
		$this->dead = false;
		$this->setAllowFlight($this->canFlyInLobby());
	}

	/**
	 * @return bool
	 */
	public function isInArena() : bool{
		return $this->currentArena !== null;
	}

	/**
	 * @param string|FFAArena $arena
	 * @param bool            $message
	 *
	 * @return bool
	 *
	 * Teleports the player to the ffa arena.
	 */
	public function teleportToFFAArena($arena, bool $message = true) : bool{
		if($this->isFrozen() || $this->isInEvent()){
			return false;
		}

		$arenaHandler = MineceitCore::getArenas();
		$arena = ($arena instanceof FFAArena) ? $arena : $arenaHandler->getArena($arena);

		if($arena !== null && $arena instanceof FFAArena && $arena->isOpen()){

			$this->getExtensions()->enableFlying(false);
			$this->currentArena = $arena;

			$inventory = $this->getInventory();
			$armorInv = $this->getArmorInventory();

			$inventory->clearAll();
			$armorInv->clearAll();

			$arena->teleportPlayer($this, $message);

			$scoreboardInfo = $this->getScoreboardInfo();
			if($scoreboardInfo->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
				$scoreboardInfo->setScoreboard(Scoreboard::SCOREBOARD_FFA);
			}

			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 * Used to determine if player is getting ssed or not.
	 */
	public function isFrozen() : bool{
		return $this->frozen;
	}

	/**
	 * @param bool $frozen
	 * Used for /freeze to not get confused for immobile.
	 */
	public function setFrozen(bool $frozen = false) : void{
		$this->setImmobile($frozen);
		$this->frozen = $frozen;
	}

	/**
	 * @return bool
	 *
	 * Determines if the player is in an event.
	 */
	public function isInEvent() : bool{
		$eventManager = MineceitCore::getEventManager();
		return $eventManager->getEventFromPlayer($this) !== null;
	}

	/**
	 * @return bool
	 *
	 * Determines whether the player can fly in the lobby.
	 */
	public function canFlyInLobby() : bool{
		return $this->hasBuilderPermissions() || $this->hasHelperPermissions();
	}

	/**
	 * @return bool
	 *
	 * Determines if the player has helper permissions.
	 */
	public function hasBuilderPermissions() : bool{

		foreach($this->ranks as $rank){
			if($rank->getPermission() === Rank::PERMISSION_BUILDER || $rank->getPermission() === Rank::PERMISSION_ADMIN || $rank->getPermission() === Rank::PERMISSION_OWNER){
				return true;
			}
		}

		return $this->isOp();
	}

	/**
	 * @return bool
	 *
	 * Determines if the player has vip permissions.
	 */
	public function hasVipPermissions() : bool{

		foreach($this->ranks as $rank){
			if($rank->getPermission() === Rank::PERMISSION_VIP || $rank->getPermission() === Rank::PERMISSION_VIPPL || $rank->getPermission() === Rank::PERMISSION_CONTENT_CREATOR){
				return true;
			}
		}

		return $this->isOp();
	}

	/**
	 * @return bool
	 *
	 * Determines if the player has vipplus permissions.
	 */
	public function hasVipPlusPermissions() : bool{

		foreach($this->ranks as $rank){
			if($rank->getPermission() === Rank::PERMISSION_VIPPL || $rank->getPermission() === Rank::PERMISSION_CONTENT_CREATOR){
				return true;
			}
		}

		return $this->isOp();
	}

	/**
	 * @return bool
	 *
	 * Determines if the player has creator permissions.
	 */
	public function hasCreatorPermissions() : bool{

		foreach($this->ranks as $rank){
			if($rank->getPermission() === Rank::PERMISSION_CONTENT_CREATOR){
				return true;
			}
		}

		return $this->isOp();
	}

	/**
	 * @return bool
	 *
	 * Determines if the player has owner permissions.
	 */
	public function hasOwnerPermissions() : bool{

		foreach($this->ranks as $rank){
			if($rank->getPermission() === Rank::PERMISSION_OWNER){
				return true;
			}
		}

		return $this->isOp();
	}

	/**
	 * Occurs when players die.
	 */
	public function onDeath() : void{

		if(!$this->dead){

			$this->dead = true;
			$ev = new PlayerDeathEvent($this, $this->getDrops(), null, $this->getXpDropAmount());
			$ev->call();

			$this->setThrowPearl(true, false);
			$this->setEatGap(true, false);
			$this->setArrowCD(true, false);

			$cause = $this->getLastDamageCause();

			$addDeath = false;

			$duel = MineceitCore::getDuelHandler()->getDuel($this);
			$party = $this->getPartyEvent();
			$bot = MineceitCore::getBotHandler()->getDuel($this);
			$event = MineceitCore::getEventManager()->getEventFromPlayer($this);

			$skip = false;

			if($cause !== null){

				$causeAction = $cause->getCause();

				if($causeAction === EntityDamageEvent::CAUSE_SUICIDE
					|| $causeAction === EntityDamageEvent::CAUSE_VOID
					|| $causeAction === EntityDamageEvent::CAUSE_LAVA
					|| $causeAction === EntityDamageEvent::CAUSE_DROWNING
					|| $causeAction === EntityDamageEvent::CAUSE_SUFFOCATION
					|| $causeAction === EntityDamageEvent::CAUSE_FIRE
					|| $causeAction === EntityDamageEvent::CAUSE_FIRE_TICK){

					if($duel !== null){
						$duel->setEnded($duel->getOpponent($this));
						$skip = true;
					}elseif($bot !== null){
						$bot->setEnded(false);
						$skip = true;
					}elseif($this->isInEventDuel()){
						$eventDuel = $event->getCurrentDuel();
						$eventDuel->setResults();
						$skip = true;
					}elseif($party !== null){
						if($party instanceof PartyDuel || $party instanceof PartyGames){
							$party->addSpectator($this);
							$skip = true;
						}
					}
				}

				if(!$skip){

					$duelWinner = null;

					if($cause instanceof EntityDamageByEntityEvent){

						$killer = $cause->getDamager();

						if($killer !== null){
							if($killer instanceof MineceitPlayer){
								$killer->setThrowPearl(true, false);
								$killer->setEatGap(true, false);
								$killer->setArrowCD(true, false);
								if($this->isInArena() && $this->hasTarget()){
									if($killer->getArena() !== null && $killer->getArena()->getName() === $this->getArena()->getName()){
										$this->setInCombat(false, false);
										$killer->setInCombat(false, false);

										$this->sendMessage(TextFormat::LIGHT_PURPLE . $this->getDisplayName() . TextFormat::GRAY . ' was killed by ' . TextFormat::LIGHT_PURPLE . $killer->getDisplayName());
										$killer->sendMessage(TextFormat::LIGHT_PURPLE . $this->getDisplayName() . TextFormat::GRAY . ' was killed by ' . TextFormat::LIGHT_PURPLE . $killer->getDisplayName());

										if(($kit = $killer->getArena()->getKit()) != null){
											$killer->getExtensions()->clearAll();
											$killer->getKitHolder()->setKit($kit);
										}

										$killer->getStatsInfo()->addKill();
										$killer->getStatsInfo()->addCoins(rand(1, 10));
										$killer->getStatsInfo()->addExp(rand(1, 10));
										$addDeath = true;

										if($killer->getSettingsInfo()->isAutoGGEnabled()){
											$format = MineceitCore::getRankHandler()->formatRanksForChat($killer);
											$killer->sendMessage($format . ' gg');
											$this->sendMessage($format . ' gg');
										}
									}
								}elseif($duel !== null && $duel->isPlayer($killer)){
									$duelWinner = $killer;
								}elseif($this->isInEventDuel() && $event->getCurrentDuel()->isPlayer($killer)){
									$duelWinner = $killer;
								}elseif($this->isInEventBoss()){
									$event->getCurrentBoss()->setEliminated($this);
								}elseif($party !== null && ($party instanceof PartyDuel || $party instanceof PartyGames)){
									$party->addSpectator($this);
								}
							}elseif($killer instanceof AbstractCombatBot && $bot !== null){
								$bot->setEnded(false);
							}
						}
					}elseif($cause instanceof EntityDamageByChildEntityEvent){

						$killer = $cause->getDamager();

						if($killer !== null && $killer instanceof MineceitPlayer){
							if($duel !== null && $duel->isPlayer($killer)){
								$duelWinner = $killer;
							}elseif($party !== null && ($party instanceof PartyDuel || $party instanceof PartyGames)){
								$party->addSpectator($this);
							}
						}
					}

					if($duel !== null && $duelWinner !== null){
						$duel->setEnded($duelWinner);

						if($this->hasAdminPermissions() && $duel->isRanked()) $duelWinner->setValidTags(TextFormat::BOLD . TextFormat::DARK_RED . 'X' . TextFormat::RED . 'O' . TextFormat::GOLD . 'O' . TextFormat::YELLOW . 'P' . TextFormat::GREEN . 'E' . TextFormat::DARK_GREEN . 'R' . TextFormat::AQUA . 'M' . TextFormat::DARK_AQUA . 'A' . TextFormat::BLUE . 'N');

						$duelWinner->getStatsInfo()->addCoins(rand(5, 15));
						$duelWinner->getStatsInfo()->addExp(rand(5, 15));
						$duelWinner->getStatsInfo()->addKill();
						$addDeath = true;

						if($duelWinner->getSettingsInfo()->isAutoGGEnabled()){
							$format = MineceitCore::getRankHandler()->formatRanksForChat($duelWinner);
							$duelWinner->sendMessage($format . ' gg');
							$this->sendMessage($format . ' gg');
						}
					}elseif($this->isInEventDuel() && $duelWinner !== null){
						$event->getCurrentDuel()->setResults($duelWinner);
					}

					if($addDeath){
						$this->getStatsInfo()->addDeath();
						$this->getStatsInfo()->addExp(rand(1, 5));
					}
				}
			}

			$this->dead = false;
			$this->getExtensions()->setXpAndProgress(0, 0.0);
			$this->doCloseInventory();

		}
	}

	/**
	 * @return bool
	 *
	 * Determines if the player is in an event duel.
	 */
	public function isInEventDuel() : bool{
		if($this->isInEvent()){
			$eventManager = MineceitCore::getEventManager();
			$event = $eventManager->getEventFromPlayer($this);
			return ($duel = $event->getCurrentDuel()) !== null && $duel->isPlayer($this);
		}
		return false;
	}

	/**
	 * @return PlayerKitHolder|KitHolder|null
	 *
	 * Gets the kit holder info.
	 */
	public function getKitHolder() : ?KitHolder{
		$this->kitHolderInfo = $this->kitHolderInfo ?? new PlayerKitHolder($this);
		return $this->kitHolderInfo;
	}

	/**
	 * @return StatsInfo|null
	 * Gets the general stats info of the player.
	 */
	public function getStatsInfo() : ?StatsInfo{
		$this->statsInfo = $this->statsInfo ?? new StatsInfo($this);
		return $this->statsInfo;
	}

	/**
	 * @return bool
	 *
	 * Determines if the player is in an event boss.
	 */
	public function isInEventBoss() : bool{
		if($this->isInEvent()){
			$eventManager = MineceitCore::getEventManager();
			$event = $eventManager->getEventFromPlayer($this);
			return ($boss = $event->getCurrentBoss()) !== null && $boss->isPlayer($this);
		}
		return false;
	}

	/**
	 * @return bool
	 *
	 * Determines if the player has admin permissions.
	 */
	public function hasAdminPermissions() : bool{

		foreach($this->ranks as $rank){
			if($rank->getPermission() === Rank::PERMISSION_ADMIN || $rank->getPermission() === Rank::PERMISSION_OWNER){
				return true;
			}
		}

		return $this->isOp();
	}

	/**
	 * @return bool
	 */
	public function isInQueue() : bool{
		$duelHandler = MineceitCore::getDuelHandler();
		return $duelHandler->isInQueue($this);
	}

	public function isInBot() : bool{
		$duelHandler = MineceitCore::getBotHandler();
		$duel = $duelHandler->getDuel($this);
		return $duel !== null;
	}

	/**
	 * @param DuelInfo $winner
	 * @param DuelInfo $loser
	 * @param bool     $draw
	 */
	public function addToDuelHistory(DuelInfo $winner, DuelInfo $loser, bool $draw = false) : void{
		$this->duelHistory[] = ['winner' => $winner, 'loser' => $loser, 'draw' => $draw];
	}

	/**
	 * @param DuelReplayInfo $info
	 */
	public function addReplayDataToDuelHistory(DuelReplayInfo $info) : void{
		$length = count($this->duelHistory);
		if($length <= 0){
			return;
		}
		$lastIndex = $length - 1;
		$this->duelHistory[$lastIndex]['replay'] = $info;
	}

	/**
	 * @param int $id
	 *
	 * @return array|null
	 */
	public function getDuelInfo(int $id) : ?array{

		$result = null;

		if(isset($this->duelHistory[$id])){
			$result = $this->duelHistory[$id];
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getDuelHistory() : array{
		return $this->duelHistory;
	}

	/**
	 * @param int $seconds
	 *
	 * Sets the player on fire.
	 */
	public function setOnFire(int $seconds) : void{
		parent::setOnFire($seconds);

		if(($duel = MineceitCore::getDuelHandler()->getDuel($this)) !== null){
			$duel->setOnFire($this, $seconds);
		}
	}

	public function consumeObject(Consumable $consumable) : bool{
		$drink = $consumable instanceof Potion;

		if(($duel = MineceitCore::getDuelHandler()->getDuel($this)) !== null){
			$duel->setConsumeFor($this, $drink);
		}

		return parent::consumeObject($consumable);
	}

	public function addEffect(EffectInstance $effect) : bool{
		if(($duel = MineceitCore::getDuelHandler()->getDuel($this)) !== null){
			$duel->setEffectFor($this, $effect);
		}
		return parent::addEffect($effect);
	}

	/**
	 * @return bool
	 */
	public function isWatchingReplay() : bool{
		$replayManager = MineceitCore::getReplayManager();
		return $replayManager->getReplayFrom($this) !== null;
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function dropItem(Item $item) : bool{
		if(!$this->spawned || !$this->isAlive()){
			return false;
		}

		if($item->isNull()){
			$this->server->getLogger()->debug($this->getName() . " attempted to drop a null item (" . $item . ")");
			return true;
		}

		$motion = $this->getDirectionVector()->multiply(0.4);

		$this->level->dropItem($this->add(0, 1.3, 0), $item, $motion, 40);

		if(($duel = MineceitCore::getDuelHandler()->getDuel($this)) !== null){
			$duel->setDropItem($this, $item, $motion);
		}

		return true;
	}

	/**
	 * @param bool $value
	 */
	public function setSneaking(bool $value = true) : void{
		parent::setSneaking($value);
		if(($duel = MineceitCore::getDuelHandler()->getDuel($this)) !== null){
			$duel->setSneakingFor($this, $value);
		}
	}

	/* public function knockBack(Entity $attacker, float $damage, float $x, float $z, float $base = 0.4): void
	{
		$xzKb = $base;
		$yKb = $base;

		$f = sqrt($x * $x + $z * $z);

		if ($f <= 0) return;

		if (mt_rand() / mt_getrandmax() > $this->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()) {

			if($attacker instanceof Player){
				$attackerKit = $attacker->getKitHolder()->getKit();
				$knockbackInfo = $attackerKit->getKnockbackInfo();
				$xzKb = $knockbackInfo->getHorizontalKb();
				$yKb = $knockbackInfo->getVerticalKb();
			}

			$f = 1 / $f;

			$motion = clone $this->motion;

			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $xzKb;
			$motion->y += $yKb;
			$motion->z += $z * $f * $xzKb;

			if ($motion->y > $yKb) {
				$motion->y = $yKb;
			}

			$this->setMotion($motion);
		}
	} */

	/**
	 * Gets the information of the player.
	 *
	 * @param Language|null $lang
	 *
	 * @return array|string[]
	 */
	public function getInfo(Language $lang = null) : array{

		$levelName = $this->level->getName();
		$pRanks = $this->getRanks();

		$ranks = [];
		$locale = $lang != null ? $lang->getLocale() : null;

		foreach($pRanks as $rank){
			$ranks[] = $rank->getName();
		}

		$ranksStr = implode(", ", $ranks);
		if(strlen($ranksStr) <= 0){
			$ranksStr = $lang->getMessage(Language::NONE);
		}

		//$aliases = $this->getAliases();

		$disguiseInfo = $this->getDisguiseInfo();
		return [
			"Name" => $this->getName() . ($disguiseInfo->isDisguised()
					? " (Disguise -> {$disguiseInfo->getDisguiseData()->getDisplayName()})" : ""),
			"IP" => $this->getAddress(),
			"Ping" => $this->getPing(),
			"Version" => $this->getClientInfo()->getVersion(),
			"Device OS" => $this->getClientInfo()->getDeviceOS(true),
			"Device Model" => $this->getClientInfo()->getDeviceModel(),
			"Device ID" => $this->getClientInfo()->getRawDeviceId(),
			"UI" => $this->getClientInfo()->getUIProfile(),
			"Ranks" => $ranksStr,
			"Controls" => $this->getClientInfo()->getInputAtLogin(true),
			//"Aliases" => implode(", ", $aliases),
			"Level" => $levelName,
			"Language" => $this->getLanguageInfo()->getLanguage()
				->getNameFromLocale($locale ?? Language::ENGLISH_US),
			"Guild:" => $this->getGuild() . " ({$this->getGuildRegion()})",
			"Coins" => $this->getStatsInfo()->getCoins(),
			"Shard" => $this->getStatsInfo()->getShards(),
			"Exp" => $this->getStatsInfo()->getExp(),
			"Battlepass" => $this->isBuyBattlePass() ? "ElitePass" : "Free",
		];
	}

	/**
	 *
	 * Gets the ranks of the player.
	 *
	 * @param bool $asString
	 *
	 * @return array|Rank[]|string[]
	 */
	public function getRanks(bool $asString = false) : array{

		if($asString){

			$ranks = [];

			foreach($this->ranks as $rank)
				$ranks[] = $rank->getLocalName();

			return $ranks;
		}

		return $this->ranks;
	}

	/**
	 * Sets the ranks of the player.
	 *
	 * @param array|Rank[] $ranks
	 */
	public function setRanks($ranks = []) : void{

		$size = count($ranks);

		if($size > 0){

			$this->ranks = $ranks;
			$this->claimRewards();
		}
	}

	/**
	 * @return DisguiseInfo|null
	 * Gets the disguise info for the player.
	 */
	public function getDisguiseInfo() : ?DisguiseInfo{
		$this->disguiseInfo = $this->disguiseInfo ?? new DisguiseInfo($this);
		return $this->disguiseInfo;
	}

	/**
	 * @return string $guild
	 */
	public function getGuild() : string{
		return $this->guild;
	}

	/**
	 * @param string $guildName
	 */
	public function setGuild(string $guildName) : void{
		$this->guild = $guildName;
	}

	/**
	 * @return string $guild
	 */
	public function getGuildRegion() : string{
		return $this->guildRegion;
	}

	/**
	 * @param string $region
	 */
	public function setGuildRegion(string $region) : void{
		$this->guildRegion = $region;
	}

	/**
	 * @return bool
	 *
	 * Is the buy bp notification enabled.
	 */
	public function isBuyBattlePass() : bool{
		return $this->isbuybp;
	}

	/**
	 * @return bool
	 *
	 * Determines whether the player can build.
	 */
	public function canBuild() : bool{
		return $this->hasBuilderPermissions()
			&& $this->getSettingsInfo()->getBuilderModeInfo()->canBuild();
	}

	/**
	 * @return int
	 *
	 * Gets the report permissions of the players.
	 */
	public function getReportPermissions() : int{

		if($this->hasModPermissions()){
			return ReportInfo::PERMISSION_MANAGE_REPORTS;
		}elseif($this->hasHelperPermissions()){
			return ReportInfo::PERMISSION_VIEW_ALL_REPORTS;
		}

		return ReportInfo::PERMISSION_NORMAL;
	}

	/**
	 * @return bool
	 *
	 * Determines if the player has mod permissions.
	 */
	public function hasModPermissions() : bool{

		foreach($this->ranks as $rank){
			if($rank->getPermission() === Rank::PERMISSION_MOD || $rank->getPermission() === Rank::PERMISSION_ADMIN || $rank->getPermission() === Rank::PERMISSION_OWNER){
				return true;
			}
		}

		return $this->isOp();
	}

	/**
	 * Removes the rank of a player.
	 *
	 * @param string $rank
	 */
	public function removeRank(string $rank) : void{

		$keys = array_keys($this->ranks);

		foreach($keys as $key){

			$theRank = $this->ranks[$key];

			if($theRank->getLocalName() === $rank){
				unset($this->ranks[$key]);
				break;
			}
		}

		if(!$this->canFlyInLobby() && $this->getAllowFlight()){
			$this->getExtensions()->enableFlying(false);
		}
	}

	/**
	 *
	 * Gets the bpclaimed of the player.
	 *
	 *
	 * @return array|string[]
	 */
	public function getBpClaimed() : array{
		return $this->bpclaimed;
	}

	/**
	 * Sets the bpclaimed of the player.
	 *
	 * @param int $n
	 */
	public function setBpClaimed(int $n) : void{
		# $this->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' You recive Battle Pass reward : ' . TextFormat::LIGHT_PURPLE . $n);
		$this->bpclaimed[$n] = '1';
	}

	/**
	 * Is the bpclaimed of the player.
	 *
	 * @param int $n
	 *
	 * @return bool
	 */
	public function isBpClaimed(int $n) : bool{
		if(isset($this->bpclaimed[$n]))
			return $this->bpclaimed[$n] === '1';
		return false;
	}

	/**
	 * @return array
	 *
	 * Gets the aliases of the player.
	 */
	public function getAliases() : array{
		$playerHandler = MineceitCore::getPlayerHandler();
		$aliasManager = $playerHandler->getAliasManager();
		return $aliasManager->getAliases($this);
	}

	/**
	 * @param bool $enabled
	 *
	 * Enables/Disables the buy bp notification.
	 */
	public function setBuyBattlePass(bool $enabled) : void{
		$this->isbuybp = $enabled;
	}

	/**
	 * @param bool $enabled
	 *
	 * Enables/Disables the join and leave for staff notification.
	 */
	public function setSilentStaffEnabled(bool $enabled) : void{
		$this->silentStaff = $enabled;
	}

	/**
	 * @return bool
	 *
	 * Is the join and leave for staff notification enabled.
	 */
	public function isSilentStaffEnabled() : bool{
		return $this->silentStaff;
	}

	/**
	 * @param array               $items
	 * @param int                 $price
	 * @param string              $type
	 * @param MineceitPlayer|null $gift
	 *
	 * Gacha box function
	 */
	public function gachaBox(array $items, int $price, string $type, MineceitPlayer $gift = null) : void{
		if($this->getStatsInfo()->getCoins() >= $price){

			$already = false;

			$reward = null;
			$range = 0;
			foreach($items as $item)
				$range = $range + $item[1];

			$percents = rand(1, $range);
			$gacha = 0;

			foreach($items as $item){
				if($percents > $gacha && $percents <= $gacha + $item[1]){
					$reward = $item[0];
					$percents = $item[1];
					break;
				}
				$gacha = $gacha + $item[1];
			}

			if(is_string($reward)){

				$giftmsg = null;

				if($gift !== null){
					$gift->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' ' . $this->getDisplayName() . ' sent you a gift.');
				}

				switch($type){
					case "tag":
						if($gift === null){
							if(array_search($reward, $this->getValidTags()) !== false){
								$this->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' You already got ' . TextFormat::LIGHT_PURPLE . $reward);
								$already = true;
							}else{
								$this->setValidTags((string) $reward);
							}
						}else{
							if(array_search($reward, $gift->getValidTags()) !== false){
								$gift->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' You already got ' . TextFormat::LIGHT_PURPLE . $reward);
								$already = true;
							}else{
								$gift->setValidTags((string) $reward);
							}
						}
						break;
					case "cape":
						if($gift === null){
							if(array_search($reward, $this->getValidCapes()) !== false){
								$this->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' You already got ' . TextFormat::LIGHT_PURPLE . $reward);
								$already = true;
							}else{
								$this->setValidCapes($reward);
							}
						}else{
							if(array_search($reward, $gift->getValidCapes()) !== false){
								$gift->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' You already got ' . TextFormat::LIGHT_PURPLE . $reward);
								$already = true;
							}else{
								$gift->setValidCapes($reward);
							}
						}
						break;
					case "artifact":
						if($gift === null){
							if(array_search($reward, $this->getValidStuffs()) !== false){
								$this->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' You already got ' . TextFormat::LIGHT_PURPLE . $reward);
								$already = true;
							}else{
								$this->setValidStuffs($reward);
							}
						}else{
							if(array_search($reward, $gift->getValidStuffs()) !== false){
								$gift->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' You already got ' . TextFormat::LIGHT_PURPLE . $reward);
								$already = true;
							}else{
								$gift->setValidStuffs($reward);
							}
						}
						break;
				}

				if($gift !== null) $this->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . " {$gift->getDisplayName()} got " . TextFormat::LIGHT_PURPLE . $reward);
				if(($percents / $range) * 100 <= 5){
					$name = ($gift === null) ? $this->getDisplayName() : $gift->getDisplayName();
					MineceitUtil::broadcastMessage(TextFormat::LIGHT_PURPLE . $name . TextFormat::GOLD . " has obtained a rare $type " . TextFormat::LIGHT_PURPLE . $reward);
				}
			}

			$this->getStatsInfo()->removeCoins($price);
			if($already) $this->getStatsInfo()->addShards((int) ($price / 2));
		}else{
			$this->sendMessage(MineceitUtil::getPrefix() . TextFormat::RED . ' Not enough coin.');
		}
	}

	/**
	 *
	 * Gets the tags of the player.
	 *
	 *
	 * @return array|string[]
	 */
	public function getValidTags() : array{
		return $this->validtags;
	}

	/**
	 * Sets the tag of the player.
	 *
	 * @param string $tag
	 */
	public function setValidTags(string $tag) : void{
		$key = array_search($tag, $this->validtags);
		if($key === false){
			$this->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' You got a new tag : ' . $tag);
			$this->validtags[] = $tag;
		}
	}

	/**
	 *
	 * Gets the capes of the player.
	 *
	 *
	 * @return array|string[]
	 */
	public function getValidCapes() : array{
		return $this->validcapes;
	}

	/**
	 * Sets the cape of the player.
	 *
	 * @param string $cape
	 */
	public function setValidCapes(string $cape) : void{
		$key = array_search($cape, $this->validcapes);
		if($key === false){
			$this->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' You got a new cape : ' . TextFormat::LIGHT_PURPLE . $cape);
			$this->validcapes[] = $cape;
		}
	}

	/**
	 *
	 * Gets the stuffs of the player.
	 *
	 *
	 * @return array|string[]
	 */
	public function getValidStuffs() : array{
		return $this->validstuffs;
	}

	/**
	 * Sets the stuff of the player.
	 *
	 * @param string $stuff
	 */
	public function setValidStuffs(string $stuff) : void{
		$key = array_search($stuff, $this->validstuffs);
		if($key === false){
			$this->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' You got a new artifacts : ' . TextFormat::LIGHT_PURPLE . $stuff);
			$this->validstuffs[] = $stuff;
		}
	}

	/**
	 * @param String $item
	 * @param int    $price
	 * @param string $type
	 *
	 * buy single tag using shard function
	 */
	public function BuyShard(string $item, int $price, string $type) : void{
		if($this->getStatsInfo()->getShards() >= $price){

			$already = false;

			switch($type){
				case "tag":
					if(array_search($item, $this->getValidTags()) !== false){
						$already = true;
					}else{
						$this->setValidTags($item);
					}
					break;
				case "cape":
					if(array_search($item, $this->getValidCapes()) !== false){
						$already = true;
					}else{
						$this->setValidCapes($item);
					}
					break;
				case "artifact":
					if(array_search($item, $this->getValidStuffs()) !== false){
						$already = true;
					}else{
						$this->setValidStuffs($item);
					}
					break;
			}

			if($already){
				$this->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' You already got ' . $item);
			}else{
				$this->getStatsInfo()->removeShards($price);
			}
		}else{
			$this->sendMessage(MineceitUtil::getPrefix() . TextFormat::RED . 'Not enough Shards.');
		}
	}

	/**
	 * @return string
	 *
	 */
	public function getPotColor() : string{
		return $this->potcolor;
	}

	/**
	 * @param string $potcolor
	 *
	 * @return string
	 */
	public function setPotColor(string $potcolor) : string{
		return $this->potcolor = $potcolor;
	}

	/**
	 * Loads the player's data.
	 *
	 * @param array $data
	 */
	public function loadData($data = []) : void{
		// Initializes the stats & elo.
		$this->getStatsInfo()->init($data);
		$this->getEloInfo()->init($data);
		$this->getSettingsInfo()->init($data);
		$this->getDisguiseInfo()->init($data);

		if(isset($data['muted']))
			$this->muted = (bool) $data['muted'];
		if(isset($data['silent-staff']))
			$this->silentStaff = (bool) $data['silent-staff'];

		if(isset($data['cape']))
			$this->cape = (string) $data['cape'];
		if(isset($data['stuff']))
			$this->stuff = (string) $data['stuff'];
		if(isset($data['potcolor']))
			$this->potcolor = (string) $data['potcolor'];
		if(isset($data['tag']))
			$this->tag = (string) $data['tag'];
		if(isset($data['ranks'])){
			$ranks = $data['ranks'];
			$size = count($ranks);
			$rankHandler = MineceitCore::getRankHandler();
			$result = [];
			if($size > 0){
				foreach($ranks as $rankName){
					$rankName = strval($rankName);
					$rank = $rankHandler->getRank($rankName);
					if($rank !== null)
						$result[] = $rank;
				}
			}else{
				$defaultRank = $rankHandler->getDefaultRank();
				if($defaultRank !== null)
					$result = [$defaultRank];
			}
			$this->ranks = $result;
		}

		if(isset($data['validtags'])){
			$validtags = (string) $data['validtags'];

			$A = explode('|', $validtags);
			$B = explode(',', $validtags);

			if(count($A) >= count($B)) $this->validtags = $A;
			else $this->validtags = $B;
		}

		if(isset($data['validcapes'])){
			$validcapes = (string) $data['validcapes'];
			$this->validcapes = explode(',', $validcapes);
		}

		if(isset($data['validstuffs'])){
			$validstuffs = (string) $data['validstuffs'];
			$this->validstuffs = explode(',', $validstuffs);
		}

		if(isset($data['bpclaimed'])){
			$bpclaimed = (string) $data['bpclaimed'];
			$this->bpclaimed = explode(',', $bpclaimed);
		}

		if(isset($data['isbuybp']))
			$this->isbuybp = (bool) $data['isbuybp'];

		if(isset($data['guild'])){
			if((string) $data['guild'] === ',' || (string) $data['guild'] === ''){
				$this->guildRegion = '';
				$this->guild = '';
			}else{
				$guildData = explode(',', (string) $data['guild']);
				$this->guildRegion = (string) $guildData[0];
				$this->guild = (string) $guildData[1];
			}
		}

		$this->lastTimeHosted = -1;
		if(isset($data['lasttimehosted'])){
			$this->lastTimeHosted = (int) $data['lasttimehosted'];
		}

		if(MineceitCore::MYSQL_ENABLED){
			if(isset($data['vote']) && (int) $data['vote'] !== 0){
				$time = time();
				if($time > ((int) $data['vote'] + 86400)){
					foreach($this->ranks as $rank){
						if($rank->getName() === "Voter"){
							$this->setRanks([MineceitCore::getRankHandler()->getDefaultRank()]);
						}
					}
					if($this->getTag() === TextFormat::BOLD . TextFormat::DARK_GREEN . 'VOTER') $this->setTag('');
					if($this->getCape() === 'Z') $this->setCape('');
					if($this->getStuff() === 'Glasses') $this->setStuff('');
					$this->removeValidTags(TextFormat::BOLD . TextFormat::DARK_GREEN . 'VOTER');
					$this->removeValidCapes('Z');
					$this->removeValidStuffs('Glasses');
				}
			}
		}

		if($this->getSettingsInfo()->isScoreboardEnabled()){
			$this->getScoreboardInfo()->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
		}

		MineceitCore::getItemHandler()->spawnHubItems($this);

		$this->setNormalNameTag();

		$this->setAllowFlight($this->canFlyInLobby());

		if(($msg = MineceitUtil::getJoinMessage($this)) !== '') $this->server->broadcastMessage($msg);

		$this->setImmobile(false);

		$this->setCosmetic();

		$this->getKitHolder()->loadEditedKits();

		$auctionHouse = MineceitCore::getAuctionHouse();
		if($auctionHouse->isRunning()){
			$auctionHouse->checkCoinBack($this);
			$auctionHouse->checkShardBack($this);
			$auctionHouse->checkItemBack($this);
		}

		$this->claimRewards();
		$this->loadedData = true;
	}

	/**
	 * @return EloInfo|null
	 * Gets the elo info of the player.
	 * Separated elo from the stats because it would be easier to
	 * abstract the MYSQL Data.
	 */
	public function getEloInfo() : ?EloInfo{
		$this->eloInfo = $this->eloInfo ?? new EloInfo($this);
		return $this->eloInfo;
	}

	/**
	 * @return string
	 *
	 */
	public function getTag() : string{
		return $this->tag;
	}

	/**
	 * @param string $tag
	 *
	 * Set player's tag
	 */
	public function setTag(string $tag) : void{
		$this->tag = $tag;
	}

	/**
	 * @return string
	 *
	 */
	public function getCape() : string{
		return $this->cape;
	}

	/**
	 * @param string $cape
	 *
	 * @return string
	 */
	public function setCape(string $cape) : string{
		return $this->cape = $cape;
	}

	/**
	 * @return string
	 *
	 */
	public function getStuff() : string{
		return $this->stuff;
	}

	/**
	 * @param string $stuff
	 *
	 * @return string
	 */
	public function setStuff(string $stuff) : string{
		return $this->stuff = $stuff;
	}

	/**
	 * Removes the tag of a player.
	 *
	 * @param string $tag
	 */
	public function removeValidTags(string $tag) : void{
		$key = array_search($tag, $this->validtags);
		if($key !== false)
			unset($this->validtags[$key]);
	}

	/**
	 * Removes the cape of a player.
	 *
	 * @param string $cape
	 */
	public function removeValidCapes(string $cape) : void{
		$key = array_search($cape, $this->validcapes);
		if($key !== false)
			unset($this->validcapes[$key]);
	}

	/**
	 * Removes the stuff of a player.
	 *
	 * @param string $stuff
	 */
	public function removeValidStuffs(string $stuff) : void{
		$key = array_search($stuff, $this->validstuffs);
		if($key !== false)
			unset($this->validstuffs[$key]);
	}

	/**
	 *
	 */
	public function setCosmetic() : void{
		$cosmetic = MineceitCore::getCosmeticHandler();
		if(!$this->getDisguiseInfo()->isDisguised()){
			if($this->getStuff() !== ""){
				$cosmetic->setSkin($this, $this->getStuff());
			}else if($this->getCape() !== ""){
				$capedata = $cosmetic->getCapeData($this->getCape());
				$this->setSkin(new Skin($this->getSkin()->getSkinId(), $this->getSkin()->getSkinData(), $capedata, $this->getSkin()->getGeometryName() !== 'geometry.humanoid.customSlim' ? 'geometry.humanoid.custom' : $this->getSkin()->getGeometryName(), ''));
				$this->sendSkin();
			}else{
				$this->setSkin(new Skin($this->getSkin()->getSkinId(), $this->getSkin()->getSkinData(), '', $this->getSkin()->getGeometryName() !== 'geometry.humanoid.customSlim' ? 'geometry.humanoid.custom' : $this->getSkin()->getGeometryName(), ''));
				$this->sendSkin();
			}
		}else{
			$this->getDisguiseInfo()->setDisguised(true, true);
		}
	}

	public function claimRewards(){
		foreach($this->ranks as $rank){
			switch($rank){
				case "Donator":
					$this->setValidTags(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'SUPPORTER');
					$this->setValidTags(TextFormat::BOLD . TextFormat::RED . 'CONTRIBUTOR');
					$this->setValidTags(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'SUBSCRIBER');
					$this->setValidCapes('Lunar');
					$this->setValidStuffs('Adidas');
					$this->setValidStuffs('Boxing');
					break;
				case "DonatorPlus":
					$this->setValidTags(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'SUPPORTER');
					$this->setValidTags(TextFormat::BOLD . TextFormat::RED . 'CONTRIBUTOR');
					$this->setValidTags(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'SUBSCRIBER');
					$this->setValidTags(TextFormat::BOLD . TextFormat::RED . 'BEQUEATH');
					$this->setValidCapes('Lunar');
					$this->setValidCapes('Galaxy');
					$this->setValidStuffs('Adidas');
					$this->setValidStuffs('Boxing');
					$this->setValidStuffs('Nike');
					break;
				case "Booster":
					$this->setValidTags(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'SUBSCRIBER');
					$this->setValidCapes('Galaxy');
					$this->setValidStuffs('Nike');
					break;
				case "Media":
				case "Famous":
					$this->setValidTags(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'ZE' . TextFormat::WHITE . 'QA');
					$this->setValidTags(TextFormat::BOLD . TextFormat::DARK_RED . 'C' . TextFormat::RED . 'L' . TextFormat::GOLD . 'O' . TextFormat::YELLOW . 'U' . TextFormat::GREEN . 'T' . TextFormat::DARK_GREEN . 'E' . TextFormat::AQUA . 'D');
					$this->setValidCapes('Pepe');
					$this->setValidStuffs('LouisVuitton');
					break;
			}

			if($rank->getName() === "Donator" || $rank->getName() === "DonatorPlus" || $rank->getName() === "Booster"){
				$time = time();
				$dailycoins = false;
				if(MineceitCore::MYSQL_ENABLED){
					if(isset($data['donate'])){
						if($time > ((int) $data['donate'] + 86400)){
							$task = new AsyncSaveDonateVoteData($this, true);
							$this->server->getAsyncPool()->submitTask($task);
							$dailycoins = true;
						}
					}
				}else{
					$donatordata = new Config(MineceitCore::getDataFolderPath() . "donator.yml", Config::YAML);
					if($time > ($donatordata->get($this->getName()) + 86400)){
						$donatordata->set($this->getName(), $time);
						$donatordata->save();
						$dailycoins = true;
					}
				}

				if($dailycoins){
					switch($rank){
						case "Donator":
							$this->getStatsInfo()->addCoins(300);
							break;
						case "DonatorPlus":
							$this->getStatsInfo()->addCoins(500);
							break;
						case "Booster":
							$this->getStatsInfo()->addCoins(100);
							break;
					}
					$this->sendMessage(MineceitUtil::getPrefix() . TextFormat::GRAY . ' You have received ' . TextFormat::YELLOW . 'a daily reward.');
				}
			}
		}

		if($this->hasHelperPermissions() || $this->hasBuilderPermissions()){
			$this->setValidTags(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'ZE' . TextFormat::WHITE . 'QA');
			$this->setValidCapes('Chimera');
			$this->setValidStuffs('Gudoudame');
		}
	}

	/**
	 * @return bool
	 *
	 * Determines whether the player has loaded their data or not.
	 */
	public function hasLoadedData() : bool{
		return $this->loadedData;
	}

	/**
	 * @return int
	 *
	 * Gets the last time the player hosted.
	 */
	public function getLastTimeHosted() : int{
		return $this->lastTimeHosted;
	}

	/**
	 * @param int $time
	 *
	 * Sets the last time the player hosted.
	 */
	public function setLastTimeHosted(int $time) : void{
		$this->lastTimeHosted = $time;
	}

	/**
	 * @return array
	 *
	 * Gets the follower.
	 */
	public function getFollower() : array{
		$players = [];
		foreach($this->follower as $name){
			if(($player = MineceitUtil::getPlayerExact($name, true)) !== null && $player instanceof MineceitPlayer){
				$players[] = $player;
			}else{
				if(isset($this->follower[$name])) unset($this->follower[$name]);
			}
		}
		return $players;
	}

	/**
	 * @param string $name
	 * @param bool   $follow
	 *
	 * Sets the follower.
	 */
	public function setFollower(string $name = '', bool $follow = true) : void{
		if($follow) $this->follower[$name] = $name;
		else{
			if(isset($this->follower[$name])) unset($this->follower[$name]);
		}
	}

	/**
	 * @return bool
	 *
	 * Gets the followed.
	 */
	public function isFollowed() : bool{
		return count($this->follower) !== 0;
	}

	/**
	 * @return string
	 *
	 * Gets the followed.
	 */
	public function getFollowing() : string{
		return $this->following;
	}

	/**
	 * @param string $name
	 *
	 * Sets the followed.
	 */
	public function setFollowing(string $name = '') : void{
		if($name === ''){
			$this->reset(true, true);
			MineceitCore::getItemHandler()->spawnHubItems($this);
			$this->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::YELLOW . 'Unfollow: ' . $this->following);
		}else{
			$this->setGamemode(Player::SPECTATOR);
			$this->getExtensions()->clearAll();
			$this->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . 'Following: ' . $name . ' | /follow to unfollow.');
		}
		$this->following = $name;
	}

	/**
	 * @param bool $clearInv
	 * @param bool $teleportSpawn
	 */
	public function reset(bool $clearInv = false, bool $teleportSpawn = false) : void{

		$this->currentArena = null;

		if($this->getExtensions()->isSpectator()){
			$this->getExtensions()->setFakeSpectator(false);
		}

		$this->setGamemode(0);
		$this->getKitHolder()->clearKit();
		$this->extinguish();

		if($this->isImmobile()){
			$this->setImmobile(false);
		}

		if($this->combat){
			$this->setInCombat(false, false);
		}

		if($this->throwPearl){
			$this->setThrowPearl();
		}

		if($this->eatGap){
			$this->setEatGap();
		}

		if($this->arrCD){
			$this->setArrowCD();
		}

		if($clearInv){
			$this->getExtensions()->clearAll();
		}

		if($teleportSpawn){
			$level = $this->getServer()->getDefaultLevel();
			if($level !== null){
				$pos = $level->getSpawnLocation();
				$this->teleport($pos);
			}
			$this->setAllowFlight($this->canFlyInLobby());
		}
	}

	/**
	 * Extinguishes the player.
	 */
	public function extinguish() : void{
		parent::extinguish();
		if(($duel = MineceitCore::getDuelHandler()->getDuel($this)) !== null){
			$duel->setOnFire($this, false);
		}
	}

	/**
	 * @return bool
	 *
	 * Gets the followed.
	 */
	public function isFollowing() : bool{
		return $this->following !== '';
	}

	/**
	 * @return bool
	 *
	 * Determines whether the player can duel.
	 */
	public function canDuel() : bool{
		return !$this->isFrozen() && !$this->isInEvent() && !$this->isInParty() && !$this->isInDuel() && !$this->isInArena() && !$this->isADuelSpec();
	}

	/**
	 * @return bool
	 *
	 * Determines if the player is in a party.
	 */
	public function isInParty() : bool{
		$partyManager = MineceitCore::getPartyManager();
		return $partyManager->getPartyFromPlayer($this) !== null;
	}

	/**
	 * @return bool
	 *
	 * Determines if the player is in a duel.
	 */
	public function isInDuel() : bool{
		$duelHandler = MineceitCore::getDuelHandler();
		$duel = $duelHandler->getDuel($this);
		return $duel !== null;
	}

	/**
	 * @return bool
	 */
	public function isADuelSpec() : bool{
		$duelHandler = MineceitCore::getDuelHandler();
		$duel = $duelHandler->getDuelFromSpec($this);
		return $duel !== null;
	}

	/**
	 * @param int $action
	 *
	 * Tracks the player's current action.
	 */
	public function setAction(int $action) : void{
		$currentTime = round(microtime(true) * 1000);

		if($this->currentAction === -1){

			$this->currentAction = $action;
			$this->currentActionTime = $currentTime;
			$this->lastAction = PlayerActionPacket::ACTION_ABORT_BREAK;
			$this->lastActionTime = 0;
		}else{

			$this->lastAction = $this->currentAction;
			$this->lastActionTime = $this->currentActionTime;

			$this->currentAction = $action;
			$this->currentActionTime = $currentTime;
		}
	}

	/**
	 * @param int $tickDiff
	 *
	 * Does the food tick.
	 */
	protected function doFoodTick(int $tickDiff = 1) : void{
		$this->setFood($this->getMaxFood());
		$this->setSaturation($this->getExtensions()->getMaxSaturation());
	}
}
