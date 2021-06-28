<?php

declare(strict_types=1);

namespace mineceit\player\info;


use mineceit\MineceitCore;
use mineceit\misc\AbstractListener;
use mineceit\player\info\device\DeviceIds;
use mineceit\player\MineceitPlayer;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;

class ClientInfo extends AbstractListener implements DeviceIds{

	/** @var string */
	private $version = ProtocolInfo::MINECRAFT_VERSION;

	/** @var string - The raw device model as sent from clientData. */
	private $deviceModelRaw = "";
	/** @var string - The device model found in the deviceInfo. */
	private $deviceModel = "";
	/** @var int - The client randomized id. */
	private $clientRandomID = 0;
	/** @var int - The device os. */
	private $deviceOS = self::UNKNOWN;
	/** @var string - The device id as sent from clientData. */
	private $deviceIdRaw = "";
	/** @var int - Gets the current input mode at login (subject to change at runtime.) */
	private $inputAtLogin = self::UNKNOWN;
	/** @var int - Gets the current ui profile at login. */
	private $uiProfileAtLogin = self::UNKNOWN;

	/** @var string */
	private $playerName;
	/** @var Player */
	private $player;

	public function __construct(Player $player){
		parent::__construct(MineceitCore::getInstance());
		$this->player = $player;
		$this->playerName = $player->getName();
	}

	/**
	 * @return Player|null
	 *
	 * Get the player.
	 */
	public function getPlayer() : ?Player{
		return $this->player;
	}

	/**
	 * @return string
	 *
	 * Gets the name of the player.
	 */
	public function getName() : string{
		return $this->playerName;
	}

	public function getVersion() : string{
		return $this->version;
	}

	/**
	 * @return bool
	 *
	 * Determines if the client is PE.
	 */
	public function isPE() : bool{
		return !isset(self::NON_PE_DEVICES[$this->deviceOS])
			&& $this->getInputAtLogin() === self::TOUCH;
	}

	/**
	 * @param bool $asString
	 *
	 * @return int|string
	 */
	public function getInputAtLogin(bool $asString = false){
		return $asString ? self::INPUT_VALUES[$this->inputAtLogin]
			: $this->inputAtLogin;
	}

	public function getRawDeviceId() : string{
		return $this->deviceIdRaw;
	}

	/**
	 * @param bool $asString
	 *
	 * @return int|string
	 */
	public function getDeviceOS(bool $asString = false){
		return $asString ? self::DEVICE_OS_VALUES[$this->deviceOS]
			: $this->deviceOS;
	}

	public function getRawDeviceModel() : string{
		return $this->deviceModelRaw;
	}

	public function getDeviceModel() : string{
		return $this->deviceModel;
	}

	public function getUIProfile() : string{
		return self::UI_PROFILE_VALUES[$this->uiProfileAtLogin];
	}

	public function getClientRandomId() : int{
		return $this->clientRandomID;
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 *
	 * Called when the player receives a data packet.
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		if($player instanceof MineceitPlayer
			&& $packet instanceof LoginPacket){
			$player->getClientInfo()->init(
				$packet->clientData);
		}
	}

	/**
	 * @param array $clientData - The client data sent from login packet.
	 * Initializes the data contained here.
	 */
	private function init(array $clientData) : void{
		// Hack for getting device model & os for xbox & linux.
		// Could change in the future.
		$this->version = $clientData["GameVersion"] ?? ProtocolInfo::MINECRAFT_VERSION;
		$deviceModel = (string) ($clientData["DeviceModel"] ?? 'Unknown');
		$deviceOS = (int) ($clientData["DeviceOS"] ?? self::UNKNOWN);

		if(trim($deviceModel) === ""){
			switch($deviceOS){
				case self::ANDROID:
					$deviceOS = self::LINUX;
					$deviceModel = "Linux";
					break;
				case self::XBOX:
					$deviceModel = "Xbox One";
					break;
			}
		}

		$this->deviceModelRaw = $deviceModel;
		$this->deviceModel = MineceitCore::getPlayerHandler()
				->getDeviceInfo()->getDeviceFromModel($this->deviceModelRaw) ?? $this->deviceModelRaw;
		$this->deviceOS = $deviceOS;
		$this->deviceIdRaw = (string) ($clientData["DeviceId"] ?? 'Unknown');
		$this->inputAtLogin = (int) ($clientData["CurrentInputMode"] ?? self::UNKNOWN);
		$this->uiProfileAtLogin = (int) ($clientData["UIProfile"] ?? self::UNKNOWN);
		$this->clientRandomID = (int) ($clientData["ClientRandomId"] ?? 0);
	}
}
