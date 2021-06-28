<?php

declare(strict_types=1);

namespace mineceit\commands\basic;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class HealCommand extends MineceitCommand{

	public function __construct(){
		parent::__construct('heal', 'A command to heal other players.', 'Usage: /heal <player>', []);
		parent::setPermission('mineceit.permission.heal');
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

		$personHealed = null;

		if($sender instanceof MineceitPlayer){

			if($this->testPermission($sender) && $this->canUseCommand($sender)){

				$count = count($args);

				if($count <= 1){

					$player = $sender;
					$lang = $sender->getLanguageInfo()->getLanguage();

					if($count === 1){
						$playerName = $args[0];
						$player = MineceitUtil::getPlayerExact($playerName, true);
						if($player === null || !$player->isOnline())
							$msg = $lang->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $playerName]);
					}

					if($player !== null && $player->isOnline()){
						$lang = $player->getLanguageInfo()->getLanguage();
						$message = $lang->generalMessage(Language::PLAYER_HEAL);
						$player->setHealth($player->getMaxHealth());
						$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
					}
				}else $msg = $this->getUsage();
			}
		}

		if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

		return true;
	}

	public function testPermission(CommandSender $sender) : bool{

		if($sender instanceof MineceitPlayer && $sender->hasHelperPermissions()){
			return true;
		}

		return parent::testPermission($sender);
	}
}
