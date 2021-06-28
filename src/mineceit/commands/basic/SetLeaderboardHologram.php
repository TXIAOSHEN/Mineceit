<?php

declare(strict_types=1);

namespace mineceit\commands\basic;

use mineceit\commands\MineceitCommand;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class SetLeaderboardHologram extends MineceitCommand{

	public function __construct(){
		parent::__construct('setLeaderboardHologram', 'Places either a elo or stats leaderboard hologram at current position.', 'Usage: /setLeaderboardHologram <elo:stats:rule:rank>', ['setLB', 'setlb']);
		parent::setPermission('mineceit.permission.leaderboard');
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

			$p = $sender->getPlayer();

			$language = $p->getLanguageInfo()->getLanguage();

			if($this->testPermission($sender) && $this->canUseCommand($sender)){

				if($sender->isInHub()){

					$count = count($args);

					$valid_parameters = ['elo', 'stats', 'rule', 'rank'];

					if($count === 1 && in_array(strtolower($args[0]), $valid_parameters)){

						$param = strtolower($args[0]);

						MineceitCore::getLeaderboards()->setLeaderboardHologram($p, $param);

						$msg = $language->generalMessage(Language::PLACE_LEADERBOARD_HOLOGRAM);
					}else{

						$msg = $this->getUsage();
					}
				}else $msg = $language->generalMessage(Language::ONLY_USE_IN_LOBBY);
			}
		}else $msg = "Console can't use this command.";


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
