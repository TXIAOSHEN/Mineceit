<?php

declare(strict_types=1);

namespace mineceit\commands\basic;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\utils\TextFormat;

class BattlepassCommand extends MineceitCommand{

	public function __construct(){
		parent::__construct('battlepass', 'Set BattlePass for player', "Usage: /battlepass name on | off", []);
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

		if($this->testPermission($sender) && $this->canUseCommand($sender)){

			if(count($args) <= 1){
				throw new InvalidCommandSyntaxException();
			}

			$name = array_shift($args);

			if(($player = MineceitUtil::getPlayerExact($name, true)) instanceof MineceitPlayer){
				if($args[0] === 'on'){
					$player->setBuyBattlePass(true);
					$msg = TextFormat::GRAY . $player->getDisplayName() . "'s battlepass has set to ElitePass";
				}else{
					$player->setBuyBattlePass(false);
					$msg = TextFormat::GRAY . $player->getDisplayName() . "'s battlepass has set to Free";
				}
			}
		}

		if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

		return true;
	}

	public function testPermission(CommandSender $sender) : bool{

		if($sender instanceof MineceitPlayer && $sender->hasAdminPermissions()){
			return true;
		}

		return parent::testPermission($sender);
	}
}
