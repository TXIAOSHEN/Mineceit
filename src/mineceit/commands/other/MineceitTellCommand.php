<?php

declare(strict_types=1);

namespace mineceit\commands\other;

use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\TellCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\utils\TextFormat;

class MineceitTellCommand extends TellCommand{

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) < 2){
			throw new InvalidCommandSyntaxException();
		}

		$player = $sender->getServer()->getPlayer(array_shift($args));

		if($player === $sender){
			$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.message.sameTarget"));
			return true;
		}

		if($player instanceof MineceitPlayer){

			$message = implode(" ", $args);

			$senderName = $sender instanceof MineceitPlayer ? $sender->getDisplayName() : $sender->getName();

			$format = "[{$senderName} -> {$player->getDisplayName()}] ";

			$sender->sendMessage($format . $message);

			if($player->getSettingsInfo()->doesTranslateMessages()){
				$player->sendMessage($format . $message);

				//TranslateUtil::client5_translate($message, $player, $senderLanguage, $format);
			}else{
				$player->sendMessage($format . $message);
			}
		}else{

			$sender->sendMessage(new TranslationContainer("commands.generic.player.notFound"));
		}

		return true;
	}
}
