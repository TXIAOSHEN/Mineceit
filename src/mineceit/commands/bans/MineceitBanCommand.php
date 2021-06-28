<?php

declare(strict_types=1);

namespace mineceit\commands\bans;

use mineceit\discord\DiscordUtil;
use mineceit\game\FormUtil;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\BanCommand;
use pocketmine\lang\TranslationContainer;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MineceitBanCommand extends BanCommand{

	public function execute(CommandSender $sender, string $commandLabel, array $args){

		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) === 0 && $sender instanceof MineceitPlayer){
			$form = FormUtil::getBanForm($sender, [""]);
			$sender->sendFormWindow($form, ["name" => [""]]);
		}elseif(count($args) === 1 && $sender instanceof MineceitPlayer){
			$form = FormUtil::getBanForm($sender, $args);
			$sender->sendFormWindow($form, ["name" => $args]);
		}elseif(count($args) === 2){
			$name = array_shift($args);
			$reason = trim(implode(" ", $args));

			$banTime = null;
			$expires = 'Forever';
			$theReason = TextFormat::BOLD . TextFormat::RED . 'Network Ban' . "\n\n" . TextFormat::RESET;
			$theReason .= TextFormat::RED . 'Reason ' . TextFormat::DARK_GRAY . '» ' . TextFormat::GRAY . $reason . "\n";
			$theReason .= TextFormat::RED . 'Duration ' . TextFormat::DARK_GRAY . '» ' . TextFormat::GRAY . $expires . "\n";
			$theReason .= TextFormat::GRAY . 'Appeal at: ' . TextFormat::RED . 'https://www.zeqa.net/Appeal';

			if(($player = MineceitUtil::getPlayerExact($name, true)) instanceof Player){
				$name = $player->getName();
			}

			if(MineceitCore::MYSQL_ENABLED){
				$ban = [0 => '', 1 => strtolower($name), 2 => $reason, 3 => $banTime];
				MineceitCore::getBanHandler()->addBanList($ban);
			}else{
				$sender->getServer()->getNameBans()->addBan($name, $reason, $banTime, $sender->getName());
			}

			if(($player = MineceitUtil::getPlayerExact($name, true)) instanceof Player){
				$player->kick($theReason, false);
			}

			$sendername = $sender->getName();
			$announce = TextFormat::GRAY . "-------------------------\n" . TextFormat::RED . "$sendername banned $name\n" . "Reason: " . TextFormat::WHITE . $reason . TextFormat::GRAY . "\n-------------------------";
			$sender->getServer()->broadcastMessage($announce);
			$title = DiscordUtil::boldText("Ban");
			$description = DiscordUtil::boldText("User:") . " {$sendername} \n\n" . DiscordUtil::boldText("Banned:") . " {$name}\n" . DiscordUtil::boldText("Reason:") . " {$reason}\n" . DiscordUtil::boldText("Expires in:") . " {$expires}\n";
			DiscordUtil::sendBan($title, $description, DiscordUtil::RED);

			Command::broadcastCommandMessage($sender, new TranslationContainer("%commands.ban.success", [$player !== null ? $player->getName() : $name]));
		}else{
			$name = array_shift($args);
			$duration = array_pop($args);
			$reason = trim(implode(" ", $args));
			$day = 0;
			$hour = 0;
			$min = 0;

			$regex = '/^([0-9]+d)?([0-9]+h)?([0-9]+m)?$/';
			$matches = [];
			$is_matching = preg_match($regex, $duration, $matches);
			if(!$is_matching){
				$sender->sendMessage(TextFormat::RED . "Duration: Day(d) Hour(h) Minute(m)");
				return true;
			}

			foreach($matches as $index => $match){
				if($index === 0 || strlen($match) === 0) continue;
				$n = substr($match, 0, -1);
				if(substr($match, -1) === 'd'){
					if($n > 100){
						$day = 0;
						$hour = 0;
						$min = 0;
						break;
					}
					$day = $n;
				}elseif(substr($match, -1) === 'h'){
					if($n > 2400){
						$day = 0;
						$hour = 0;
						$min = 0;
						break;
					}
					$hour = $n;
				}elseif(substr($match, -1) === 'm'){
					if($n > 144000){
						$day = 0;
						$hour = 0;
						$min = 0;
						break;
					}
					$min = $n;
				}
			}

			$banTime = new \DateTime('NOW');
			$banTime->modify("+{$day} days");
			$banTime->modify("+{$hour} hours");
			$banTime->modify("+{$min} mins");
			$banTime->format(\DateTime::ISO8601);

			if($day === 0 && $hour === 0 && $min === 0) $banTime = null;

			$banTime === null ? $expires = 'Forever' : $expires = "{$day} day(s) {$hour} hour(s) {$min} min(s)";
			$theReason = TextFormat::BOLD . TextFormat::RED . 'Network Ban' . "\n\n" . TextFormat::RESET;
			$theReason .= TextFormat::RED . 'Reason ' . TextFormat::DARK_GRAY . '» ' . TextFormat::GRAY . $reason . "\n";
			$theReason .= TextFormat::RED . 'Duration ' . TextFormat::DARK_GRAY . '» ' . TextFormat::GRAY . $expires . "\n";
			$theReason .= TextFormat::GRAY . 'Appeal at: ' . TextFormat::RED . 'https://www.zeqa.net/Appeal';

			if(($player = MineceitUtil::getPlayerExact($name, true)) instanceof Player){
				$name = $player->getName();
			}

			if(MineceitCore::MYSQL_ENABLED){
				if($banTime === null) $ban = [0 => '', 1 => strtolower($name), 2 => $reason, 3 => $banTime];
				else $ban = [0 => '', 1 => strtolower($name), 2 => $reason, 3 => $banTime->format('Y-m-d-H-i')];
				MineceitCore::getBanHandler()->addBanList($ban);
			}else{
				$sender->getServer()->getNameBans()->addBan($name, $reason, $banTime, $sender->getName());
			}

			if(($player = MineceitUtil::getPlayerExact($name, true)) instanceof Player){
				$player->kick($theReason, false);
			}

			$sendername = $sender->getName();
			$announce = TextFormat::GRAY . "-------------------------\n" . TextFormat::RED . "$sendername banned $name\n" . "Reason: " . TextFormat::WHITE . $reason . TextFormat::GRAY . "\n-------------------------";
			$sender->getServer()->broadcastMessage($announce);
			$title = DiscordUtil::boldText("Ban");
			$description = DiscordUtil::boldText("User:") . " {$sendername} \n\n" . DiscordUtil::boldText("Banned:") . " {$name}\n" . DiscordUtil::boldText("Reason:") . " {$reason}\n" . DiscordUtil::boldText("Expires in:") . " {$expires}\n";
			DiscordUtil::sendBan($title, $description, DiscordUtil::RED);

			Command::broadcastCommandMessage($sender, new TranslationContainer("%commands.ban.success", [$player !== null ? $player->getName() : $name]));
		}

		return true;
	}


	public function testPermission(CommandSender $target) : bool{
		if($target instanceof MineceitPlayer && $target->hasModPermissions()){
			return true;
		}

		return parent::testPermission($target);
	}
}
