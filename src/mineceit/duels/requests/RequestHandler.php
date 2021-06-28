<?php

declare(strict_types=1);

namespace mineceit\duels\requests;

use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\utils\TextFormat;

class RequestHandler
{

	/* @var DuelRequest[]|array */
	private $requests;

	public function __construct()
	{
		$this->requests = [];
	}

	/**
	 * @param MineceitPlayer $from
	 * @param MineceitPlayer $to
	 * @param string $queue
	 * @param bool $ranked
	 *
	 * Sends a duel request from a player to another.
	 */
	public function sendRequest(MineceitPlayer $from, MineceitPlayer $to, string $queue, bool $ranked): void
	{

		$fromMsg = $from->getLanguageInfo()->getLanguage()->generalMessage(Language::SENT_REQUEST, ["name" => $to->getDisplayName()]);
		$from->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $fromMsg);

		$toMsg = $to->getLanguageInfo()->getLanguage()->generalMessage(Language::RECEIVE_REQUEST, ["name" => $from->getDisplayName()]);
		$key = $from->getName() . ':' . $to->getName();

		$send = true;

		if (isset($this->requests[$key])) {
			/** @var DuelRequest $oldRequest */
			$oldRequest = $this->requests[$key];
			$send = $oldRequest->getQueue() !== $queue || $oldRequest->isRanked() !== $ranked;
		}

		if ($send) {
			$to->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $toMsg);
		}

		$this->requests[$key] = new DuelRequest($from, $to, $queue, $ranked);
	}

	/**
	 * @param MineceitPlayer $player \
	 * @return array|DuelRequest[]
	 *
	 * Gets the requests of a player.
	 */
	public function getRequestsOf(MineceitPlayer $player)
	{

		$result = [];

		$name = $player->getName();

		foreach ($this->requests as $request) {
			$from = $request->getFrom();
			if ($request->getTo()->getName() === $name && $from->isOnline()) {
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
	public function removeAllRequestsWith($player): void
	{
		$name = $player instanceof MineceitPlayer ? $player->getName() : $player;
		foreach ($this->requests as $key => $request) {
			if ($request->getFromName() === $name || $request->getToName() === $name) {
				unset($this->requests[$key]);
			}
		}
	}

	/**
	 * @param DuelRequest $request
	 *
	 * Accepts a duel request.
	 */
	public function acceptRequest(DuelRequest $request): void
	{
		$from = $request->getFrom();
		$to = $request->getTo();

		$toMsg = $to->getLanguageInfo()->getLanguage()->generalMessage(Language::DUEL_ACCEPTED_REQUEST_TO, ["name" => $request->getFromDisplayName()]);
		$to->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $toMsg);

		$fromMsg = $from->getLanguageInfo()->getLanguage()->generalMessage(Language::DUEL_ACCEPTED_REQUEST_FROM, ["name" => $request->getToDisplayName()]);
		$from->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $fromMsg);

		unset($this->requests[$request->getFromName() . ':' . $request->getToName()]);
	}
}
