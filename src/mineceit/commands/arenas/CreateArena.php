<?php

declare(strict_types=1);

namespace mineceit\commands\arenas;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class CreateArena extends MineceitCommand{

	public function __construct(){
		parent::__construct('createarena', 'Creates an arena with a specified kit.', 'Usage: /createarena <name> <kit>', []);
		parent::setPermission('mineceit.permission.toggle-arenas');
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

		$arenaHandler = MineceitCore::getArenas();

		if($sender instanceof MineceitPlayer){

			$p = $sender->getPlayer();
			$language = $p->getLanguageInfo()->getLanguage();
			if($this->testPermission($sender) && $this->canUseCommand($sender)){
				$size = count($args);
				if($size === 2){
					$arenaName = strval($args[0]);
					$kit = MineceitCore::getKits()->getKit(
						$kitName = strval($args[1]));
					if($kit === null){
						$sender->sendMessage(
							$language->kitMessage($kitName, Language::KIT_NO_EXIST));
						return true;
					}

					if(!$kit->getMiscKitInfo()->isFFAEnabled()){
						// TODO: Send message
						return true;
					}

					$arena = $arenaHandler->getArena($arenaName);
					if($arena !== null){
						$sender->sendMessage(
							$language->arenaMessage(Language::ARENA_EXISTS, $arena));
						return true;
					}

					$arenaHandler->createArena($arenaName, $kit->getLocalizedName(), $p, 'FFA');
					$sender->sendMessage(
						$language->arenaMessage(Language::CREATE_ARENA, $arenaName));
					return true;
				}else{
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $this->getUsage();
				}
			}
		}else $msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RED . "Console can't use this command.";

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
