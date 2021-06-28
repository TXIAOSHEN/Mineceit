<?php

declare(strict_types=1);

namespace mineceit\scoreboard;

use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ScoreboardUtil{

	public const ONLINE_PLAYERS = 'online-players';
	public const IN_QUEUES = 'in-queues';
	public const IN_FIGHTS = 'in-fights';

	/**
	 * @param string              $type
	 * @param MineceitPlayer|null $matched
	 *
	 * Updates the spawn scoreboard for everyone.
	 */
	public static function updateSpawnScoreboard(string $type, MineceitPlayer $matched = null) : void{

		switch($type){
			case self::ONLINE_PLAYERS:
				self::updateOnlinePlayers($matched);
				break;
			case self::IN_QUEUES:
				self::updateInQueues($matched);
				break;
			case self::IN_FIGHTS:
				self::updateInFights($matched);
				break;
		}
	}


	/**
	 * @param MineceitPlayer|null $matched
	 *
	 * Updates the online players.
	 */
	private static function updateOnlinePlayers(MineceitPlayer $matched = null) : void{

		$players = Server::getInstance()->getOnlinePlayers();
		$online = count($players);

		$lineNum = 1;

		$color = MineceitUtil::getThemeColor();
		$original = TextFormat::WHITE . ' %online%: ' . $color . $online;

		foreach($players as $player){

			if($player instanceof MineceitPlayer){

				$pSb = $player->getScoreboardInfo();
				if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_SPAWN){
					continue;
				}

				if($matched !== null && $matched->equalsPlayer($player)){
					continue;
				}
				$language = $player->getLanguageInfo()->getLanguage();
				$onlineStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_ONLINE);
				$line = $original;
				$theLine = str_replace('%online%', $onlineStr, $line);
				$pSb->updateLineOfScoreboard($lineNum, $theLine);
			}
		}
	}

	/**
	 * @param MineceitPlayer|null $matched
	 *
	 * Updates the in-queues line of the scoreboadr for everyone.
	 */
	private static function updateInQueues(MineceitPlayer $matched = null) : void{

		/** @var MineceitPlayer[] $players */
		$players = Server::getInstance()->getOnlinePlayers();

		$numInQueues = MineceitCore::getDuelHandler()->getEveryoneInQueues();

		$lineNum = 7;

		$color = MineceitUtil::getThemeColor();

		$original = TextFormat::WHITE . ' %in-queues%: ' . $color . $numInQueues;

		foreach($players as $player){
			$pSb = $player->getScoreboardInfo();
			if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_SPAWN){
				continue;
			}
			if($matched !== null && $matched->equalsPlayer($player)){
				continue;
			}
			$language = $player->getLanguageInfo()->getLanguage();
			$inQueuesStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_INQUEUES);
			$line = $original;
			$theLine = str_replace('%in-queues%', $inQueuesStr, $line);
			$pSb->updateLineOfScoreboard($lineNum, $theLine);
		}
	}

	/**
	 * @param MineceitPlayer|null $matched
	 *
	 * Updates the scoreboard of the in-fights line.
	 */
	private static function updateInFights(MineceitPlayer $matched = null) : void{

		$players = Server::getInstance()->getOnlinePlayers();
		$numInFights = MineceitCore::getDuelHandler()->getDuels(true) * 2;
		$lineNum = 6;

		$color = MineceitUtil::getThemeColor();
		$originalFormat = TextFormat::WHITE . ' %in-fights%: ' . $color . $numInFights;

		foreach($players as $player){

			if($player instanceof MineceitPlayer){
				$pSb = $player->getScoreboardInfo();
				if($pSb->getScoreboardType() === Scoreboard::SCOREBOARD_SPAWN){

					if($matched !== null && $matched->equalsPlayer($player)){
						continue;
					}
					$language = $player->getLanguageInfo()->getLanguage();
					$inFightsStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_INFIGHTS);
					$line = $originalFormat;
					$theLine = str_replace('%in-fights%', $inFightsStr, $line);
					$pSb->updateLineOfScoreboard($lineNum, $theLine);
				}
			}
		}
	}
}
