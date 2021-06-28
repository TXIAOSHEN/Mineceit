<?php

declare(strict_types=1);

namespace mineceit\parties;

use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\utils\TextFormat;

class MineceitParty{

	public const MAX_PLAYERS = 8;

	/* @var int */
	private $maxPlayers;

	/* @var MineceitPlayer[]|array */
	private $players;

	/* @var MineceitPlayer */
	private $owner;

	/* @var string */
	private $name;

	/* @var string */
	private $lowername;

	/* @var bool */
	private $open;

	/* @var string[]|array */
	private $blacklisted;

	public function __construct(MineceitPlayer $owner, string $name, int $maxPlayers, bool $open = true){
		$this->owner = $owner;
		$this->name = $name;
		$this->maxPlayers = $maxPlayers;
		$local = strtolower($owner->getName());
		$this->players = [$local => $owner];
		$this->open = $open;
		$this->blacklisted = [];
		$this->lowername = strtolower($name);
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function addPlayer(MineceitPlayer $player) : void{

		$name = $player->getName();

		$local = strtolower($name);

		if(!isset($this->players[$local])){

			$this->players[$local] = $player;
			$itemHandler = MineceitCore::getItemHandler();
			$itemHandler->spawnPartyItems($player);

			$duelHandler = MineceitCore::getDuelHandler();
			if($duelHandler->isInQueue($player))
				$duelHandler->removeFromQueue($player, false);

			foreach($this->players as $member){
				if($member->isOnline()){
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member->getLanguageInfo()->getLanguage()->generalMessage(Language::PLAYER_JOIN_PARTY, ["name" => $player->getDisplayName()]);
					$member->sendMessage($msg);
				}
			}
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param string         $reason
	 * @param bool           $blacklist
	 */
	public function removePlayer(MineceitPlayer $player, string $reason = '', bool $blacklist = false) : void{

		$name = $player->getName();

		$kicked = $reason !== '';

		$local = strtolower($name);

		if(isset($this->players[$local])){

			$itemHandler = MineceitCore::getItemHandler();

			MineceitCore::getPartyManager()->getEventManager()->removeFromQueue($this);

			if($this->isOwner($player)){

				$partyManager = MineceitCore::getPartyManager();

				$partyEvent = $partyManager->getEventManager()->getPartyEvent($this);

				if($partyEvent !== null){
					foreach($this->players as $p){
						if($p->isOnline()){
							$partyEvent->removeFromEvent($p);
						}
					}
				}

				foreach($this->players as $member){
					if($member->isOnline()){
						$inHub = $member->isInHub();
						if(!$inHub) $member->reset(true, true);
						$itemHandler->spawnHubItems($member, $inHub);
						$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member->getLanguageInfo()->getLanguage()->generalMessage(Language::PLAYER_DISBAND_PARTY, ["name" => $player->getDisplayName()]);
						$member->sendMessage($msg);
					}
				}

				$partyManager->endParty($this);

				return;
			}

			$inHub = $player->isInHub();

			if(!$inHub)
				$player->reset(false, true);

			$itemHandler->spawnHubItems($player, $inHub);

			if($kicked && $blacklist)
				$this->addToBlacklist($player);

			foreach($this->players as $member){
				if($member->isOnline()){
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member->getLanguageInfo()->getLanguage()->generalMessage(Language::PLAYER_LEAVE_PARTY, ["name" => $player->getDisplayName()]);
					$member->sendMessage($msg);
				}
			}

			unset($this->players[$local]);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return bool
	 */
	public function isOwner(MineceitPlayer $player) : bool{
		return $this->owner->equalsPlayer($player);
	}

	/**
	 * @param MineceitPlayer|string $player
	 */
	public function addToBlacklist($player) : void{
		$name = ($player instanceof MineceitPlayer) ? $player->getName() : $player;
		$this->blacklisted[] = $name;

		if($player instanceof MineceitPlayer){
			$name = $player->getDisplayName();
		}else{
			if(($player = MineceitUtil::getPlayerExact($name, true)) !== null && $player instanceof MineceitPlayer){
				$name = $player->getDisplayName();
			}
		}
		foreach($this->players as $member){
			if($member->isOnline()){
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member->getLanguageInfo()->getLanguage()->generalMessage(Language::PLAYER_ADD_BLACKLIST, ["name" => $name]);
				$member->sendMessage($msg);
			}
		}
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return bool
	 */
	public function isPlayer($player) : bool{
		$local = $player instanceof MineceitPlayer ? strtolower($player->getName()) : strtolower($player);
		if(isset($this->players[$local])){
			return true;
		}

		foreach($this->players as $player){
			$displayName = strtolower($player->getDisplayName());
			if($displayName === $local){
				return true;
			}
		}

		return false;
	}

	/**
	 * @param MineceitParty $party
	 *
	 * @return bool
	 */
	public function equalsParty(MineceitParty $party) : bool{
		if($party !== null && $party instanceof MineceitParty)
			return $party->getName() === $this->getName();
		return false;
		//return $player->getName() === $this->getName() && $player->getId() === $this->getId();
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getLowerName() : string{
		return $this->lowername;
	}

	/**
	 * @param bool $int
	 *
	 * @return array|int|MineceitPlayer[]
	 */
	public function getPlayers(bool $int = false){
		return ($int) ? count($this->players) : $this->players;
	}

	/**
	 * @return int
	 */
	public function getMaxPlayers() : int{
		return $this->maxPlayers;
	}

	/**
	 * @param int $players
	 */
	public function setMaxPlayers(int $players) : void{
		$this->maxPlayers = $players;
	}

	/**
	 * @return bool
	 */
	public function isOpen() : bool{
		return $this->open;
	}

	/**
	 * @param bool $open
	 */
	public function setOpen(bool $open = true) : void{
		$this->open = $open;
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function promoteToOwner(MineceitPlayer $player) : void{

		$max = 4;
		$ranks = $player->getRanks(true);
		foreach($ranks as $rank){
			if($rank === "owner" || $rank === "admin" || $rank === "dev" || $rank === "mod" || $rank === "helper" || $rank === "famous" || $rank === "donatorplus" || $rank === "donator"){
				if($max < 8) $max = 8;
			}elseif($rank === "media" || $rank === "booster" || $rank === "voter" || $rank === "designer" || $rank === "builder"){
				if($max < 6) $max = 6;
			}
		}

		if($this->maxPlayers > $max){
			$this->owner->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $this->owner
					->getLanguageInfo()->getLanguage()->generalMessage(
						Language::CANT_PROMOTE_PARTY, ["name" => $player->getDisplayName()]));
			return;
		}

		$oldLocal = $this->getLocalName();

		$oldOwner = $this->owner;

		$this->owner = $player;

		foreach($this->players as $member){
			if($member->isOnline()){
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member
						->getLanguageInfo()->getLanguage()->generalMessage(
							Language::PLAYER_PROMOTE_PARTY, ["name" => $player->getDisplayName()]);
				$member->sendMessage($msg);
			}
		}

		$partyManager = MineceitCore::getPartyManager();

		$newLocal = $this->getLocalName();

		$itemHandler = MineceitCore::getItemHandler();

		$itemHandler->spawnPartyItems($oldOwner);
		$itemHandler->spawnPartyItems($this->owner);

		$partyManager->swapLocal($oldLocal, $newLocal);
	}

	/**
	 * @return string
	 */
	public function getLocalName() : string{
		return strtolower($this->owner->getName()) . ':' . $this->getName();
	}

	/**
	 * @return MineceitPlayer
	 */
	public function getOwner() : MineceitPlayer{
		return $this->owner;
	}

	/**
	 * @param string $name
	 *
	 * @return MineceitPlayer|null
	 */
	public function getPlayer(string $name) : ?MineceitPlayer{
		$local = strtolower($name);
		if(isset($this->players[$local])){
			return $this->players[$local];
		}

		foreach($this->players as $player){
			$displayName = strtolower($player->getDisplayName());
			if($displayName === $name){
				return $player;
			}
		}
		return null;
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * @return bool
	 */
	public function isBlackListed($player) : bool{
		$name = $player instanceof MineceitPlayer ? $player->getName() : $player;
		return in_array($name, $this->blacklisted);
	}

	/**
	 * @param MineceitPlayer|string $player
	 */
	public function removeFromBlacklist($player) : void{
		$name = ($player instanceof MineceitPlayer) ? $player->getName() : $player;
		if(in_array($name, $this->blacklisted)){
			$index = array_search($name, $this->blacklisted);
			unset($this->blacklisted[$index]);
			$this->blacklisted = array_values($this->blacklisted);

			if($player instanceof MineceitPlayer){
				$name = $player->getDisplayName();
			}else{
				if(($player = MineceitUtil::getPlayerExact($name, true)) !== null && $player instanceof MineceitPlayer){
					$name = $player->getDisplayName();
				}
			}
			foreach($this->players as $member){
				if($member->isOnline()){
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $member->getLanguageInfo()->getLanguage()->generalMessage(Language::PLAYER_REMOVE_BLACKLIST, ["name" => $name]);
					$member->sendMessage($msg);
				}
			}
		}
	}

	/**
	 * @return array|string[]
	 */
	public function getBlacklisted() : array{
		return $this->blacklisted;
	}

	/**
	 * @return string
	 */
	public function getPrefix() : string{
		return TextFormat::BOLD . TextFormat::DARK_GRAY . '[' . TextFormat::GREEN . $this->name . TextFormat::DARK_GRAY . ']' . TextFormat::RESET;
	}
}
