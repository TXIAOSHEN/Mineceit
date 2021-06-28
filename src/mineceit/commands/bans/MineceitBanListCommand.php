<?php

declare(strict_types=1);

namespace mineceit\commands\bans;

use mineceit\player\MineceitPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\BanListCommand;

class MineceitBanListCommand extends BanListCommand{

	public function testPermission(CommandSender $target) : bool{
		if($target instanceof MineceitPlayer && $target->hasModPermissions()){
			return true;
		}

		return parent::testPermission($target);
	}
}
