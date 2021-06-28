<?php

declare(strict_types=1);

namespace mineceit\guild;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use mineceit\MineceitCore;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class GuildForm{
	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function PreGuildForm(MineceitPlayer $player) : ?SimpleForm{

		if(($region = $player->getGuildRegion()) !== '' && $region !== MineceitCore::getRegion()){
			// TODO MSG NOT IN YOUR GUILD REGION
			return null;
		}

		$guild = MineceitCore::getGuildManager()->getGuildfromPlayer($player);
		if($guild === null){
			$player->setGuild('');
			$player->setGuildRegion('');
		}else{
			if($guild->isInGuild($player)){
				return self::MainGuildForm($player);
			}
			if(!$guild->isInJoinRequest($player)){
				$player->setGuild('');
				$player->setGuildRegion('');
			}
		}

		$form = new SimpleForm(function(Player $event, $data = null){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();
				$guild = MineceitCore::getGuildManager()->getGuildfromPlayer($event);

				if($data !== null){
					switch((int) $data){
						case 0:
							$form = self::CreateGuildForm($event);
							$event->sendFormWindow($form);
							break;
						case 1:
							$form = self::JoinGuildForm($event);
							$event->sendFormWindow($form);
							break;
						case 2:
							$guild->removeJoinRequest($event->getName());
							// TODO MSG CANCEL JOIN REQUEST
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle('Guild Menu');
		$form->setContent('Choose Action: ');
		$form->addButton('Create Guild', 0, 'textures/ui/anvil_icon.png');
		$form->addButton('Join Guild', 0, 'textures/ui/creative_icon.png');
		if($player->getGuild() !== ''){
			$form->addButton("Cancel Request to\n" . $player->getGuild(), 0, 'textures/ui/creative_icon.png');
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function MainGuildForm(MineceitPlayer $player) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$guild = MineceitCore::getGuildManager()->getGuildfromPlayer($event);

					switch((int) $data){
						case 0:
							$guild->dailyReward($event);
							break;
						case 1:
							$form = self::LeaveGuildForm($event, $guild);
							$event->sendFormWindow($form);
							break;
						case 2:
							$form = self::GuildMemberManagerForm($event, $guild);
							$event->sendFormWindow($form);
							break;
						case 3:
							$form = self::GuildJoinRequestForm($event, $guild);
							$event->sendFormWindow($form);
							break;
						case 4:
							$form = self::GuildBanManagerForm($event, $guild);
							$event->sendFormWindow($form);
							break;
					}
				}
			}
		});

		$guild = MineceitCore::getGuildManager()->getGuildfromPlayer($player);

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle('Guild Menu');
		$form->setContent("EXP: " . $guild->getExp(true) . "\nResource: " . $guild->getResource(true) . "\n");
		$form->addButton('Daily Login', 0, 'textures/ui/creative_icon.png');
		$form->addButton('Leave Guild', 0, 'textures/ui/creative_icon.png');
		if($player->getName() === $guild->getLeader()){
			$form->addButton('Member Manager', 0, 'textures/ui/creative_icon.png');
			$form->addButton('Join Request', 0, 'textures/ui/creative_icon.png');
			$form->addButton('Unban Player', 0, 'textures/ui/creative_icon.png');
		}
		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Guild          $guild
	 *
	 * @return SimpleForm
	 */
	public static function LeaveGuildForm(MineceitPlayer $player, Guild $guild) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$guildManager = MineceitCore::getGuildManager();
					$guild = $guildManager->getGuildfromPlayer($event);

					switch((int) $data){
						case 0:
							if($guild->getLeader() === $event->getName()){
								$guildManager->removeGuild($guild);
							}else{
								$guild->kick($event->getName());
								$event->setGuild('');
								$event->setGuildRegion('');
							}
							break;
						case 1:
							$form = self::MainGuildForm($event);
							$event->sendFormWindow($form);
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle('Leave Guild');
		if($guild->getLeader() === $player->getName()) $form->setContent("Are you sure to delete guild?" . "\n");
		else $form->setContent("Are you sure you want to leave guild?" . "\n");

		$form->addButton(TextFormat::GREEN . 'YES' . TextFormat::RESET);
		$form->addButton(TextFormat::RED . 'NO' . TextFormat::RESET);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Guild          $guild
	 *
	 * @return SimpleForm
	 */
	public static function GuildMemberManagerForm(MineceitPlayer $player, Guild $guild) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$guild = MineceitCore::getGuildManager()->getGuildfromPlayer($event);
					$members = $guild->getMember();

					if(isset($members[(int) $data])){
						$form = self::GuildMemberManagerDetailForm($event, $guild, $members[(int) $data]);
						$event->sendFormWindow($form);
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();
		$members = $guild->getMember();

		$form->setTitle('Member Manager');
		$form->setContent("EXP: " . $guild->getExp(true) . "\nResource: " . $guild->getResource(true) . "\n");

		foreach($members as $member){
			$form->addButton($member);
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Guild          $guild
	 * @param string         $member
	 *
	 * @return SimpleForm
	 */
	public static function GuildMemberManagerDetailForm(MineceitPlayer $player, Guild $guild, string $member) : ?SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null) use ($member, $guild){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$guild = MineceitCore::getGuildManager()->getGuildfromPlayer($event);

					if($member === $guild->getLeader() || $member === $event->getName()){
						// TODO UNSUCCESS ACTION
						return null;
					}

					switch((int) $data){
						case 0:
							$guild->changeToMember($member);
							// TODO MSG TO MEMBER
							break;
						case 1:
							$guild->changeToOfficer($member);
							// TODO MSG TO OFFICER
							break;
						case 2:
							$guild->kick($member);
							// TODO MSG SUCCESS KICK
							break;
						case 3:
							$guild->ban($member);
							// TODO MSG SUCCESS BAN
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($member);
		$form->setContent("EXP: " . $guild->getExp(true) . "\nResource: " . $guild->getResource(true) . "\n");

		$form->addButton('Change to Member');
		$form->addButton('Change to Officer');
		$form->addButton('Kick');
		$form->addButton('Ban');

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Guild          $guild
	 *
	 * @return SimpleForm
	 */
	public static function GuildJoinRequestForm(MineceitPlayer $player, Guild $guild) : SimpleForm{
		$joinRequest = $guild->getJoinRequest();

		$form = new SimpleForm(function(Player $event, $data = null) use ($joinRequest, $guild){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					$form = self::AcceptGuildJoinRequestForm($event, $guild, $joinRequest[(int) $data]);
					$event->sendFormWindow($form);
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle('Join Request');
		foreach($joinRequest as $request){
			$form->addButton($request);
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Guild          $guild
	 * @param string         $member
	 *
	 * @return SimpleForm
	 */
	public static function AcceptGuildJoinRequestForm(MineceitPlayer $player, Guild $guild, string $member) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null) use ($guild, $member){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					switch((int) $data){
						case 0:
							$guild->addMember($member);
							break;
						case 1:
							$guild->removeJoinRequest($member);
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle('Request ' . $member);
		$form->setContent('Do you want to accept request from ' . $member);
		$form->addButton(TextFormat::GREEN . 'Accept' . TextFormat::RESET);
		$form->addButton(TextFormat::RED . 'Decline' . TextFormat::RESET);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Guild          $guild
	 *
	 * @return SimpleForm
	 */
	public static function GuildBanManagerForm(MineceitPlayer $player, Guild $guild) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$guild = MineceitCore::getGuildManager()->getGuildfromPlayer($event);
					$members = $guild->getBanList();

					if(isset($members[(int) $data])){
						$member = $members[(int) $data];
						$guild->unBan($member);
						// TODO MSG SUCCESS UNBAN
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();
		$members = $guild->getBanList();

		$form->setTitle('Unban Player');
		$form->setContent("EXP: " . $guild->getExp(true) . "\nResource: " . $guild->getResource(true) . "\n");

		if(count($members) === 0){
			$form->addButton('None');
		}else{
			foreach($members as $member){
				$form->addButton($member);
			}
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return CustomForm
	 */
	public static function CreateGuildForm(MineceitPlayer $player) : CustomForm{
		$form = new CustomForm(function(Player $event, $data = null){

			$colors = ["§c", "§d", "§5", "§1", "§b", "§a", "§e", "§6", "§f", "§7", "§0"];
			$profileImages = ['default']; // Todo list of images

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null && $event->isInHub()){
					$name = (string) $data[0];
					$leader = $event->getName();
					$color = $colors[(int) $data[1]];
					$profile = $profileImages[(int) $data[2]];
					$guildTag = $color . $name . TextFormat::RESET;

					if($event->getGuild() !== ''){
						// TODO MSG ALREADY GOT GUILD
						return;
					}

					$nameLength = strlen($name);
					if(!preg_match("/([A-Za-z0-9]+)/", $name) || strpos($name, ' ') !== false || $nameLength < 5 || $nameLength > 15){
						// ONLY NUMBER ALPHABET, NO SPACE ALLOWED, 5-15 character
						return;
					}

					$lang = $event->getLanguageInfo()->getLanguage();

					$guilds = MineceitCore::getGuildManager()->getGuilds();

					$lowerName = strtolower($name);
					foreach($guilds as $guild){
						if(strtolower($guild->getName()) === $lowerName){
							// TODO GUILD ALREDY EXIST MSG
							return;
						}
					}

					$guild = new Guild($name, $leader, $profile, $guildTag);

					$event->setGuild($name);
					$event->setGuildRegion(MineceitCore::getRegion());

					MineceitCore::getGuildManager()->addGuild($guild);
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle("Create Guild");
		$form->addInput("Guild Name: ");
		$colors = ["§cRed", "§dPink", "§5Purple", "§1Blue", "§bCyan", "§aGreen", "§eYellow", "§6Orange", "§fWhite", "§7Grey", "§0Black"];
		$form->addDropdown("Guild Color: ", $colors);
		$profileImages = ['default']; // Todo list of images
		$form->addDropdown("Guild Profile: ", $profileImages);
		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function JoinGuildForm(MineceitPlayer $player) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();
				$lang = $event->getLanguageInfo()->getLanguage();

				if($data !== null){

					$guilds = MineceitCore::getGuildManager()->getGuilds();

					if(isset($guilds[(string) $data])){
						// todo join guild detail form
						$form = self::JoinGuildDetailForm($event, $guilds[(string) $data]);
						$event->sendFormWindow($form);
					}else{
						// todo msg guild not found
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();
		$guilds = MineceitCore::getGuildManager()->getGuilds();

		$form->setTitle('Join Guild');
		$form->setContent('Choose a guild to join:');

		if(count($guilds) === 0){
			$form->addButton($lang->generalMessage(Language::NONE));
			return $form;
		}

		foreach($guilds as $guild){
			$buttonContent = $guild->getGuildTag();
			$buttonContent = $buttonContent . "\n" . $guild->getMember(true) . ' out of ' . $guild->getMaxMember();
			$form->addButton($buttonContent, 0, "", (string) $guild->getName());
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param Guild          $guild
	 *
	 * @return SimpleForm
	 */
	public static function JoinGuildDetailForm(MineceitPlayer $player, Guild $guild) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null) use ($guild){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();
				$lang = $event->getLanguageInfo()->getLanguage();

				if($data !== null){
					if($event->getGuild() !== ''){
						// TODO MSG ALREADY HAVE GUILD
						return;
					}

					switch((int) $data){
						case 0:
							$guild->addJoinRequest($event);
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$content = "Name : " . $guild->getGuildTag() . "\n";
		$content = $content . "Leader : " . $guild->getLeader() . "\n";
		$content = $content . "Member : " . $guild->getMember(true) . '/' . $guild->getMaxMember() . "\n";
		$content = $content . "EXP : " . $guild->getExp(true) . "\n";
		$content = $content . "Resource : " . $guild->getResource(true) . "\n";

		$content = $content . "TankAgent : \n";
		$tankAgent = $guild->getAgent(0);
		foreach($tankAgent as $agent){
			$contentLine = '- ' . $agent->getName() . ' : ' . $agent->getLevel(true) . "\n";
			$content = $content . $contentLine;
		}

		$content = $content . "DamageAgent : \n";
		$damageAgent = $guild->getAgent(1);
		foreach($damageAgent as $agent){
			$contentLine = '- ' . $agent->getName() . ' : ' . $agent->getLevel(true) . "\n";
			$content = $content . $contentLine;
		}

		$content = $content . "SupportAgent : \n";
		$supportAgent = $guild->getAgent(2);
		foreach($supportAgent as $agent){
			$contentLine = '- ' . $agent->getName() . ' : ' . $agent->getLevel(true) . "\n";
			$content = $content . $contentLine;
		}

		$form->setTitle($guild->getGuildTag());
		$form->setContent($content);
		$form->addButton(TextFormat::GREEN . "Join");
		return $form;
	}
}
