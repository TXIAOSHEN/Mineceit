<?php

declare(strict_types=1);

namespace mineceit\parties\requests;

use mineceit\MineceitUtil;
use mineceit\parties\MineceitParty;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\utils\TextFormat;

class RequestHandler{

	/* @var PartyRequest[]|array */
	private $requests;

	public function __construct(){
		$this->requests = [];
	}

	/**
	 * @param MineceitPlayer $from
	 * @param MineceitPlayer $to
	 * @param MineceitParty  $party
	 *
	 * Sends a party request from a player to another.
	 */
	public function sendRequest(MineceitPlayer $from, MineceitPlayer $to, MineceitParty $party) : void{

		$fromMsg = $from->getLanguageInfo()->getLanguage()
			->generalMessage(Language::SENT_INVITE, ["name" => $to->getDisplayName()]);
		$from->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $fromMsg);

		$toMsg = $to->getLanguageInfo()->getLanguage()
			->generalMessage(Language::RECEIVE_INVITE, ["name" => $from->getDisplayName()]);
		$key = $from->getName() . ':' . $to->getName();

		$to->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $toMsg);

		$this->requests[$key] = new PartyRequest($from, $to, $party);
	}

	/**
	 * @param MineceitPlayer $player \
	 *
	 * @return array|PartyRequest[]
	 *
	 * Gets the requests of a player.
	 */
	public function getRequestsOf(MineceitPlayer $player) : array{

		$result = [];

		$name = $player->getName();

		foreach($this->requests as $request){
			$from = $request->getFrom();
			if($request->getTo()->getName() === $name && $from->isOnline()){
				$result[$from->getName()] = $request;
			}
		}

		return $result;
	}

	/**
	 * @param MineceitPlayer|string $player
	 *
	 * Removes all requests with the player's name.
	 */
	public function removeAllRequestsWith($player) : void{
		$name = $player instanceof MineceitPlayer ? $player->getName() : $player;
		foreach($this->requests as $key => $request){
			if($request->getFromName() === $name || $request->getToName() === $name){
				unset($this->requests[$key]);
			}
		}
	}

	/**
	 * @param PartyRequest $request
	 *
	 * Accepts a party request.
	 */
	public function acceptRequest(PartyRequest $request) : void{
		$from = $request->getFrom();
		$to = $request->getTo();

		$toMsg = $to->getLanguageInfo()->getLanguage()
			->generalMessage(Language::PARTIES_ACCEPTED_INVITE_TO, ["name" => $request->getFromDisplayName()]);
		$to->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $toMsg);

		$fromMsg = $from->getLanguageInfo()->getLanguage()
			->generalMessage(Language::PARTIES_ACCEPTED_INVITE_FROM, ["name" => $request->getToDisplayName()]);
		$from->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $fromMsg);

		unset($this->requests[$request->getFromName() . ':' . $request->getToName()]);
	}
}
