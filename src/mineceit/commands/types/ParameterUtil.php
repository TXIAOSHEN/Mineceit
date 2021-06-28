<?php

declare(strict_types=1);

namespace mineceit\commands\types;

use mineceit\arenas\DuelArena;
use mineceit\arenas\EventArena;
use mineceit\game\FormUtil;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ParameterUtil{

	// TODO DO THE LANGUAGE FOR SUCCESSFULLY EDITED ARENA

	/**
	 * @return array|MineceitCommandParameter[]
	 *
	 * Initializes && gets the event arena command parameters.
	 */
	public static function getEventArenaParameters(){

		$createParameter = new MineceitCommandParameter("create", function(CommandSender $sender, array $args){

			$length = count($args);

			$msg = null;

			$usage = "Usage: /event create <name> <kit>";

			if($sender instanceof MineceitPlayer){

				$language = $sender->getLanguageInfo()->getLanguage();

				if($length === 2){

					$arenaName = strval($args[0]);

					$kitName = strval($args[1]);

					$kitHandler = MineceitCore::getKits();
					$arenaHandler = MineceitCore::getArenas();

					if($kitHandler->isKit($kitName)){

						$kit = $kitHandler->getKit($kitName);

						$arena = $arenaHandler->getArena($arenaName);

						if($arena !== null){

							$msg = $language->arenaMessage(Language::ARENA_EXISTS, $arena);
						}else{

							$arenaHandler->createArena($arenaName, $kit->getLocalizedName(), $sender->getPlayer(), 'Event');
							$msg = $language->arenaMessage(Language::CREATE_ARENA, $arenaName);
						}
					}else $msg = $language->kitMessage($kitName, Language::KIT_NO_EXIST);
				}else{
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
				}
			}else{
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
			}

			if($msg !== null){
				$sender->sendMessage($msg);
			}
		});


		$deleteParameter = new MineceitCommandParameter('delete', function(CommandSender $sender, array $args){

			$length = count($args);

			$msg = null;

			$usage = "Usage: /event delete <name>";

			if($sender instanceof MineceitPlayer){

				$arenaHandler = MineceitCore::getArenas();

				$language = $sender->getLanguageInfo()->getLanguage();

				if($length === 1){

					$arenaName = $args[0];

					$arena = $arenaHandler->getArena($arenaName);

					if($arena !== null && $arena instanceof EventArena){

						$arenaName = $arena->getName();

						$arenaHandler->deleteArena($arenaName);

						$msg = $language->arenaMessage(Language::DELETE_ARENA, $arenaName);
					}else $msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);
				}else{
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
				}
			}else{
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
			}

			if($msg !== null){
				$sender->sendMessage($msg);
			}
		});


		$pos1Parameter = new MineceitCommandParameter('p1start', function(CommandSender $sender, array $args){

			$length = count($args);

			$msg = null;

			$usage = "Usage: /event p1start <name>";

			if($sender instanceof MineceitPlayer){

				$arenaHandler = MineceitCore::getArenas();

				$language = $sender->getLanguageInfo()->getLanguage();

				if($length === 1){

					$arenaName = $args[0];

					$arena = $arenaHandler->getArena($arenaName);

					if($arena !== null && $arena instanceof EventArena){

						$arena->setP1SpawnPos($sender->asVector3());

						$arenaHandler->editArena($arena);

						$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . 'Successfully edited the arena!';
						// TODO LANG

					}else{
						$msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);
					}
				}else{
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
				}
			}else{
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
			}

			if($msg !== null){
				$sender->sendMessage($msg);
			}
		});


		$pos2Parameter = new MineceitCommandParameter('p2start', function(CommandSender $sender, array $args){

			$length = count($args);

			$msg = null;

			$usage = "Usage: /event p2start <name>";

			if($sender instanceof MineceitPlayer){

				$arenaHandler = MineceitCore::getArenas();

				$language = $sender->getLanguageInfo()->getLanguage();

				if($length === 1){

					$arenaName = $args[0];

					$arena = $arenaHandler->getArena($arenaName);

					if($arena !== null && $arena instanceof EventArena){

						$arena->setP2SpawnPos($sender->asVector3());

						$arenaHandler->editArena($arena);

						$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . 'Successfully edited the arena!';

						// TODO LANG

					}else $msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);
				}else{
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
				}
			}else{
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
			}

			if($msg !== null){
				$sender->sendMessage($msg);
			}
		});


		$spawnParameter = new MineceitCommandParameter('spawn', function(CommandSender $sender, array $args){

			$length = count($args);

			$msg = null;

			$usage = "Usage: /event spawn <name>";

			if($sender instanceof MineceitPlayer){

				$arenaHandler = MineceitCore::getArenas();

				$language = $sender->getLanguageInfo()->getLanguage();

				if($length === 1){

					$arenaName = $args[0];

					$arena = $arenaHandler->getArena($arenaName);

					if($arena !== null && $arena instanceof EventArena){

						$arena->setSpawn($sender->asVector3());

						$arenaHandler->editArena($arena);

						$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . 'Successfully edited the arena!';

						// TODO LANG

					}else $msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);
				}else{
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
				}
			}else{
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
			}

			if($msg !== null){
				$sender->sendMessage($msg);
			}
		});

		return [
			$createParameter->getName() => $createParameter,
			$deleteParameter->getName() => $deleteParameter,
			$pos1Parameter->getName() => $pos1Parameter,
			$pos2Parameter->getName() => $pos2Parameter,
			$spawnParameter->getName() => $spawnParameter
		];
	}

	/**
	 * @return array|MineceitCommandParameter[]
	 *
	 * Initializes && gets the duel arena command parameters.
	 */
	public static function getDuelArenaParameters(){

		$createParameter = new MineceitCommandParameter("create", function(CommandSender $sender, array $args){

			$length = count($args);

			$msg = null;

			$usage = "Usage: /arena create <name>";

			if($sender instanceof MineceitPlayer){

				if($length === 1){

					$arenaName = strval($args[0]);

					$form = FormUtil::getEditDuelArenaForm($sender, $arenaName);
					$sender->sendFormWindow($form, ['arena' => $arenaName]);

				}else{
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
				}
			}else{
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
			}

			if($msg !== null){
				$sender->sendMessage($msg);
			}
		});


		$deleteParameter = new MineceitCommandParameter('delete', function(CommandSender $sender, array $args){

			$length = count($args);

			$msg = null;

			$usage = "Usage: /arena delete <name>";

			if($sender instanceof MineceitPlayer){

				$arenaHandler = MineceitCore::getArenas();

				$language = $sender->getLanguageInfo()->getLanguage();

				if($length === 1){

					$arenaName = $args[0];

					$arena = $arenaHandler->getArena($arenaName);

					if($arena !== null && $arena instanceof DuelArena){

						$arenaName = $arena->getName();

						unlink(MineceitCore::getResourcesFolder() . 'worlds/' . $arenaName . '.zip');

						$arenaHandler->deleteArena($arenaName);

						$msg = $language->arenaMessage(Language::DELETE_ARENA, $arenaName);
					}else $msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);
				}else{
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
				}
			}else{
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
			}

			if($msg !== null){
				$sender->sendMessage($msg);
			}
		});


		$pos1Parameter = new MineceitCommandParameter('p1start', function(CommandSender $sender, array $args){

			$length = count($args);

			$msg = null;

			$usage = "Usage: /arena p1start <name>";

			if($sender instanceof MineceitPlayer){

				$arenaHandler = MineceitCore::getArenas();

				$language = $sender->getLanguageInfo()->getLanguage();

				if($length === 1){

					$arenaName = $args[0];

					$arena = $arenaHandler->getArena($arenaName);

					if($arena !== null && $arena instanceof DuelArena){

						$arena->setP1SpawnPos($sender->asVector3());

						$arenaHandler->editArena($arena);

						$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . 'Successfully edited the arena!';
						// TODO LANG

					}else{
						$msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);
					}
				}else{
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
				}
			}else{
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
			}

			if($msg !== null){
				$sender->sendMessage($msg);
			}
		});


		$pos2Parameter = new MineceitCommandParameter('p2start', function(CommandSender $sender, array $args){

			$length = count($args);

			$msg = null;

			$usage = "Usage: /arena p2start <name>";

			if($sender instanceof MineceitPlayer){

				$arenaHandler = MineceitCore::getArenas();

				$language = $sender->getLanguageInfo()->getLanguage();

				if($length === 1){

					$arenaName = $args[0];

					$arena = $arenaHandler->getArena($arenaName);

					if($arena !== null && $arena instanceof DuelArena){

						$arena->setP2SpawnPos($sender->asVector3());

						$arenaHandler->editArena($arena);

						$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . 'Successfully edited the arena!';

						// TODO LANG

					}else $msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);
				}else{
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
				}
			}else{
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
			}

			if($msg !== null){
				$sender->sendMessage($msg);
			}
		});


		$kitsParameter = new MineceitCommandParameter('kits', function(CommandSender $sender, array $args){

			$length = count($args);

			$msg = null;

			$usage = "Usage: /arena kits <name>";

			if($sender instanceof MineceitPlayer){

				$arenaHandler = MineceitCore::getArenas();

				$language = $sender->getLanguageInfo()->getLanguage();

				if($length === 1){

					$arenaName = $args[0];

					$arena = $arenaHandler->getArena($arenaName);

					if($arena !== null && $arena instanceof DuelArena){

						$form = FormUtil::getEditDuelArenaForm($sender, $arena);
						$sender->sendFormWindow($form, ['arena' => $arena]);

						// TODO LANG

					}else $msg = $language->arenaMessage(Language::ARENA_NO_EXIST, $arenaName);
				}else{
					$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . $usage;
				}
			}else{
				$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "Console can't use this command.";
			}

			if($msg !== null){
				$sender->sendMessage($msg);
			}
		});

		return [
			$createParameter->getName() => $createParameter,
			$deleteParameter->getName() => $deleteParameter,
			$pos1Parameter->getName() => $pos1Parameter,
			$pos2Parameter->getName() => $pos2Parameter,
			$kitsParameter->getName() => $kitsParameter
		];
	}
}
