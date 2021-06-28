<?php

declare(strict_types=1);

namespace mineceit\commands;

use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

abstract class MineceitCommand extends Command{

	/* @var bool */
	private $canUseInDuelAndCombat;

	/* @var bool */
	private $canUseAsASpec;

	public function __construct(string $name, string $description = "", string $usageMessage = null, $aliases = [], $canUseAsASpec = false, $canUseInDuelAndCombat = false){
		parent::__construct($name, $description, $usageMessage, $aliases);
		$this->canUseInDuelAndCombat = $canUseInDuelAndCombat;
		$this->canUseAsASpec = $canUseAsASpec;
	}

	/**
	 * @param CommandSender $player
	 *
	 * @return bool
	 */
	public function canUseCommand(CommandSender $player) : bool{

		$result = true;

		$msg = null;

		$playerHandler = MineceitCore::getPlayerHandler();

		$language = $player instanceof MineceitPlayer ? $player->getLanguageInfo()
			->getLanguage() : $playerHandler->getLanguage();

		if($player instanceof MineceitPlayer){

			if($player->isInCombat() || $player->isInDuel() || $player->isInBot() || $player->isInEventDuel() || $player->isInEventBoss()){
				$result = $this->canUseInDuelAndCombat;
				if(!$result){
					if($player->isInDuel() || $player->isInEventDuel() || $player->isInEventBoss() || $player->isInBot())
						$msg = $language->generalMessage(Language::COMMAND_FAIL_IN_DUEL);
					elseif($player->isInCombat())
						$msg = $language->generalMessage(Language::COMMAND_FAIL_IN_COMBAT);
				}
			}elseif($player->isADuelSpec()){
				$result = $this->canUseAsASpec;
				if(!$result){
					$msg = TextFormat::RED . "Can't use this command while spectating a duel!";
				}
			}elseif($player->isFrozen()){
				$result = false;
				$msg = TextFormat::RED . "Can't use this command while got frozen!";
			}elseif($player->getKitHolder()->isEditingKit()){
				$result = false;
				$msg = TextFormat::RED . "Can't use this command while editing kits!";
			}elseif($player->getPartyEvent() !== null){
				$result = false;
				$msg = $language->generalMessage(Language::COMMAND_FAIL_IN_DUEL);
			}
		}

		if($msg !== null) $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);

		return $result;
	}

	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function testPermission(CommandSender $sender) : bool{

		if($this->testPermissionSilent($sender))
			return true;

		$playerHandler = MineceitCore::getPlayerHandler();

		$language = $sender instanceof MineceitPlayer ? $sender->getLanguageInfo()
			->getLanguage() : $playerHandler->getLanguage();

		$message = $language->getPermissionMessage();

		$sender->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);

		return false;
	}
}
