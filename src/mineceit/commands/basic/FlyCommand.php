<?php

declare(strict_types=1);

namespace mineceit\commands\basic;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class FlyCommand extends MineceitCommand{

	public function __construct(){
		parent::__construct('fly', 'Gives players the ability to fly.', 'Usage: /fly', []);
		parent::setPermission('mineceit.permission.fly');
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
		$message = null;

		if($sender instanceof MineceitPlayer){

			$lang = $sender->getLanguageInfo()->getLanguage();

			if($this->testPermission($sender) && $this->canUseCommand($sender)){

				if(!$sender->getAllowFlight()){
					$message = $lang->generalMessage(Language::NOW_FLYING);
					$sender->getExtensions()->enableFlying(true);
				}else{
					$message = $lang->generalMessage(Language::NO_LONGER_FLYING);
					$sender->getExtensions()->enableFlying(false);
				}
			}
		}else $message = TextFormat::RED . "Console can't use this command.";

		if($message !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);

		return true;
	}

	public function testPermission(CommandSender $sender) : bool{

		if($sender instanceof MineceitPlayer && $sender->canFlyInLobby()){
			return true;
		}

		return parent::testPermission($sender);
	}
}
