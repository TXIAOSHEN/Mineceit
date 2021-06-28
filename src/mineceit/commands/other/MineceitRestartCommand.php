<?php

namespace mineceit\commands\other;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MineceitRestartCommand extends MineceitCommand{

	/** @var int */
	private $countdown;

	/* @var Server */
	private $server;

	public function __construct(){
		parent::__construct("restart", "Restart Server.", "Usage: /restart", ['rs']);
		parent::setPermission('permission.mineceit.restart');
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

		$size = count($args);

		if($this->testPermission($sender)){

			$use = true;

			if($sender instanceof MineceitPlayer)
				$use = $this->canUseCommand($sender);
			elseif(!$sender instanceof Player){
				$use = true;
			}

			if($use){

				if($size === 1 && $args[0] === 'confirm'){
					$this->countdown = 5;
					$this->server = $sender->getServer();
					MineceitCore::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(
						function(int $currentTick) : void{
							if($this->countdown > 0) MineceitUtil::broadcastMessage('Server restart in ' . TextFormat::LIGHT_PURPLE . $this->countdown);
							$this->countdown--;
							if($this->countdown === -1){
								$playerHandler = MineceitCore::getPlayerHandler();
								$this->server->setConfigBool("white-list", true);
								foreach($this->server->getOnlinePlayers() as $player){
									if($player instanceof MineceitPlayer) $playerHandler->savePlayerData($player);
									$player->kick(TextFormat::RED . "Server Restart", false);
								}
							}elseif($this->countdown === -90){
								$this->server->shutdown();
							}
						}
					), 20);
				}else $msg = TextFormat::RED . '/restart confirm';
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
