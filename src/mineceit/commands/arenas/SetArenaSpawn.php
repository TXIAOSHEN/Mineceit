<?php

declare(strict_types=1);

namespace mineceit\commands\arenas;

use mineceit\arenas\FFAArena;
use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class SetArenaSpawn extends MineceitCommand{

	public function __construct(){
		parent::__construct('arenaspawn', "Updates an arena's spawn.", 'Usage: /arenaspawn <name>', []);
		parent::setPermission('mineceit.permission.setarenaspawn');
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

				if($size === 1){

					$arenaName = strval($args[0]);

					$arena = $arenaHandler->getArena($arenaName);

					if($arena !== null && $arena instanceof FFAArena){

						$arena->setSpawn($sender);
						$arenaHandler->editArena($arena);
						$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::GREEN . 'Successfully edited the arena!';
					}else{

						$msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);
					}
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
