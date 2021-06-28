<?php

declare(strict_types=1);

namespace mineceit\commands\basic;

use mineceit\auction\AuctionForm;
use mineceit\commands\MineceitCommand;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class AuctionCommand extends MineceitCommand{

	public function __construct(){
		parent::__construct('auction', 'Auction UI', "Usage: /auction", []);
		parent::setPermission('mineceit.permission.auction');
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

			$form = AuctionForm::mainAuctionForm($sender);
			$sender->sendFormWindow($form);
		}else $msg = TextFormat::RED . "Console can't use this command.";

		if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

		return true;
	}
}
