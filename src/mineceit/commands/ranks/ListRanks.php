<?php

declare(strict_types=1);

namespace mineceit\commands\ranks;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;

class ListRanks extends MineceitCommand{

	public function __construct(){
		parent::__construct('listranks', 'List all of the ranks on the server.', 'Usage: /listranks', []);
		parent::setPermission('mineceit.permission.toggle-ranks');
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param string[]      $args
	 *
	 * @return mixed
	 * @throws CommandException
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args){
		$msg = null;

		$playerHandler = MineceitCore::getPlayerHandler();

		$rankHandler = MineceitCore::getRankHandler();

		$language = $sender instanceof MineceitPlayer ? $sender->getLanguageInfo()->getLanguage() : $playerHandler->getLanguage();

		if($this->testPermission($sender) && $this->canUseCommand($sender)){
			$ranks = $rankHandler->listRanks();
			$msg = $language->listMessage(Language::LIST_RANKS, $ranks);
		}

		if($msg !== null) $sender->sendMessage($msg);
	}

	public function testPermission(CommandSender $sender) : bool{

		if($sender instanceof MineceitPlayer && $sender->hasAdminPermissions()){
			return true;
		}

		return parent::testPermission($sender);
	}
}
