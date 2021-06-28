<?php

declare(strict_types=1);

namespace mineceit\commands\ranks;

use mineceit\commands\MineceitCommand;
use mineceit\game\FormUtil;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class CreateRank extends MineceitCommand{

	public function __construct(){
		parent::__construct('createrank', 'Creates a new Rank with the given name.', 'Usage: /createrank <name>', []);
		parent::setPermission('mineceit.permission.toggle-ranks');
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

		if($sender instanceof MineceitPlayer){

			if($this->testPermission($sender) && $this->canUseCommand($sender)){

				$rankForm = FormUtil::getCreateRankForm($sender);

				$sender->sendFormWindow($rankForm);
			}
		}else{

			$msg = TextFormat::RED . "Console can't use this command!";
		}

		if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);


		return true;
	}

	public function testPermission(CommandSender $sender) : bool{

		if($sender instanceof MineceitPlayer && $sender->hasOwnerPermissions()){
			return true;
		}

		return parent::testPermission($sender);
	}
}
