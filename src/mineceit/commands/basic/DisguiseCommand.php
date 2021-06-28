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

class DisguiseCommand extends MineceitCommand{

	public function __construct(){
		parent::__construct('disguise', 'Disguise a name.', 'Usage: /disguise', []);
		parent::setPermission('mineceit.permissions.disguise');
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

				$form = FormUtil::getDisguiseForm($sender);

				$sender->sendFormWindow($form);
			}
		}else $message = TextFormat::RED . "Console can't use this command.";

		if($message !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);

		return true;
	}

	public function testPermission(CommandSender $sender) : bool{

		if($sender instanceof MineceitPlayer && ($this->canDisguise($sender))){
			return true;
		}

		return parent::testPermission($sender);
	}

	public function canDisguise(MineceitPlayer $player) : bool{
		if($player->hasCreatorPermissions() || $player->hasHelperPermissions()) return true;
		$ranks = $player->getRanks(true);
		foreach($ranks as $rank){
			if($rank === "media") return true;
		}
		return false;
	}
}
