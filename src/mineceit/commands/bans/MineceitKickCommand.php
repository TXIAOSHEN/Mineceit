<?php

declare(strict_types=1);

namespace mineceit\commands\bans;

use mineceit\discord\DiscordUtil;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\BanCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MineceitKickCommand extends BanCommand{

	public function execute(CommandSender $sender, string $commandLabel, array $args){

		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) === 0){
			throw new InvalidCommandSyntaxException();
		}

		if(count($args) === 1){
			$sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RED . 'You can\'t kick player for no reason');
			return true;
		}

		$senderName = $sender->getName();

		$name = array_shift($args);
		$reason = trim(implode(" ", $args));

		$theReason = TextFormat::BOLD . TextFormat::RED . 'Network Kick' . "\n\n" . TextFormat::RESET;
		$theReason .= TextFormat::RED . 'Reason ' . TextFormat::DARK_GRAY . '» ' . TextFormat::GRAY . $reason . "\n";
		$theReason .= TextFormat::RED . 'Kicked by ' . TextFormat::DARK_GRAY . '» ' . TextFormat::GRAY . $senderName;

		if(($player = MineceitUtil::getPlayerExact($name, true)) instanceof Player){
			$player->kick($theReason, false);
		}else{
			$sender->sendMessage(new TranslationContainer("§c" . "%commands.generic.player.notFound"));
			return true;
		}

		$title = DiscordUtil::boldText("Kick");
		$description = DiscordUtil::boldText("User:") . " {$senderName} \n\n" . DiscordUtil::boldText("Kicked:") . " {$name}" . "\n" . DiscordUtil::boldText("Reason:") . " {$reason}" . "\n";
		DiscordUtil::sendBan($title, $description, DiscordUtil::GOLD);

		Command::broadcastCommandMessage($sender, new TranslationContainer("commands.kick.success.reason", [$name, $reason]));

		return true;
	}


	public function testPermission(CommandSender $target) : bool{
		if($target instanceof MineceitPlayer && $target->hasHelperPermissions()){
			return true;
		}

		return parent::testPermission($target);
	}
}
