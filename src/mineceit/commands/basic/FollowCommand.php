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

class FollowCommand extends MineceitCommand{

	public function __construct(){
		parent::__construct('follow', 'Follow a player.', 'Usage: /follow', []);
		parent::setPermission('mineceit.permissions.follow');
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

				$sname = $sender->getName();

				if($sender->isFollowing()){

					$followed = $sender->getFollowing();

					if(($player = MineceitUtil::getPlayerExact($followed, true)) !== null && $player instanceof MineceitPlayer){
						$player->setFollower($sname, false);
					}
					$sender->setFollowing();
				}else{

					if(!$sender->isInHub() || $sender->isInParty() || $sender->getKitHolder()->isEditingKit()){
						$message = TextFormat::RED . "Please use this command in hub.";
					}else{
						$onlinePlayers = $sender->getServer()->getOnlinePlayers();
						$online = [];

						foreach($onlinePlayers as $player){
							$name = $player->getDisplayName();
							if($name !== $sname)
								$online[] = $name;
						}

						$form = FormUtil::getFollowForm($sender, $online);

						$sender->sendFormWindow($form, ['online-players' => $online]);
					}
				}
			}
		}else $message = TextFormat::RED . "Console can't use this command.";

		if($message !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);

		return true;
	}

	public function testPermission(CommandSender $sender) : bool{

		if($sender instanceof MineceitPlayer && $sender->hasHelperPermissions()){
			return true;
		}

		return parent::testPermission($sender);
	}
}
