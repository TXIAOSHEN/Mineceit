<?php

declare(strict_types=1);

namespace mineceit\commands\kits;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;

class ListKits extends MineceitCommand{

	public function __construct(){
		parent::__construct('listKits', 'List all of the kits on the server.', "Usage: /listKits", []);
		parent::setPermission('mineceit.permission.listkits');
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

		if($this->testPermission($sender)){

			$kits = MineceitCore::getKits();

			$playerHandler = MineceitCore::getPlayerHandler();

			$language = $sender instanceof MineceitPlayer ? $sender->getLanguageInfo()
				->getLanguage() : $playerHandler->getLanguage();

			$msg = $language->listMessage(Language::LIST_KITS, $kits->getKits(true));
		}

		if($msg !== null) $sender->sendMessage($msg);

		return true;
	}

	public function testPermission(CommandSender $sender) : bool{

		if($sender instanceof MineceitPlayer && $sender->hasOwnerPermissions()){
			return true;
		}

		return parent::testPermission($sender);
	}
}
