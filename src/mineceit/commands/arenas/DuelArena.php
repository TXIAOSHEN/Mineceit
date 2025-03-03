<?php

declare(strict_types=1);

namespace mineceit\commands\arenas;

use mineceit\commands\MineceitCommand;
use mineceit\commands\types\MineceitCommandParameter;
use mineceit\commands\types\ParameterUtil;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class DuelArena extends MineceitCommand{

	/** @var MineceitCommandParameter[]|array */
	private $parameters;

	public function __construct(){
		parent::__construct("arena", "Command used for creating, editing, and deleting duel arenas.", "Usage: /arena <create|delete|p1start|p2start|kits>", []);
		parent::setPermission("mineceit.permission.duelarena");
		$this->parameters = ParameterUtil::getDuelArenaParameters();
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

			$count = count($args);

			if($count >= 1){

				$parameter = strtolower(strval($args[0]));

				if(isset($this->parameters[$parameter])){
					$command = $this->parameters[$parameter];
					$args = array_values(array_diff($args, [$args[0]]));
					$command->execute($sender, $args);
				}else{
					$msg = $this->getUsage();
				}
			}else{
				$msg = $this->getUsage();
			}
		}

		if($msg !== null){
			$sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
		}

		return true;
	}

	public function testPermission(CommandSender $sender) : bool{

		if($sender instanceof MineceitPlayer && $sender->hasOwnerPermissions()){
			return true;
		}

		return parent::testPermission($sender);
	}
}
