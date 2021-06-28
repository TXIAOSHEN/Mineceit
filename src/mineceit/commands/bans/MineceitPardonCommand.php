<?php

declare(strict_types=1);

namespace mineceit\commands\bans;

use mineceit\discord\DiscordUtil;
use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\PardonCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;

class MineceitPardonCommand extends PardonCommand{

	public function execute(CommandSender $sender, string $commandLabel, array $args){

		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) !== 1){
			throw new InvalidCommandSyntaxException();
		}

		if(MineceitCore::MYSQL_ENABLED){
			MineceitCore::getBanHandler()->removeBanList(strtolower($args[0]));
		}else{
			$sender->getServer()->getNameBans()->remove($args[0]);
		}

		$title = DiscordUtil::boldText("Unban");
		$description = DiscordUtil::boldText("User:") . " {$sender->getName()} \n\n" . DiscordUtil::boldText("Unbanned:") . " {$args[0]}";
		DiscordUtil::sendBan($title, $description, DiscordUtil::GREEN);

		Command::broadcastCommandMessage($sender, new TranslationContainer("commands.unban.success", [$args[0]]));
		return true;
	}

	public function testPermission(CommandSender $target) : bool{
		if($target instanceof MineceitPlayer && $target->hasModPermissions()){
			return true;
		}

		return parent::testPermission($target);
	}
}
