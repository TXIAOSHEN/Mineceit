<?php

declare(strict_types=1);

namespace mineceit\commands\ranks;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class SetRanks extends MineceitCommand{

	public function __construct(){
		parent::__construct('setranks', 'Set ranks of a player.', 'Usage: /setranks <player> <ranks>', []);
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

		$playerHandler = MineceitCore::getPlayerHandler();

		$language = $sender instanceof MineceitPlayer ? $sender->getLanguageInfo()->getLanguage() : $playerHandler->getLanguage();

		if($this->testPermission($sender) && $this->canUseCommand($sender)){

			$size = count($args);
			if($size <= 3 && $size > 1){

				$name = (string) $args[0];
				$server = $sender->getServer();
				$player = $server->getPlayer($name);
				$rankHandler = MineceitCore::getRankHandler();

				if($player !== null && $player instanceof MineceitPlayer){

					if($size === 2){
						$ranks = [$args[1]];
					}else{
						$ranks = [$args[1], $args[2]];
					}

					$validRank = true;

					$theRanks = [];

					foreach($ranks as $rank){
						$theRank = $rankHandler->getRank($rank);
						if($theRank === null){
							$validRank = false;
							break;
						}else{
							$theRanks[] = $theRank;
						}
					}

					if($validRank){

						$player->setRanks($theRanks);
						$msg = $language->generalMessage(Language::PLAYER_SET_RANKS);
					}else{

						$msg = $language->generalMessage(Language::PLAYER_SET_RANKS_FAIL);
					}
				}elseif(!MineceitCore::MYSQL_ENABLED){

					if(file_exists($path = MineceitCore::getDataFolderPath() . 'player/' . $name . '.yml')){

						if($size === 2){
							$ranks = [$args[1]];
						}else{
							$ranks = [$args[1], $args[2]];
						}

						$validRank = true;

						$theRanks = [];

						foreach($ranks as $rank){
							$theRank = $rankHandler->getRank($rank);
							if($theRank === null){
								$validRank = false;
								break;
							}else{
								$theRanks[] = $theRank->getLocalName();
							}
						}

						if($validRank){
							$playerdata = new Config($path, Config::YAML);
							$playerdata->set('ranks', $theRanks);
							$playerdata->save();
							$msg = $language->generalMessage(Language::PLAYER_SET_RANKS);
						}else{
							$msg = $language->generalMessage(Language::PLAYER_SET_RANKS_FAIL);
						}
					}else{
						$msg = TextFormat::RED . "Don't have $name on data!";
					}
				}
			}else $msg = $this->getUsage();
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
