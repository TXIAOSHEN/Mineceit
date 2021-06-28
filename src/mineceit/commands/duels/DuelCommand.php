<?php

declare(strict_types=1);

namespace mineceit\commands\duels;

use mineceit\commands\MineceitCommand;
use mineceit\game\FormUtil;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;

class DuelCommand extends MineceitCommand{

	public function __construct(){
		parent::__construct('duel', 'Send a duel request via a form.', 'Usage: /duel', []);
		parent::setPermission('mineceit.permission.duel');
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
			$language = $sender->getLanguageInfo()->getLanguage();
			if($this->testPermission($sender) && $this->canUseCommand($sender)){
				if($sender->isInHub() && !$sender->isInParty()){
					$form = FormUtil::getRequestForm($sender);
					$sender->sendFormWindow($form);
				}else $msg = $language->generalMessage(Language::ONLY_USE_IN_LOBBY);
			}
		}else $msg = TextFormat::RED . "Console can't use this command.";

		if($msg !== null) $sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

		return $msg;
	}
}
