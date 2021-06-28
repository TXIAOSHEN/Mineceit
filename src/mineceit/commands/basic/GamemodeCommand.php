<?php

declare(strict_types=1);

namespace mineceit\commands\basic;

use mineceit\commands\MineceitCommand;
use mineceit\game\FormUtil;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class GamemodeCommand extends MineceitCommand{

	public function __construct(){
		parent::__construct('gamemode', 'Change gamemode of a player.', 'Usage: /gamemode', ['gm', 'gmui']);
		parent::setPermission("pocketmine.command.gamemode");
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

				$onlinePlayers = $sender->getServer()->getOnlinePlayers();
				$online = [];

				foreach($onlinePlayers as $player){
					$name = $player->getDisplayName();
					$online[] = $name;
				}

				$form = FormUtil::getGamemodeForm($sender, $online);
				$sender->sendFormWindow($form, ['online-players' => $online]);
			}
		}else{
			$message = TextFormat::RED . "Console can't use this command.";
		}

		if($message !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);

		return true;
	}

	public function testPermission(CommandSender $sender) : bool{

		if($sender instanceof MineceitPlayer && ($sender->hasBuilderPermissions() || $sender->hasHelperPermissions())){
			return true;
		}

		return parent::testPermission($sender);
	}
}
