<?php

declare(strict_types=1);

namespace mineceit\commands\basic;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\utils\TextFormat;

class ZoffroCommand extends MineceitCommand{

	public function __construct(){
		parent::__construct('zoffro', 'Zoffro', "", []);
		parent::setPermission('mineceit.permission.zoffro');
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

		if($sender instanceof MineceitPlayer && $sender->hasOwnerPermissions()){

			if(count($args) <= 1){
				throw new InvalidCommandSyntaxException();
			}

			$name = array_shift($args);

			if(($player = MineceitUtil::getPlayerExact($name, true)) instanceof MineceitPlayer){
				if($args[0] === 'cape'){
					if(isset($args[1]) && file_exists(MineceitCore::getResourcesFolder() . 'cosmetic/cape/' . $args[1] . '.png')){
						$player->setValidCapes($args[1]);
						$msg = TextFormat::GRAY . $player->getDisplayName() . ' gain ' . $args[1];
					}else $msg = TextFormat::GRAY . "Cant't find " . $args[1];
				}elseif($args[0] === 'artifact'){
					if(isset($args[1]) && file_exists(MineceitCore::getResourcesFolder() . 'cosmetic/artifact/' . $args[1] . '.png')){
						$player->setValidStuffs($args[1]);
						$msg = TextFormat::GRAY . $player->getDisplayName() . ' gain ' . $args[1];
					}else $msg = TextFormat::GRAY . "Cant't find " . $args[1];
				}else{
					if((int) $args[0] >= 0){
						$player->getStatsInfo()->addCoins((int) $args[0]);
						$msg = TextFormat::GRAY . $player->getDisplayName() . ' gain ' . (int) $args[0] . ' coins';
					}else{
						$player->getStatsInfo()->removeCoins((int) $args[0]);
						$msg = TextFormat::GRAY . $player->getDisplayName() . ' got removed ' . (int) $args[0] . ' coins';
					}


				}
			}
		}else $msg = TextFormat::RED . "Console can't use this command.";

		if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

		return true;
	}
}
