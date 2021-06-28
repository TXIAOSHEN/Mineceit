<?php

declare(strict_types=1);

namespace mineceit\player\info\settings;


use mineceit\data\IDataHolder;
use mineceit\data\mysql\MysqlRow;
use mineceit\game\FormUtil;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\misc\AbstractListener;
use mineceit\player\MineceitPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

class SettingsInfo extends AbstractListener implements IDataHolder{

	// The swish sounds.
	const SWISH_SOUNDS = [
		41 => true,
		42 => true,
		43 => true
	];

	/** @var LanguageInfo */
	private $languageInfo;

	/** @var bool - Determines if scoreboard is enabled. */
	private $scoreboardEnabled = true;
	/** @var bool - Translates the player messages. */
	private $translateMessages = false;
	/** @var bool - Determines if the swish sound is enabled. */
	private $swishSoundEnabled = false;
	/** @var bool - Determines is the player has a cps popup. */
	private $cpsPopup = false;
	/** @var bool - Determines if the player has pe only queues enabled. */
	private $peOnlyQueues = false;
	/** @var bool - Determines whether auto respawn is enabled. */
	private $autoRespawnEnabled = false;
	/** @var bool - Determines whether auto sprint is enabled. */
	private $autoSprintEnabled = false;
	/** @var bool - Determines whether there are more crits enabled. */
	private $moreCritsEnabled = false;
	/** @var bool - Determines if there is a lightning death particle enabled. */
	private $lightningDeathEnabled = false;
	/** @var bool - Determines if blood is enabled. */
	private $bloodEnabled = false;
	/** @var bool - Determines if autogg is enabled. */
	private $autoGGEnabled = false;

	/** @var BuilderModeInfo - The builder mode info for the player. */
	private $builderModeInfo;

	/** @var string */
	private $playerName;
	/** @var MineceitPlayer */
	private $player;

	public function __construct(MineceitPlayer $player){
		parent::__construct(MineceitCore::getInstance());
		$this->player = $player;
		$this->playerName = $player->getName();
		$this->languageInfo = new LanguageInfo($player);
		$this->builderModeInfo = new BuilderModeInfo($player);
	}

	public function getBuilderModeInfo() : ?BuilderModeInfo{
		return $this->builderModeInfo;
	}

	/**
	 * @param array $data
	 *
	 * Exports the data to an array.
	 */
	public function export(array &$data) : void{
		$data['scoreboards-enabled'] = $this->scoreboardEnabled;
		$data['pe-only'] = $this->scoreboardEnabled;
		$data['translate'] = $this->translateMessages;
		$data['swish-sound'] = $this->swishSoundEnabled;
		$data['cps-popup'] = $this->cpsPopup;
		$data['autorespawn'] = $this->autoRespawnEnabled;
		$data['autosprint'] = $this->autoSprintEnabled;
		$data['morecrit'] = $this->moreCritsEnabled;
		$data['lightning'] = $this->lightningDeathEnabled;
		$data['blood'] = $this->bloodEnabled;
		$data['autogg'] = $this->autoGGEnabled;
	}

	/**
	 * @param array $data
	 *
	 * Initializes the data.
	 */
	public function init(array &$data) : void{
		$this->builderModeInfo->init($data);
		$this->loadLanguage($data);

		MineceitUtil::loadData('scoreboards-enabled',
			$data, $this->scoreboardEnabled);
		MineceitUtil::loadData('translate',
			$data, $this->translateMessages);
		MineceitUtil::loadData('swish-sound',
			$data, $this->swishSoundEnabled);
		MineceitUtil::loadData('cps-popup',
			$data, $this->cpsPopup);
		MineceitUtil::loadData('pe-only',
			$data, $this->peOnlyQueues);
		MineceitUtil::loadData('autorespawn',
			$data, $this->autoRespawnEnabled);
		MineceitUtil::loadData('autosprint',
			$data, $this->autoSprintEnabled);
		MineceitUtil::loadData('morecrit',
			$data, $this->moreCritsEnabled);
		MineceitUtil::loadData('lightning',
			$data, $this->lightningDeathEnabled);
		MineceitUtil::loadData('blood',
			$data, $this->bloodEnabled);
		MineceitUtil::loadData('autogg',
			$data, $this->autoGGEnabled);
	}

	/**
	 * Loads the language.
	 *
	 * @param array $data
	 */
	private function loadLanguage(array &$data) : void{
		$languageLocale = $this->player->getLocale();
		MineceitUtil::loadData('language',
			$data, $languageLocale);
		$oldLanguage = MineceitCore::getPlayerHandler()->getLanguageFromOldName($languageLocale);
		if($oldLanguage !== null && !$oldLanguage->equals($this->getLanguageInfo()->getLanguage())){
			$this->getLanguageInfo()->setLanguage($languageLocale);
			$form = FormUtil::getLanguageForm($languageLocale);
			$this->player->sendFormWindow($form, ['locale' => $languageLocale]);
		}else{
			$this->getLanguageInfo()->setLanguage($languageLocale);
		}
	}

	public function getLanguageInfo() : ?LanguageInfo{
		return $this->languageInfo;
	}

	/**
	 * @param bool $updateRow
	 *
	 * @return MysqlRow
	 *
	 * Generates the mysql row.
	 */
	public function generateMYSQLRow(bool $updateRow) : MysqlRow{
		$playerSettings = new MysqlRow("PlayerSettings");
		$playerSettings->put("username", $this->playerName);
		$playerSettings->put("language",
			$this->languageInfo->getLanguage()->getLocale());
		if($updateRow){
			$playerSettings->put('scoreboardsenabled', $this->scoreboardEnabled);
			$playerSettings->put('peonly', $this->peOnlyQueues);
			$playerSettings->put('translate', $this->translateMessages);
			$playerSettings->put('swishsound', $this->swishSoundEnabled);
			$playerSettings->put("cpspopup", $this->cpsPopup);
			$playerSettings->put("placebreak", $this->builderModeInfo->isEnabled());
			$playerSettings->put("autorespawn", $this->autoRespawnEnabled);
			$playerSettings->put("autosprint", $this->autoSprintEnabled);
			$playerSettings->put("morecrit", $this->moreCritsEnabled);
			$playerSettings->put("lightning", $this->lightningDeathEnabled);
			$playerSettings->put("blood", $this->bloodEnabled);
			$playerSettings->put("autogg", $this->autoGGEnabled);
		}
		return $playerSettings;
	}

	public function isScoreboardEnabled() : bool{
		return $this->scoreboardEnabled;
	}

	public function setScoreboardEnabled(bool $scoreboardEnabled) : void{
		$this->scoreboardEnabled = $scoreboardEnabled;
	}

	public function doesTranslateMessages() : bool{
		return $this->translateMessages;
	}

	public function setTranslateMessages(bool $translateMessages) : void{
		$this->translateMessages = $translateMessages;
	}

	public function isCPSPopupEnabled() : bool{
		return $this->cpsPopup;
	}

	public function setCPSPopupEnabled(bool $enabled) : void{
		$this->cpsPopup = $enabled;
	}

	public function hasPEOnlyQueues() : bool{
		return $this->peOnlyQueues;
	}

	public function setPEOnlyQueues(bool $enabled) : void{
		$this->peOnlyQueues = $enabled;
	}

	public function isAutoRespawnEnabled() : bool{
		return $this->autoRespawnEnabled;
	}

	public function setAutoRespawnEnabled(bool $enabled) : void{
		$this->autoRespawnEnabled = $enabled;
	}

	public function isAutoSprintEnabled() : bool{
		return $this->autoSprintEnabled;
	}

	public function setAutoSprintEnabled(bool $enabled) : void{
		$this->autoSprintEnabled = $enabled;
	}

	public function isMoreCritsEnabled() : bool{
		return $this->moreCritsEnabled;
	}

	public function setMoreCritsEnabled(bool $enabled) : void{
		$this->moreCritsEnabled = $enabled;
	}

	public function isAutoGGEnabled() : bool{
		return $this->autoGGEnabled;
	}

	public function setAutoGGEnabled(bool $enabled) : void{
		$this->autoGGEnabled = $enabled;
	}

	/**
	 * @param EntityDamageByEntityEvent $event
	 *
	 * Called when an entity is damaged by another entity.
	 */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void{
		$damager = $event->getDamager();
		if($damager instanceof Player
			&& $damager->getName() === $this->playerName
			&& $this->moreCritsEnabled){
			// Sends a critical hit particle to the damager.
			$pk = new AnimatePacket();
			$pk->action = AnimatePacket::ACTION_CRITICAL_HIT;
			$pk->entityRuntimeId = $event->getEntity()->getId();
			$damager->dataPacket($pk);
		}
	}

	/**
	 * @param DataPacketSendEvent $event
	 *
	 * Called when a player sends a data packet.
	 */
	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		if($player !== null && $player->isOnline()
			&& $player->getName() === $this->playerName){
			if($packet instanceof LevelSoundEventPacket){
				$event->setCancelled($this->checkSwishSound($packet->sound));
			}
			// Too many packets in a single batch
			// elseif ($packet instanceof BatchPacket)
			// {
			//     $packets = $packet->getPackets();
			//     foreach($packets as $packetBuffer)
			//     {
			//         $pkt = PacketPool::getPacket($packetBuffer);
			//         if($pkt instanceof LevelSoundEventPacket)
			//         {
			//             $pkt->decode();
			//             if($this->checkSwishSound($pkt->sound))
			//             {
			//                 // Should send nothing.
			//                 $pkt->setBuffer("");
			//             }
			//             $pkt->encode();
			//         }
			//     }
			// }
		}
	}

	/**
	 * @param int $soundId
	 *
	 * @return bool
	 *
	 * Called to check whether it is a swish sound and to check the sound id.
	 */
	private function checkSwishSound(int $soundId) : bool{
		return isset(self::SWISH_SOUNDS[$soundId]) && !$this->isSwishSoundEnabled();
	}

	public function isSwishSoundEnabled() : bool{
		return $this->swishSoundEnabled;
	}

	public function setSwishSoundEnabled(bool $enabled) : void{
		$this->swishSoundEnabled = $enabled;
	}

	/**
	 * @param PlayerDeathEvent $event
	 *
	 * Called when a player has died.
	 */
	public function onDeath(PlayerDeathEvent $event) : void{
		$player = $event->getPlayer();
		$lastDamageCause = $player->getLastDamageCause();
		if($lastDamageCause !== null
			&& $lastDamageCause instanceof EntityDamageByEntityEvent){
			$damager = $lastDamageCause->getDamager();
			if($damager instanceof Player
				&& $damager->getName() === $this->playerName){
				if($this->isLightningDeathEnabled()){
					MineceitUtil::spawnLightningBolt($player, [$damager]);
				}
				if($this->isBloodEnabled()){
					MineceitUtil::sprayBlood($player, [$damager]);
				}
			}
		}
	}

	public function isLightningDeathEnabled() : bool{
		return $this->lightningDeathEnabled;
	}

	public function setLightningDeathEnabled(bool $enabled) : void{
		$this->lightningDeathEnabled = $enabled;
	}

	public function isBloodEnabled() : bool{
		return $this->bloodEnabled;
	}

	public function setBloodEnabled(bool $enabled) : void{
		$this->bloodEnabled = $enabled;
	}
}