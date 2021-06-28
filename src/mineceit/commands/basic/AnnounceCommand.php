<?php

declare(strict_types=1);

namespace mineceit\commands\basic;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class AnnounceCommand extends MineceitCommand{

	public function __construct(){
		parent::__construct('announce', 'Announce a message.', 'Usage: /announce message', ['an']);
		parent::setPermission('mineceit.permissions.announce');
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

			if($this->testPermission($sender) && $this->canUseCommand($sender)){

				if(count($args) === 0){
					throw new InvalidCommandSyntaxException();
				}

				$message = trim(implode(" ", $args));

				MineceitUtil::broadcastTranslatedMessage($sender, MineceitUtil::getPrefix() . ' ' . TextFormat::RESET, $message, Server::getInstance()->getOnlinePlayers());
			}
		}else $message = TextFormat::RED . "Console can't use this command.";

		if($message !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);

		return true;
	}

	public function testPermission(CommandSender $sender) : bool{

		if($sender instanceof MineceitPlayer && $sender->hasAdminPermissions()){
			return true;
		}

		return parent::testPermission($sender);
	}
}
