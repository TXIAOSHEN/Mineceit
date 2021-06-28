<?php

declare(strict_types=1);

namespace mineceit\game;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use mineceit\arenas\DuelArena;
use mineceit\arenas\FFAArena;
use mineceit\auction\AuctionForm;
use mineceit\discord\DiscordUtil;
use mineceit\duels\groups\MineceitDuel;
use mineceit\duels\requests\DuelRequest;
use mineceit\game\inventories\menus\PostMatchInv;
use mineceit\maitenance\reports\data\BugReport;
use mineceit\maitenance\reports\data\HackReport;
use mineceit\maitenance\reports\data\ReportInfo;
use mineceit\maitenance\reports\data\StaffReport;
use mineceit\maitenance\reports\data\TranslateReport;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\parties\MineceitParty;
use mineceit\parties\requests\PartyRequest;
use mineceit\player\info\duels\DuelInfo;
use mineceit\player\info\duels\duelreplay\info\DuelReplayInfo;
use mineceit\player\info\duels\duelreplay\MineceitReplay;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\player\ranks\Rank;
use mineceit\scoreboard\Scoreboard;
use pocketmine\command\Command;
use pocketmine\entity\Skin;
use pocketmine\lang\TranslationContainer;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Timezone;

class FormUtil{

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getFFAForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					if(isset($formData[$data]['text'])){
						$arenaName = (string) $formData[$data]['text'];
					}else return;

					$arenaName = TextFormat::clean(explode("\n", $arenaName)[0]);
					if($arenaName === 'One In The Chamber') $arenaName = 'OITC';
					$arena = MineceitCore::getArenas()->getArena($arenaName);

					if($arena !== null && $arena instanceof FFAArena && !$event->isInDuel()){

						if($event->isInQueue()){
							MineceitCore::getDuelHandler()->removeFromQueue($event, false);
						}

						$event->teleportToFFAArena($arena);
					}
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();
		$numPlayersStr = $language->formWindow(Language::FFA_FORM_NUMPLAYERS);

		$form->setTitle($language->formWindow(Language::FFA_FORM_TITLE));
		$form->setContent($language->formWindow(Language::FFA_FORM_DESC));

		$arenaHandler = MineceitCore::getArenas();
		$arenas = $arenaHandler->getFFAArenas();

		$size = count($arenas);

		if($size <= 0){
			$form->addButton($language->generalMessage(Language::NONE));
			return $form;
		}

		foreach($arenas as $arena){

			$name = $arena->getName();
			if($name === 'OITC') $name = 'One In The Chamber';
			$players = $arenaHandler->getPlayersInArena($arena);
			$str = TextFormat::LIGHT_PURPLE . $name . "\n" . TextFormat::WHITE . $players . ' ' . $numPlayersStr;
			$texture = $arena->getTexture();
			$form->addButton($str, 0, $texture);
		}
		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getBotForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					if($event->isInHub()){
						$duelHandler = MineceitCore::getDuelHandler();
						if($event->isInQueue()){
							$duelHandler->removeFromQueue($event, false);
						}

						MineceitCore::getBotHandler()->placeInDuel($event, (string) $data);
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::HUB_BOT_FORM_TITLE));

		$form->setContent($lang->formWindow(Language::HUB_BOT_FORM_DESC));

		$form->addButton(
			$lang->formWindow(Language::HUB_BOT_EASY_FORM),
			0,
			"textures/items/iron_ingot.png",
			"EasyBot"
		);

		$form->addButton(
			$lang->formWindow(Language::HUB_BOT_MEDIUM_FORM),
			0,
			"textures/items/gold_ingot.png",
			"MediumBot"
		);

		$form->addButton(
			$lang->formWindow(Language::HUB_BOT_HARD_FORM),
			0,
			"textures/items/diamond.png",
			"HardBot"
		);

		$form->addButton(
			$lang->formWindow(Language::HUB_BOT_HACKER_FORM),
			0,
			"textures/items/emerald.png",
			"HackerBot"
		);

		$form->addButton(
			$lang->formWindow(Language::HUB_BOT_CLUTCH_FORM),
			0,
			"textures/items/snowball.png",
			"ClutchBot"
		);
		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getEventsForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					$theEvent = MineceitCore::getEventManager()->getEventFromIndex((int) $data);
					if($theEvent !== null){
						if($event->isInQueue()){
							MineceitCore::getDuelHandler()->removeFromQueue($event, false);
						}
						$theEvent->addPlayer($event);
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->getMessage(Language::EVENT_FORM_TITLE));
		$form->setContent($lang->getMessage(Language::EVENT_FORM_DESC));

		$events = MineceitCore::getEventManager()->getEvents();
		if(count($events) <= 0){
			$form->addButton($lang->getMessage(Language::NONE));
			return $form;
		}

		foreach($events as $event){

			$form->addButton(
				$event->formatForForm($lang),
				0,
				$event->getArena()->getTexture()
			);
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getHostEventsForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					$theEvent = MineceitCore::getEventManager()->getEventFromIndex((int) $data);
					if($theEvent !== null){
						if($theEvent->hasOpened()){
							$time = time();
							if(!$event->isOp()){
								$diff = $time - $event->getLastTimeHosted();
								$flag = false;
								$ranks = $event->getRanks(true);
								foreach($ranks as $rank){
									if($rank === "booster" || $rank === "media") $flag = true;
								}

								if(($event->hasCreatorPermissions() || $flag) && $diff < 5400){
									$left = 90 - ceil($diff / 100);
									$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "You can't host events right now, you're currently in a cooldown: {$left} min(s) left to host again!");
									return;
								}elseif($event->hasVipPermissions() && $diff < 3600){
									$left = 60 - ceil($diff / 100);
									$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "You can't host events right now, you're currently in a cooldown: {$left} min(s) left to host again!");
									return;
								}elseif($event->hasVipPlusPermissions() && $diff < 1800){
									$left = 30 - ceil($diff / 100);
									$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::RED . "You can't host events right now, you're currently in a cooldown: {$left} min(s) left to host again!");
									return;
								}
							}

							$theEvent->setOpened($event->hasAdminPermissions());
							$event->setLastTimeHosted($time);
							$event->getServer()->broadcastMessage("\n");
							$message = TextFormat::LIGHT_PURPLE . $theEvent->getName() . ' Event' . TextFormat::RESET . ' has started! go to Lobby to join!.';
							MineceitUtil::broadcastTranslatedMessage($event, MineceitUtil::getPrefix() . ' ' . TextFormat::RESET, $message, Server::getInstance()->getOnlinePlayers());
							$event->getServer()->broadcastMessage("\n");
						}else{
							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . 'Event has already started!');
						}
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->getMessage(Language::EVENTHOST_FORM_TITLE));
		$form->setContent($lang->getMessage(Language::EVENTHOST_FORM_DESC));

		$events = MineceitCore::getEventManager()->getEvents();
		if(count($events) <= 0){
			$form->addButton($lang->getMessage(Language::NONE));
			return $form;
		}

		foreach($events as $event){

			$form->addButton(
				$event->getName(),
				0,
				$event->getArena()->getTexture()
			);
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getSettingsMenu(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					switch((int) $data){
						case 0:
							$statsArr = MineceitCore::getPlayerHandler()->listStats($event, $event->getLanguageInfo()->getLanguage());
							$msg = implode("\n", $statsArr);
							$event->sendMessage($msg);
							break;
						case 1:
							$form = self::getChangeSettingsForm($event);
							$event->sendFormWindow($form);
							break;
						case 2:
							$form = self::getLanguageForm($event->getLanguageInfo()->getLanguage()->getLocale());
							$event->sendFormWindow($form, ["locale" => $event->getLanguageInfo()->getLanguage()->getLocale()]);
							break;
						case 3:
							if($event->isInParty()){
								$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $event->getLanguageInfo()->getLanguage()->generalMessage(Language::CANT_USE_INPARTY));
								return;
							}
							$form = self::getEditKitForm($event);
							$event->sendFormWindow($form);
							break;
						case 4:
							$form = self::getCosmeticForm($event);
							$event->sendFormWindow($form);
							break;
						case 5:
							$perms = $event->getReportPermissions();
							$form = self::getReportMenuForm($event, $perms);
							$event->sendFormWindow($form, ["perms" => $perms]);
							break;
						case 6:
							$event->getSettingsInfo()
								->getBuilderModeInfo()->updateBuilderLevels();
							$builderLevels = $event->getSettingsInfo()
								->getBuilderModeInfo()->getBuilderLevels();
							$form = self::getBuilderModeForm($event, $builderLevels);
							$event->sendFormWindow($form, ['builder-levels' => array_keys($builderLevels)]);
							break;
					}
				}
			}
		});

		// $player->reloadPermissions();

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::SETTINGS_FORM_TITLE));

		$form->addButton(
			$lang->formWindow(Language::STATS_FORM),
			0,
			'textures/ui/copy.png'
		);

		$form->addButton(
			$lang->formWindow(Language::SETTINGS_FORM_CHANGE_SETTINGS),
			0,
			'textures/ui/settings_glyph_color_2x.png'
		);

		$form->addButton(
			$lang->formWindow(Language::CHANGE_LANGUAGE_FORM),
			0,
			'textures/items/book_writable.png'
		);

		$form->addButton(
			$lang->formWindow(Language::KIT_EDIT_FORM_SETTINGS),
			0,
			'textures/ui/icon_recipe_equipment.png'
		);

		$form->addButton(
			$lang->formWindow(Language::COSMETIC_FORM),
			0,
			'textures/ui/dressing_room_skins.png'
		);

		$form->addButton(
			$lang->formWindow(Language::REPORTS_MENU_FORM_BUTTON),
			0,
			'textures/items/blaze_rod.png'
		);

		if($player->hasBuilderPermissions()){

			$form->addButton(
				$lang->generalMessage(Language::BUILDER_MODE_FORM_ENABLE),
				0,
				'textures/items/diamond_pickaxe.png'
			);
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return CustomForm
	 */
	public static function getChangeSettingsForm(MineceitPlayer $player) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$resultScoreboard = (bool) $data[0];
					if($event->getSettingsInfo()->isScoreboardEnabled() !== $resultScoreboard){
						$scoreboardType = !$resultScoreboard ? Scoreboard::SCOREBOARD_NONE
							: Scoreboard::SCOREBOARD_SPAWN;
						$event->getScoreboardInfo()->setScoreboard($scoreboardType);
						$event->getSettingsInfo()->setScoreboardEnabled($resultScoreboard);
					}

					$resultPeOnly = $event->getClientInfo()->isPE() ? (bool) $data[1] : false;

					if($event->getSettingsInfo()->hasPEOnlyQueues() !== $resultPeOnly){
						$event->getSettingsInfo()->setPEOnlyQueues($resultPeOnly);
					}

					$cpsPopup = (bool) $data[2];
					if($event->getSettingsInfo()->isCpsPopupEnabled() !== $cpsPopup){
						$event->getSettingsInfo()->setCpsPopupEnabled($cpsPopup);
					}

					$autoSprint = (bool) $data[3];
					if($event->getSettingsInfo()->isAutoSprintEnabled() !== $autoSprint){
						$event->getSettingsInfo()->setAutoSprintEnabled($autoSprint);
					}

					$autoRespawn = (bool) $data[4];
					if($event->getSettingsInfo()->isAutoRespawnEnabled() !== $autoRespawn){
						$event->getSettingsInfo()->setAutoRespawnEnabled($autoRespawn);
					}

					$moreCrit = (bool) $data[5];
					if($event->getSettingsInfo()->isMoreCritsEnabled() !== $moreCrit){
						$event->getSettingsInfo()->setMoreCritsEnabled($moreCrit);
					}

					$swishSounds = (bool) $data[6];
					if($event->getSettingsInfo()->isSwishSoundEnabled() !== $swishSounds){
						$event->getSettingsInfo()->setSwishSoundEnabled($swishSounds);
					}

					$lightning = (bool) $data[7];
					if($event->getSettingsInfo()->isLightningDeathEnabled() !== $lightning){
						$event->getSettingsInfo()->setLightningDeathEnabled($lightning);
					}

					$blood = (bool) $data[8];
					if($event->getSettingsInfo()->isBloodEnabled() !== $blood){
						$event->getSettingsInfo()->setBloodEnabled($blood);
					}

					$autoGG = (bool) $data[9];
					if($event->getSettingsInfo()->isAutoGGEnabled() !== $autoGG){
						$event->getSettingsInfo()->setAutoGGEnabled($autoGG);
					}

					$translate = (bool) $data[10];
					if($event->getSettingsInfo()->doesTranslateMessages() !== $translate){
						$event->getSettingsInfo()->setTranslateMessages($translate);
					}

					$silent = false;
					if(isset($data[11])) $silent = (bool) $data[11];
					if($event->isSilentStaffEnabled() !== $silent){
						$event->setSilentStaffEnabled($silent);
					}
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($language->formWindow(Language::CHANGE_SETTINGS_FORM_TITLE));

		$form->addToggle(
			$language->formWindow(Language::CHANGE_SETTINGS_FORM_SCOREBOARD),
			$player->getSettingsInfo()->isScoreboardEnabled()
		);

		$form->addToggle(
			$language->formWindow(Language::CHANGE_SETTINGS_FORM_PEONLY),
			$player->getSettingsInfo()->hasPEOnlyQueues()
		);

		$form->addToggle(
			$language->formWindow(Language::CHANGE_SETTINGS_FORM_CPSPOPUP),
			$player->getSettingsInfo()->isCpsPopupEnabled()
		);

		$form->addToggle(
			$language->formWindow(Language::CHANGE_SETTINGS_FORM_AUTOSPRINT),
			$player->getSettingsInfo()->isAutoSprintEnabled()
		);

		$form->addToggle(
			$language->formWindow(Language::CHANGE_SETTINGS_FORM_AUTORESPAWN),
			$player->getSettingsInfo()->isAutoRespawnEnabled()
		);

		$form->addToggle(
			$language->formWindow(Language::CHANGE_SETTINGS_FORM_MORECRIT),
			$player->getSettingsInfo()->isMoreCritsEnabled()
		);

		$form->addToggle(
			$language->getMessage(Language::CHANGE_SETTINGS_FORM_HIT_SOUNDS),
			$player->getSettingsInfo()->isSwishSoundEnabled()
		);

		$form->addToggle(
			$language->getMessage(Language::CHANGE_SETTINGS_FORM_LIGHTNING),
			$player->getSettingsInfo()->isLightningDeathEnabled()
		);

		$form->addToggle(
			$language->getMessage(Language::CHANGE_SETTINGS_FORM_BLOOD),
			$player->getSettingsInfo()->isBloodEnabled()
		);

		$form->addToggle(
			$language->formWindow(Language::CHANGE_SETTINGS_FORM_AUTOGG),
			$player->getSettingsInfo()->isAutoGGEnabled()
		);

		$form->addToggle(
			$language->formWindow(Language::TRANSLATE_MESSAGES),
			$player->getSettingsInfo()->doesTranslateMessages()
		);

		if($player->hasHelperPermissions()){
			$form->addToggle(
				$language->formWindow(Language::SILENT_MESSAGES),
				$player->isSilentStaffEnabled()
			);
		}

		return $form;
	}

	/**
	 * @param string $locale
	 *
	 * @return SimpleForm
	 */
	public static function getLanguageForm(string $locale) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					$locale = (string) $formData["locale"];

					$id = intval($data);

					if(isset($formData[$id])){

						$text = TextFormat::clean(strval($formData[$id]['text']));
						if(strpos($text, "\n") !== false){
							$text = strval(explode("\n", $text)[0]);
						}

						$lang = trim($text);

						$language = MineceitCore::getPlayerHandler()->getLanguageFromName($lang, $locale);

						if($language !== null && ($newLocale = $language->getLocale()) !== $locale){

							$event->getLanguageInfo()->setLanguage($newLocale);
							$sb = $event->getScoreboardInfo();
							$sb->reloadScoreboard();

							$itemHandler = MineceitCore::getItemHandler();

							if($event->isInEvent()){
								$itemHandler->spawnEventItems($event);
							}elseif($event->isInHub()){
								$itemHandler->spawnHubItems($event);
							}
						}
					}
				}
			}
		});

		$olayerManager = MineceitCore::getPlayerHandler();

		$playerLanguage = $olayerManager->getLanguage($locale);

		$form->setTitle($playerLanguage->formWindow(Language::LANGUAGE_FORM_TITLE));

		$languages = $olayerManager->getLanguages();

		foreach($languages as $lang){

			$texture = "textures/blocks/glass_white.png";
			$name = $lang->getNameFromLocale($locale);

			if($lang->getLocale() === $locale){
				$texture = "textures/blocks/glass_green.png";
				$name = TextFormat::GREEN . $name;
			}

			if($lang->hasCredit()){
				$languageCredit = $lang->getCredit();
				$creditMessage = $playerLanguage->getMessage(Language::LANGUAGE_CREDIT);
				$credit = TextFormat::RESET . TextFormat::DARK_GRAY . "{$creditMessage}: {$languageCredit}";

				$name .= "\n{$credit}";
			}

			$form->addButton($name, 0, $texture);
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getEditKitForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null && $formData !== null){

					if(isset($formData[$data]['text'])){
						$kit = TextFormat::clean(explode("\n", $formData[$data]['text'])[0]);
					}else{
						return;
					}

					if($event->isInHub()){

						if($event->isInQueue()){
							MineceitCore::getDuelHandler()->removeFromQueue($event, false);
						}

						$lang = $event->getLanguageInfo()->getLanguage();
						if($event->getKitHolder()->setEditingKit($kit)){
							$event->sendMessage("\n\n" . $lang->generalMessage(Language::KIT_EDIT_MODE));
							$event->sendMessage("\n\n");
						}
					}
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($language->formWindow(Language::KIT_EDIT_FORM_TITLE));
		$form->setContent($language->formWindow(Language::KIT_EDIT_FORM_DESC));

		$list = MineceitCore::getKits()->getKits();

		foreach($list as $kit){

			$name = $kit->getName();

			$form->addButton(
				$name,
				0,
				$kit->getMiscKitInfo()->getTexture()
			);
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getCosmeticForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				$lang = $event->getLanguageInfo()->getLanguage();

				if($data !== null){

					switch((int) $data){

						case 0:
							$form = self::getTagForm($event);
							$event->sendFormWindow($form);
							break;
						case 1:
							$form = self::getCapeForm($event);
							$event->sendFormWindow($form);
							break;
						case 2:
							$form = self::getArtifactForm($event);
							$event->sendFormWindow($form);
							break;
						case 3:
							$event->setCape('');
							$event->setStuff('');
							MineceitCore::getCosmeticHandler()->resetSkin($event);
							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::RESET_SKIN_MSG));
							break;
						case 4:
							$form = self::getPotColorForm($event);
							$event->sendFormWindow($form);
							break;
						case 5:
							$form = self::getDisguiseForm($event);
							$event->sendFormWindow($form);
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::COSMETIC_FORM_TITLE));

		$form->addButton(
			$lang->formWindow(Language::TAG_CATELOGY),
			0,
			'textures/items/name_tag.png'
		);

		$form->addButton(
			$lang->formWindow(Language::CUSTOM_CAPES),
			0,
			'textures/ui/dressing_room_capes.png'
		);

		$form->addButton(
			$lang->formWindow(Language::ARTIFACT_FORM),
			0,
			'textures/ui/dressing_room_skins.png'
		);

		$form->addButton(
			$lang->formWindow(Language::RESET_SKIN),
			0,
			'textures/ui/refresh_hover.png'
		);

		if($player->hasVipPermissions() || $player->hasBuilderPermissions() || $player->hasHelperPermissions()){

			$form->addButton(
				$lang->formWindow(Language::POT_COLORS),
				0,
				'textures/items/potion_bottle_splash_heal.png'
			);
		}

		$flag = false;
		$ranks = $player->getRanks(true);
		foreach($ranks as $rank){
			if($rank === "media") $flag = true;
		}

		if($flag || $player->hasCreatorPermissions() || $player->hasHelperPermissions()){

			$form->addButton(
				$lang->formWindow(Language::CUSTOM_DISGUISE),
				0,
				'textures/ui/FriendsDiversity.png'
			);
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getTagForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				$lang = $event->getLanguageInfo()->getLanguage();

				if($data !== null){
					$index = (int) $data;

					$tags = $event->getValidTags();

					if(isset($tags[$index])){
						$tag = $tags[$index];

						if($tag === 'None') $tag = '';

						$event->setTag($tag);
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->formWindow(Language::CHANGE_TAG_TO) . $tag);
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::TAG_CATELOGY_TITLE));

		$validtags = $player->getValidTags();
		foreach($validtags as $tag){
			$form->addButton($tag);
		}
		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getCapeForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				$lang = $event->getLanguageInfo()->getLanguage();

				if($data !== null){
					if($data === "None") return;
					else $event->setCape($data);

					if($event->getDisguiseInfo()->isDisguised()){
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::CAPE_WHILE_DISGUISE));
						return;
					}

					$oldSkin = $event->getSkin();
					$capeData = MineceitCore::getCosmeticHandler()->getCapeData($data);
					$setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $capeData, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
					$event->setSkin($setCape);
					$event->sendSkin();
					$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::CHANGE_CAPE_TO) . " {$data}.");
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::CUSTOM_CAPES));

		$form->setContent($lang->formWindow(Language::CAPE_CONTENT));

		$validcapes = $player->getValidCapes();

		if(count($validcapes) <= 1){
			$form->addButton("None", -1, "", "None");
			return $form;
		}

		foreach($validcapes as $cape){
			if($cape === "None") continue;
			$form->addButton($cape, -1, "", $cape);
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getArtifactForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					if($data === "None") return;

					$lang = $event->getLanguageInfo()->getLanguage();
					$cosmetic = MineceitCore::getCosmeticHandler();
					if(($key = array_search($data, $cosmetic->cosmeticAvailable)) !== false){
						if($event->getDisguiseInfo()->isDisguised()){
							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::COSMETIC_WHILE_DISGUISE));
							return;
						}
						if(strpos($data, 'SP-') !== false){
							$event->setCape('');
							$event->setStuff('');
							$cosmetic->setCostume($event, $cosmetic->cosmeticAvailable[$key]);
						}else{
							$event->setStuff($cosmetic->cosmeticAvailable[$key]);
							$cosmetic->setSkin($event, $cosmetic->cosmeticAvailable[$key]);
						}
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::CHANGE_COSMETIC_TO) . " {$cosmetic->cosmeticAvailable[$key]}.");
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::CUSTOM_SKINS));

		$form->setContent($lang->formWindow(Language::SKIN_CONTENT));

		$validstuffs = $player->getValidStuffs();

		if(count($validstuffs) <= 1){
			$form->addButton("None", -1, "", "None");
			return $form;
		}

		foreach($validstuffs as $stuff){
			if($stuff === "None") continue;
			$form->addButton($stuff, -1, "", $stuff);
		}


		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return CustomForm
	 */
	public static function getPotColorForm(MineceitPlayer $player) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				$lang = $event->getLanguageInfo()->getLanguage();

				if($data !== null){

					switch($data[0]){
						case 0:
							$event->setPotColor("default");
							break;
						case 1:
							$event->setPotColor("pink");
							break;
						case 2:
							$event->setPotColor("purple");
							break;
						case 3:
							$event->setPotColor("blue");
							break;
						case 4:
							$event->setPotColor("cyan");
							break;
						case 5:
							$event->setPotColor("green");
							break;
						case 6:
							$event->setPotColor("yellow");
							break;
						case 7:
							$event->setPotColor("orange");
							break;
						case 8:
							$event->setPotColor("white");
							break;
						case 9:
							$event->setPotColor("grey");
							break;
						case 10:
							$event->setPotColor("black");
							break;
					}
					if($event->getDisguiseInfo()->isDisguised()){
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::POT_WHILE_DISGUISE));
						return;
					}
					$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::CHANGE_POT_COLOR_TO) . ' ' . $event->getPotColor() . '.');
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::POT_COLORS));
		$colors = ["§cDefault", "§dPink", "§5Purple", "§1Blue", "§bCyan", "§aGreen", "§eYellow", "§6Orange", "§fWhite", "§7Grey", "§0Black"];
		$form->addStepSlider($lang->formWindow(Language::POT_COLORS), $colors, -1);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getDisguiseForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					switch((int) $data){

						case 0:
							$form = self::setDisguiseForm($event);
							$event->sendFormWindow($form);
							break;
						case 1:
							$event->getDisguiseInfo()->setDisguised(false);
							$cosmetic = MineceitCore::getCosmeticHandler();
							$cosmetic->resetSkin($event);
							$event->setCosmetic();
							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $event->getLanguageInfo()->getLanguage()->generalMessage(Language::RESET_DISGUISE));
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::CUSTOM_DISGUISE));

		$form->setContent($lang->formWindow(Language::DISGUISE_CONTENT));
		$form->addButton(
			$lang->formWindow(Language::DISGUISE_BUTTON),
			0,
			'textures/ui/blindness_effect.png'
		);
		$form->addButton(
			$lang->formWindow(Language::UNDISGUISE_BUTTON),
			0,
			'textures/ui/night_vision_effect.png'
		);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return CustomForm
	 */
	public static function setDisguiseForm(MineceitPlayer $player) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				$lang = $event->getLanguageInfo()->getLanguage();
				if($data !== null){
					if(!preg_match('/[^A-Za-z0-9]/', $data[0])){
						if(strlen($data[0]) <= 15){
							$onlinePlayers = $event->getServer()->getOnlinePlayers();
							$name = [];
							$displayname = [];
							foreach($onlinePlayers as $player){
								$name[] = strtolower($player->getName());
								$displayname[] = strtolower($player->getDisplayName());
							}
							if(in_array(strtolower($data[0]), $name)){
								$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::DISGUISE_ONLINE_PLAYER));
								return true;
							}
							if(in_array(strtolower($data[0]), $displayname)){
								$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::DISGUISE_ONLINE_PLAYER));
								return true;
							}

							// Sets the disguise data for the player.
							$disguiseInfo = $event->getDisguiseInfo();
							$disguisedData = $disguiseInfo->getDisguiseData();
							$disguisedData->setDisplayName($data[0]);
							$disguisedData->setSkin(MineceitCore::getCosmeticHandler()
								->getSteveSkin($event->getSkin()->getSkinId()));
							$disguiseInfo->setDisguised(true);

							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::SUCCESS_DISGUISE) . " {$data[0]}");
						}else{
							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::MORE_ALPHABETS_15));
						}
					}else{
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::ONLY_ENG));
					}
					return true;
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::CUSTOM_DISGUISE));
		$form->addInput($lang->formWindow(Language::DISGUISE_INPUT));

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param int            $perms
	 *
	 * @return SimpleForm
	 *
	 * The general report menu form.
	 */
	public static function getReportMenuForm(MineceitPlayer $player, int $perms = 0) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					if(!isset($formData["perms"])) return;

					$perms = (int) $formData["perms"];
					$index = (int) $data;

					switch($index){
						case 0:
							$staffMembers = MineceitCore::getPlayerHandler()->getStaffOnline(true, $event);
							$form = self::getReportStaffForm($event, $staffMembers);
							if($form !== null){
								$event->sendFormWindow($form, ['staff' => $staffMembers]);
							}
							break;
						case 1:
							$form = self::getReportBugForm($event);
							$event->sendFormWindow($form);
							break;
						case 2:
							$playerManager = MineceitCore::getPlayerHandler();
							$languages = array_values($playerManager->getLanguages());
							$form = self::getReportMisTranslationForm($event, $languages);
							$outputLanguages = array_map(function(Language $lang){
								return $lang->getLocale();
							}, $languages);
							$event->sendFormWindow($form, ["languages" => $outputLanguages]);
							break;
						case 3:

							$onlinePlayers = $event->getServer()->getOnlinePlayers();
							$players = array_diff(array_map(function(Player $player){
								return $player->getDisplayName();
							}, $onlinePlayers), [$event->getDisplayName()]);
							$players = array_values($players);

							$form = self::getReportHackerForm($event, $players);
							if($form !== null){
								$event->sendFormWindow($form, ["players" => $players]);
							}
							break;

						case 4:

							$reports = array_values($event->getReportsInfo()->getReportHistory());
							$form = self::getListOfReports($event, $reports);

							if($perms !== ReportInfo::PERMISSION_NORMAL){
								$form = self::getViewReportsSearchForm($event);
							}

							if($form !== null){
								$event->sendFormWindow($form, ['reports' => $reports, 'history' => true, 'perm' => $event->getReportPermissions()]);
							}

							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$title = $lang->getMessage(Language::REPORTS_MENU_FORM_TITLE);
		$form->setTitle($title);

		$desc = $lang->getMessage(Language::REPORTS_MENU_FORM_DESC);
		$form->setContent($desc);

		$types = [
			$lang->getMessage(Language::REPORTS_MENU_FORM_STAFF) => "textures/ui/op.png",
			$lang->getMessage(Language::REPORTS_MENU_FORM_BUG) => "textures/ui/smithing_icon.png",
			$lang->getMessage(Language::REPORTS_MENU_FORM_TRANSLATION) => "textures/ui/Feedback.png",
			$lang->getMessage(Language::REPORTS_MENU_FORM_HACKER) => "textures/ui/dressing_room_customization.png"
		];

		foreach($types as $type => $texture){
			$form->addButton($type, 0, $texture);
		}

		$text = $lang->getMessage(Language::REPORTS_MENU_FORM_YOUR_HISTORY);

		switch($perms){
			case ReportInfo::PERMISSION_VIEW_ALL_REPORTS:
				$text = $lang->getMessage(Language::REPORTS_MENU_FORM_VIEW_REPORTS);
				break;
			case ReportInfo::PERMISSION_MANAGE_REPORTS:
				$text = $lang->getMessage(Language::REPORTS_MENU_FORM_MANAGE_REPORTS);
				break;
		}

		$form->addButton($text, 0, "textures/items/book_written.png");

		return $form;
	}

	/**
	 * @param array|null     $staffMembers
	 * @param MineceitPlayer $player
	 *
	 * @return CustomForm|null
	 *
	 * Gets the report staff form.
	 */
	public static function getReportStaffForm(MineceitPlayer $player, array $staffMembers = null) : ?CustomForm{

		/** @var string[] $onlineStaff */
		$onlineStaff = $staffMembers ?? MineceitCore::getPlayerHandler()->getStaffOnline(true, $player);

		$lang = $player->getLanguageInfo()->getLanguage();

		if(count($onlineStaff) <= 0){
			$message = $lang->getMessage(Language::NO_STAFF_MEMBERS_ONLINE);
			$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
			return null;
		}

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				$staffMembers = array_values($formData['staff']);

				if($data !== null){

					$lang = $event->getLanguageInfo()->getLanguage();

					$dropdownResultIndex = (int) $data[1];
					$reason = (string) $data[2];

					if($reason === ""){
						$message = $lang->getMessage(Language::REPORT_NO_REASON);
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
						return;
					}

					$resultingStaff = $staffMembers[$dropdownResultIndex];
					$server = $event->getServer();

					$name = (string) $resultingStaff;
					$report = new StaffReport($event, $name, $reason);
					$reportType = $report->getReportType();

					if(($player = $server->getPlayer($name)) !== null && $player instanceof MineceitPlayer){

						if($player->getReportsInfo()->hasReport($event, $reportType)){
							$message = $lang->getMessage(Language::REPORT_PLAYER_ALREADY);
							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
							return;
						}

						$player->getReportsInfo()->addReport($report);

						if($player->getReportsInfo()->getOnlineReportsCount($reportType) > ReportInfo::MAX_REPORT_NUM){
							// TODO SUSPEND THE PLAYER'S PERMISSIONS
						}
					}

					$reportManager = MineceitCore::getReportManager();
					$result = $reportManager->createReport($report);

					if($result){
						$message = $lang->getMessage(Language::REPORT_SUBMIT_SUCCESS);
					}else{
						$message = $lang->getMessage(Language::REPORT_SUBMIT_FAILED);
					}

					$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
				}
			}
		});
		$desc = $lang->getMessage(Language::STAFF_REPORT_FORM_DESC);

		$form->setTitle($lang->getMessage(Language::STAFF_REPORT_FORM_TITLE));
		$form->addLabel($desc);

		$form->addDropdown($lang->getMessage(Language::STAFF_REPORT_FORM_MEMBERS), $onlineStaff);

		$form->addInput($lang->getMessage(Language::FORM_LABEL_REASON_FOR_REPORTING));

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return CustomForm
	 *
	 * The bug reports form.
	 */
	public static function getReportBugForm(MineceitPlayer $player) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$lang = $event->getLanguageInfo()->getLanguage();

					$description = (string) $data[2];
					$reproduce = (string) $data[4];

					$descWordCount = MineceitUtil::getWordCount($description);

					if($descWordCount < 5){
						$message = $lang->getMessage(Language::REPORT_FIVE_WORDS);
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
						return;
					}

					$report = new BugReport($event, $description, $reproduce);
					$reportManager = MineceitCore::getReportManager();
					$result = $reportManager->createReport($report);

					if($result){
						$message = $lang->getMessage(Language::REPORT_SUBMIT_SUCCESS);
					}else{
						$message = $lang->getMessage(Language::REPORT_SUBMIT_FAILED);
					}

					$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$desc = $lang->getMessage(Language::BUG_REPORT_FORM_DESC);

		$form->setTitle($lang->getMessage(Language::BUG_REPORT_FORM_TITLE));
		$form->addLabel($desc . "\n");

		$descriptionLabel = $lang->getMessage(Language::BUG_REPORT_FORM_DESC_LABEL_HEADER);
		$form->addLabel($descriptionLabel);

		$description = $lang->getMessage(Language::BUG_REPORT_FORM_DESC_LABEL_FOOTER);
		$form->addInput($description);

		$reproduceLabel = $lang->getMessage(Language::BUG_REPORT_FORM_REPROD_LABEL_HEADER);
		$form->addLabel($reproduceLabel);

		$reproduce = $lang->getMessage(Language::BUG_REPORT_FORM_REPROD_LABEL_FOOTER);
		$form->addInput($reproduce);

		return $form;
	}

	/**
	 * @param MineceitPlayer   $player
	 * @param Language[]|array $languages
	 *
	 * @return CustomForm
	 */
	public static function getReportMisTranslationForm(MineceitPlayer $player, array $languages) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){


			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();
				$languages = (array) $formData["languages"];

				if($data !== null){

					$lang = $event->getLanguageInfo()->getLanguage();

					$index = (int) $data[1];
					$language = $languages[$index];

					$originalPhrase = (string) $data[2];
					$newPhrase = (string) $data[3];

					if($originalPhrase === ""){
						$message = $lang->getMessage(Language::REPORT_TRANSLATE_ORIG_PHRASE);
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
						return;
					}

					if($newPhrase === ""){
						$message = $lang->getMessage(Language::REPORT_TRANSLATE_NEW_PHRASE);
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
						return;
					}

					$reportManager = MineceitCore::getReportManager();
					$report = new TranslateReport($event, $language, $originalPhrase, $newPhrase);
					$result = $reportManager->createReport($report);

					if($result){
						$message = $lang->getMessage(Language::REPORT_SUBMIT_SUCCESS);
					}else{
						$message = $lang->getMessage(Language::REPORT_SUBMIT_FAILED);
					}

					$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$desc = $lang->getMessage(Language::TRANSLATION_REPORT_FORM_DESC);

		$form->setTitle($lang->getMessage(Language::TRANSLATION_REPORT_FORM_TITLE));
		$form->addLabel($desc);

		$languages = array_values($languages);

		$dropdownArray = [];

		$defaultIndex = null;

		foreach($languages as $key => $language){

			$name = $language->getNameFromLocale($lang->getLocale());

			if($language->getLocale() === $lang->getLocale()){
				$defaultIndex = $name;
			}

			$dropdownArray[] = $name;
		}

		$form->addDropdown($lang->getMessage(Language::TRANSLATION_REPORT_FORM_DROPDOWN), $dropdownArray, array_search($defaultIndex, $dropdownArray));

		$original = $lang->getMessage(Language::TRANSLATION_REPORT_FORM_LABEL_ORIGINAL);
		$form->addInput($original);

		$new = $lang->getMessage(Language::TRANSLATION_REPORT_FORM_LABEL_NEW_TOP) . "\n\n" . $lang->getMessage(Language::TRANSLATION_REPORT_FORM_LABEL_NEW_BOTTOM);
		$form->addInput($new);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param array|null     $players
	 *
	 * @return null
	 *
	 * Gets the hacker report form.
	 */
	public static function getReportHackerForm(MineceitPlayer $player, array $players = null) : ?CustomForm{

		$server = $player->getServer();

		$onlinePlayers = $server->getOnlinePlayers();

		$players = $players ?? array_diff(array_map(function(Player $player){
				return $player->getDisplayName();
			}, $onlinePlayers), [$player->getDisplayName()]);

		$lang = $player->getLanguageInfo()->getLanguage();

		if(count($players) <= 0){
			$message = $lang->getMessage(Language::NO_OTHER_PLAYERS_ONLINE);
			$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
			return null;
		}

		$form = new CustomForm(function(Player $event, $data = null){


			if($event instanceof MineceitPlayer){
				$formData = $event->removeFormData();

				if(isset($formData['players'])){
					$players = $formData['players'];
				}else{
					return;
				}

				if($data !== null){

					$lang = $event->getLanguageInfo()->getLanguage();

					$playerIndex = (int) $data[1];
					$reason = (string) $data[2];

					if($reason === ""){
						$message = $lang->getMessage(Language::REPORT_NO_REASON);
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
						return;
					}

					$resultingStaff = $players[$playerIndex];

					$name = (string) $resultingStaff;
					if(($player = MineceitUtil::getPlayerExact($name, true)) !== null && $player instanceof MineceitPlayer){
						$name = $player->getName();
					}

					$report = new HackReport($event, $name, $reason);
					$reportType = $report->getReportType();

					if($player !== null && $player instanceof MineceitPlayer){

						if($player->getReportsInfo()->hasReport($event, $reportType)){
							$message = $lang->getMessage(Language::REPORT_PLAYER_ALREADY);
							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
							return;
						}

						$player->getReportsInfo()->addReport($report);

						if($player->getReportsInfo()->getOnlineReportsCount($reportType) > ReportInfo::MAX_REPORT_NUM){
							// TODO BAN THE PLAYER FOR HACKING
							// $player->kick("Hacking...");
						}
					}

					$reportManager = MineceitCore::getReportManager();
					$result = $reportManager->createReport($report);

					if($result){
						$message = $lang->getMessage(Language::REPORT_SUBMIT_SUCCESS);
					}else{
						$message = $lang->getMessage(Language::REPORT_SUBMIT_FAILED);
					}

					$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$desc = $lang->getMessage(Language::HACK_REPORT_FORM_DESC);

		$form->setTitle($lang->getMessage(Language::HACK_REPORT_FORM_TITLE));
		$form->addLabel($desc);

		$playersDropdownTitle = $lang->getMessage(Language::PLAYERS_LABEL) . ":";

		$form->addDropdown($playersDropdownTitle, $players);

		$form->addInput($lang->getMessage(Language::FORM_LABEL_REASON_FOR_REPORTING));

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param                $reports
	 *
	 * @return SimpleForm
	 */
	public static function getListOfReports(MineceitPlayer $player, $reports = null) : SimpleForm{

		$thehistory = $player->getReportPermissions() === 0;

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();
				/** @var ReportInfo[]|array $reports */
				$reports = $formData['reports'];

				$history = (bool) $formData['history'];

				$perm = (int) $formData['perm'];

				if($data !== null){

					$index = (int) $data;

					if(count($reports) > 0){

						/** @var ReportInfo $report */
						$report = $reports[$index];

						$type = $report->getReportType();

						if($type === ReportInfo::TYPE_TRANSLATE){

							$form = self::getInfoOfReportForm($report, $event);

							$event->sendFormWindow($form, ['history' => $history, 'perm' => $perm, 'report' => $report]);
						}else{

							MineceitCore::getReportManager()->sendTranslatedReport($event, $report, $history);
						}
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$history = $reports ?? $player->getReportsInfo()->getReportHistory();

		$ipInfo = $player->getIPInfo();
		$timeZone = $ipInfo !== null ?
			$ipInfo->getTimeZone() : Timezone::get();

		$title = $thehistory ? $lang->getMessage(Language::REPORT_HISTORY_FORM_TITLE) : $lang->getMessage(Language::SEARCH_RESULTS_REPORTS_FORM_TITLE);
		$desc = $thehistory ? $lang->getMessage(Language::REPORT_HISTORY_FORM_DESC) : $lang->getMessage(Language::SEARCH_RESULTS_REPORTS_FORM_DESC);

		$form->setTitle($title);
		$form->setContent($desc);

		if(count($history) <= 0){
			$none = $lang->getMessage(Language::NONE);
			$form->addButton($none);
			return $form;
		}

		$langFormat = Language::REPORT_HISTORY_FORM_FORMAT;
		$dateFormat = "";
		if(!$thehistory){
			$langFormat = Language::SEARCH_RESULTS_REPORTS_FORM_FORMAT;
			$dateFormat = '%m%/%d%';
		}

		$author = $lang->getMessage(Language::FORM_LABEL_AUTHOR);

		foreach($history as $reportInfo){

			$time = $reportInfo->getTime($timeZone, $lang, "", false);
			$date = $reportInfo->getDate($timeZone, $lang, $dateFormat);
			$reporter = $reportInfo->getReporter();

			if(strlen($reporter) > 8){
				$reporter = substr($reporter, 0, 5) . '...';
			}

			$type = $reportInfo->getReportType(true, $lang);

			$result = $lang->getMessage($langFormat, [
				'type' => $type,
				'name' => $reporter,
				'time' => $time,
				'date' => $date,
				'author' => $author
			]);

			$form->addButton($result);
		}

		return $form;
	}

	/** ---------------------------------- PARTY FORMS ---------------------------------------- */

	/**
	 * @param ReportInfo     $info
	 * @param MineceitPlayer $player
	 * @param array          $translatedValues
	 *
	 * @return CustomForm
	 */
	public static function getInfoOfReportForm(ReportInfo $info, MineceitPlayer $player, $translatedValues = []) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				$history = (bool) $formData['history'];

				/** @var ReportInfo $report */
				$report = $formData['report'];

				$reports = $history ? array_values($event->getReportsInfo()->getReportHistory()) : null;

				if($reports !== null){

					$form = self::getListOfReports($event, $reports);

					$event->sendFormWindow($form, ['reports' => $reports, 'history' => $history, 'perm' => $event->getReportPermissions()]);
				}else{

					$saved = $event->getReportsInfo()->getLastSearchReportHistory();
					MineceitCore::getReportManager()->searchReports($event, $saved['searched'], $saved['timespan'], $saved['report-data'], $saved['resolved']);
				}

				if($data !== null){

					$resolved = $data[0];

					if($resolved !== null){
						$report->setResolved(boolval($resolved));
					}
				}
			}
		});

		$perm = $player->getReportPermissions();

		$lang = $player->getLanguageInfo()->getLanguage();

		$title = $info->getReportType(true, $lang);

		$form->setTitle($title);
		$ipInfo = $player->getIPInfo();
		$timeZone = $ipInfo !== null ? $ipInfo->getTimeZone()
			: Timezone::get();
		$infoResolved = $info->isResolved();

		$r = $lang->getMessage(Language::FORM_LABEL_RESOLVED);
		$ur = $lang->getMessage(Language::FORM_LABEL_UNRESOLVED);

		$resolved = $infoResolved ? $r : $ur;

		$information = [
			$lang->getMessage(Language::FORM_LABEL_STATUS) => $resolved,
			"",
			$lang->getMessage(Language::FORM_LABEL_AUTHOR) => $info->getReporter(),
			"",
			$lang->getMessage(Language::FORM_LABEL_DATE) => $info->getDate($timeZone, $lang),
			$lang->getMessage(Language::FORM_LABEL_TIME) => $info->getTime($timeZone, $lang),
			""
		];

		if($perm === ReportInfo::PERMISSION_MANAGE_REPORTS){

			$information = [
				$lang->getMessage(Language::FORM_LABEL_AUTHOR) => $info->getReporter(),
				"",
				$lang->getMessage(Language::FORM_LABEL_DATE) => $info->getDate($timeZone, $lang),
				$lang->getMessage(Language::FORM_LABEL_TIME) => $info->getTime($timeZone, $lang),
				""
			];

			// Change the report's status:
			$form->addDropdown($lang->getMessage(Language::REPORT_INFO_FORM_CHANGE_STATUS), [$ur, $r], intval($infoResolved));
		}

		if($info instanceof BugReport){

			$description = $info->getDescription();

			if(isset($translatedValues['desc'])){
				$description = (string) $translatedValues['desc'];
			}

			$reproduce = $info->getReproduceInfo();
			if(isset($translatedValues['reproduce'])){
				$reproduce = (string) $translatedValues['reproduce'];
			}

			$information = array_merge($information, [
				$lang->getMessage(Language::FORM_LABEL_BUG) => $description,
				"",
				$lang->getMessage(Language::FORM_LABEL_REPRODUCED) => $reproduce,
				""
			]);
		}elseif($info instanceof HackReport){

			$reason = $info->getReason();
			if(isset($translatedValues['reason'])){
				$reason = (string) $translatedValues['reason'];
			}

			$information = array_merge($information, [
				$lang->getMessage(Language::FORM_LABEL_PLAYER) => $info->getReported(),
				"",
				$lang->getMessage(Language::FORM_LABEL_REASON) => $reason,
				""
			]);
		}elseif($info instanceof StaffReport){

			$reason = $info->getReason();
			if(isset($translatedValues['reason'])){
				$reason = (string) $translatedValues['reason'];
			}

			$information = array_merge($information, [

				$lang->getMessage(Language::FORM_LABEL_STAFF_MEMBER) => $info->getReported(),
				"",
				$lang->getMessage(Language::FORM_LABEL_REASON) => $reason,
				""
			]);
		}elseif($info instanceof TranslateReport){
			$information = array_merge($information, [
				$lang->getMessage(Language::FORM_LABEL_LANGUAGE) => $info->getLang(true, $lang),
				"",
				$lang->getMessage(Language::FORM_LABEL_ORIGINAL_MESSAGE) => "",
				$info->getOriginalMessage(),
				"",
				$lang->getMessage(Language::FORM_LABEL_NEW_MESSAGE) => "",
				$info->getNewMessage(),
				""
			]);
		}

		$array = [];

		foreach($information as $key => $value){

			$resultString = strval($key) . ': ' . strval($value);

			if(is_numeric($key)){
				$resultString = strval($value);
			}

			$array[] = TextFormat::RESET . TextFormat::WHITE . $resultString;
		}

		$result = implode("\n", $array);

		$form->addLabel($result);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return CustomForm
	 *
	 * The form that allows the players to toggle the reports.
	 */
	public static function getViewReportsSearchForm(MineceitPlayer $player) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$reportManager = MineceitCore::getReportManager();

					$nameToSearch = $data[1];

					$reportTypes = ReportInfo::REPORT_TYPES;

					$reportData = [];

					foreach($reportTypes as $typeInt){
						$outputType = $typeInt + 3;
						$reportData[$typeInt] = $data[$outputType];
					}

					$length = count($reportTypes) + 3;

					$timeSpanOutput = $data[$length];

					$resolvedOutput = $data[$length + 1];

					$saved = [
						"searched" => (string) $nameToSearch,
						"timespan" => (int) $timeSpanOutput,
						"report-data" => (array) $reportData,
						'resolved' => (int) $resolvedOutput
					];

					$event->getReportsInfo()->setReportSearchHistory($saved);

					// Searches the reports.
					$reportManager->searchReports($event, $saved['searched'], $saved['timespan'], $saved['report-data'], $saved['resolved']);
				}
			}
		});

		$reportHistory = $player->getReportsInfo()->getLastSearchReportHistory()
			?? ['searched' => "", "timespan" => 0, 'report-data' => [true, true, true, true], 'resolved' => 0];

		$lang = $player->getLanguageInfo()->getLanguage();

		$desc = $lang->getMessage(Language::SEARCH_REPORTS_FORM_DESC);

		$form->setTitle($lang->getMessage(Language::FORM_LABEL_REPORTS));

		$form->addLabel($desc);

		$searchByName = $lang->getMessage(Language::FORM_LABEL_SEARCH) . ":";

		$searchedDefault = (string) $reportHistory['searched'];
		$form->addInput($searchByName, $searchedDefault);

		$reportType = $lang->getMessage(Language::FORM_LABEL_REPORT_TYPES);
		$searchReportsByType = "{$reportType}:";

		$reportTypes = ReportInfo::REPORT_TYPES;

		$form->addLabel($searchReportsByType);

		$defaultType = $reportHistory['report-data'];
		foreach($reportTypes as $typeInt){
			$type = ReportInfo::getReportsType($typeInt, $lang);
			$form->addToggle(TextFormat::LIGHT_PURPLE . $type, $defaultType[$typeInt]);
		}

		$timespan = $lang->getMessage(Language::FORM_LABEL_TIMESPAN);
		$searchReportsTime = "{$timespan}:";

		$options = [
			$lang->getMessage(Language::NONE),
			$lang->getMessage(Language::FORM_LABEL_LAST_HOUR),
			$lang->getMessage(Language::FORM_LABEL_LAST_12_HOURS),
			$lang->getMessage(Language::FORM_LABEL_LAST_24_HOURS),
			$lang->getMessage(Language::FORM_LABEL_LAST_WEEK),
			$lang->getMessage(Language::FORM_LABEL_LAST_MONTH)
		];

		$timeSpanDefault = (int) $reportHistory['timespan'];
		$form->addDropdown($searchReportsTime, $options, $timeSpanDefault);

		$status = $lang->getMessage(Language::FORM_LABEL_STATUS);
		$statusReports = "{$status}:";

		$options = [$lang->getMessage(Language::NONE), $lang->getMessage(Language::FORM_LABEL_UNRESOLVED), $lang->getMessage(Language::FORM_LABEL_RESOLVED)];

		$statusDefault = (int) $reportHistory['resolved'];
		$form->addDropdown($statusReports, $options, $statusDefault);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param array|null     $builderLevels
	 *
	 * @return CustomForm
	 */
	public static function getBuilderModeForm(MineceitPlayer $player, array $builderLevels = null) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){
			if($event instanceof MineceitPlayer){
				$formData = $event->removeFormData();
				if($data !== null){
					if(!$event->hasBuilderPermissions()){
						return;
					}
					$event->getSettingsInfo()
						->getBuilderModeInfo()->setEnabled((bool) $data[1]);
					$builderLevels = $formData['builder-levels'];
					if(($size = count($data)) > 2){
						$start = 3;
						while($start < $size){
							$level = $builderLevels[$start - 3];
							$event->getSettingsInfo()->getBuilderModeInfo()
								->setBuildEnabledInLevel(strval($level), (bool) $data[$start]);
							$start++;
						}
					}
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();
		$levels = $builderLevels ?? $player->getSettingsInfo()
				->getBuilderModeInfo()->getBuilderLevels();

		$form->setTitle($language->getMessage(Language::BUILDER_MODE_FORM_TITLE));
		$form->addLabel($language->getMessage(Language::BUILDER_MODE_FORM_DESC));

		$enabled = $player->getSettingsInfo()
			->getBuilderModeInfo()->isEnabled();
		$enableNDisableToggle = $enabled ? Language::BUILDER_MODE_FORM_DISABLE : Language::BUILDER_MODE_FORM_ENABLE;
		$enableNDisableToggle = $language->getMessage($enableNDisableToggle);

		$form->addToggle(TextFormat::LIGHT_PURPLE . $enableNDisableToggle, $enabled);
		if(count($levels) <= 0){
			$form->addLabel($language->formWindow(Language::BUILDER_MODE_FORM_LEVEL_NONE));
			return $form;
		}

		$form->addLabel($language->formWindow(Language::BUILDER_MODE_FORM_LEVEL));
		foreach($levels as $levelName => $value){
			$form->addToggle(TextFormat::LIGHT_PURPLE . strval($levelName), $value);
		}
		return $form;
	}

	/**
	 * @param MineceitPlayer   $player
	 * @param DuelArena|string $arena
	 *
	 * @return CustomForm
	 */
	public static function getEditDuelArenaForm(MineceitPlayer $player, $arena) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){


			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					$arenaHandler = MineceitCore::getArenas();
					$arena = $formData['arena'];

					$key = 0;
					$duelkits = [];
					$kits = MineceitCore::getKits()->getKits();
					foreach($kits as $kit){
						if($kit->getMiscKitInfo()->isDuelsEnabled()){
							if((bool) $data[$key]){
								$duelkits[] = $kit->getName();
							}
							$key++;
						}
					}

					if($arena instanceof DuelArena){

						$arena->setKit($duelkits);

						$arenaHandler->editArena($arena);

						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . TextFormat::GREEN . 'Successfully edited the arena!');
					}else{
						if($arenaHandler->getArena($arena) === null){

							$server = $event->getServer();
							$level = $event->getLevel();
							$lname = $level->getName();

							if($level !== $server->getDefaultLevel() && $arenaHandler->getArena($lname) === null){
								$event->reset(true, true);
								MineceitCore::getItemHandler()->spawnHubItems($event);
								$event->getScoreboardInfo()->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);

								$server->unloadLevel($level);

								$path = $server->getDataPath() . "worlds/$lname/";

								$zip = new \ZipArchive;
								if($zip->open(MineceitCore::getResourcesFolder() . 'worlds/' . $arena . '.zip', $zip::CREATE) === true){
									$nbt = new BigEndianNBTStream();
									$leveldat = zlib_decode(file_get_contents($path . 'level.dat'));
									$levelData = $nbt->read($leveldat);
									$levelData["Data"]->setTag(new StringTag("LevelName", "$arena"));

									$buffer = $nbt->writeCompressed($levelData);
									file_put_contents($path . 'level.dat', $buffer);

									$rootPath = realpath($path);
									$files = new \RecursiveIteratorIterator(
										new \RecursiveDirectoryIterator($rootPath),
										\RecursiveIteratorIterator::LEAVES_ONLY
									);

									foreach($files as $name => $file){
										if(!$file->isDir()){
											$filePath = $file->getRealPath();
											$relativePath = substr($filePath, strlen($rootPath) + 1);

											$zip->addFile($filePath, $relativePath);
										}
									}
									$zip->close();
									unset($zip);

									$levelData["Data"]->setTag(new StringTag("LevelName", "$lname"));

									$buffer = $nbt->writeCompressed($levelData);
									file_put_contents($path . 'level.dat', $buffer);

									$arenaHandler->createArena($arena, $duelkits, $event, 'Duel');
								}

								$server->loadLevel($lname);
							}else{
								//Message cant create world as duel arena
							}
						}else{
							//Message arena already exist
						}
					}
				}
			}
		});

		if($arena instanceof DuelArena){
			$form->setTitle('Edit Kits');
		}else{
			$form->setTitle('Create Duel Arena');
		}

		$kits = MineceitCore::getKits()->getKits();
		foreach($kits as $kit){
			if($kit->getMiscKitInfo()->isDuelsEnabled())
				$form->addToggle($kit->getName(), false);
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getDuelsForm(MineceitPlayer $player) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){
			if($event instanceof MineceitPlayer){
				$event->removeFormData();
				if($data !== null){
					$language = $event->getLanguageInfo()->getLanguage();
					switch($data){
						case 0:
							$form = FormUtil::getDuelForm($event, $language->formWindow(Language::DUELS_RANKED_FORM_TITLE), true);
							$event->sendFormWindow($form, ['ranked' => true]);
							break;
						case 1:
							$form = FormUtil::getDuelForm($event, $language->formWindow(Language::DUELS_UNRANKED_FORM_TITLE), false);
							$event->sendFormWindow($form, ['ranked' => false]);
							break;
						case 2:
							$form = FormUtil::getRequestForm($event);
							$event->sendFormWindow($form);
							break;
						case 3:
							$requestHandler = MineceitCore::getDuelHandler()->getRequestHandler();
							$requests = $requestHandler->getRequestsOf($event);
							$form = FormUtil::getDuelInbox($event, $requests);
							$event->sendFormWindow($form, ['requests' => $requests]);
							break;
						case 4:
							$duels = MineceitCore::getDuelHandler()->getDuels();
							$duels = array_values($duels);
							$form = FormUtil::getSpectateForm($event, $duels);
							$event->sendFormWindow($form, ['duels' => $duels]);
							break;
						case 5:
							$form = FormUtil::getDuelHistoryForm($event);
							$event->sendFormWindow($form);
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();
		$form->setTitle($lang->formWindow(Language::HUB_PLAY_FORM_DUELS));
		$form->setContent($lang->formWindow(Language::HUB_PLAY_DUEL_FORM_DESC));
		$form->addButton(
			$lang->formWindow(Language::HUB_PLAY_FORM_RANKED_DUELS),
			0,
			"textures/ui/strength_effect.png"
		);

		$form->addButton(
			$lang->formWindow(Language::HUB_PLAY_FORM_UNRANKED_DUELS),
			0,
			"textures/ui/weakness_effect.png"
		);

		$form->addButton(
			$lang->formWindow(Language::HUB_DUELS_REQUEST),
			0,
			"textures/items/paper.png"
		);

		$form->addButton(
			$lang->formWindow(Language::HUB_DUELS_ACCEPT),
			0,
			"textures/blocks/trapped_chest_front.png"
		);

		$form->addButton(
			$lang->formWindow(Language::HUB_DUELS_SPEC),
			0,
			"textures/ui/invisibility_effect.png"
		);

		$form->addButton(
			$lang->formWindow(Language::HUB_DUELS_HISTORY),
			0,
			"textures/items/book_written.png"
		);
		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param string         $title
	 * @param bool           $ranked
	 *
	 * @return SimpleForm
	 */
	public static function getDuelForm(MineceitPlayer $player, string $title, bool $ranked) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null && $formData !== null){

					if(isset($formData[$data]['text'])){
						$queue = TextFormat::clean(explode("\n", $formData[$data]['text'])[0]);
					}else{
						return;
					}


					if($event->isInHub()){
						if(isset($formData['ranked'])){
							MineceitCore::getDuelHandler()->placeInQueue($event, $queue, (bool) $formData['ranked']);
						}else{
							return;
						}
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($title);
		$form->setContent($lang->formWindow(Language::DUELS_FORM_DESC));

		$list = MineceitCore::getKits()->getKits();

		$format = TextFormat::WHITE . '%iq% ' . TextFormat::GRAY . $lang->formWindow(Language::DUELS_FORM_INQUEUES);

		foreach($list as $kit){
			if($kit->getMiscKitInfo()->isDuelsEnabled()){

				$name = $kit->getName();
				$numInQueue = MineceitCore::getDuelHandler()->getPlayersInQueue($ranked, $name);

				$form->addButton(
					TextFormat::LIGHT_PURPLE . $name . "\n" . str_replace('%iq%', $numInQueue, $format),
					0,
					$kit->getMiscKitInfo()->getTexture()
				);
			}
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return CustomForm
	 */
	public static function getRequestForm(MineceitPlayer $player) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){


			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					if(!isset($formData[0])) return;

					$firstIndex = $formData[0];

					if(!isset($firstIndex['type'])) return;

					if($firstIndex['type'] !== 'label'){

						$senderLang = $event->getLanguageInfo()->getLanguage();

						$playerName = $firstIndex['options'][(int) $data[0]];

						$queue = $formData[1]['options'][(int) $data[1]];

						$ranked = (int) $data[2] !== 0;

						$requestHandler = MineceitCore::getDuelHandler()->getRequestHandler();

						if(($to = MineceitUtil::getPlayerExact($playerName, true)) !== null && $to instanceof MineceitPlayer){

							$requestHandler->sendRequest($event, $to, $queue, $ranked);
						}else{
							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $senderLang->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $playerName]));
						}
					}
				}
			}
		});

		$onlinePlayers = $player->getServer()->getOnlinePlayers();

		$language = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($language->formWindow(Language::REQUEST_FORM_TITLE));

		$ranked = $language->getRankedStr(true);
		$unranked = $language->getRankedStr(false);

		$dropdownArr = [];

		$name = $player->getDisplayName();

		$size = count($onlinePlayers);

		foreach($onlinePlayers as $p){
			$pName = $p->getDisplayName();
			if($pName !== $name)
				$dropdownArr[] = $pName;
		}

		$sendRequest = $language->formWindow(Language::REQUEST_FORM_SEND_TO);
		$selectQueue = $language->formWindow(Language::REQUEST_FORM_SELECT_QUEUE);
		$setDuelRankedOrUnranked = $language->formWindow(Language::REQUEST_FORM_RANKED_OR_UNRANKED);

		if(($size - 1) > 0){
			$duelkits = [];
			$kits = MineceitCore::getKits()->getKits();
			foreach($kits as $kit){
				if($kit->getMiscKitInfo()->isDuelsEnabled())
					$duelkits[] = $kit->getName();
			}
			$form->addDropdown($sendRequest, $dropdownArr);
			$form->addDropdown($selectQueue, $duelkits);
			$form->addDropdown($setDuelRankedOrUnranked, [$unranked, $ranked]);
		}else
			$form->addLabel($language->formWindow(Language::REQUEST_FORM_NOBODY_ONLINE));

		return $form;
	}

	/**
	 * @param MineceitPlayer      $player
	 * @param DuelRequest[]|array $requestInbox
	 *
	 * @return SimpleForm
	 */
	public static function getDuelInbox(MineceitPlayer $player, $requestInbox = []) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				$language = $event->getLanguageInfo()->getLanguage();

				$duelHandler = MineceitCore::getDuelHandler();

				if($data !== null){

					$index = (int) $data;
					if($index !== $language->generalMessage(Language::NONE)){

						if(isset($formData['requests'])){
							$requests = $formData['requests'];
							$keys = array_keys($requests);
							if(!isset($keys[$index])) return;
							$name = $keys[$index];
							$request = $requests[$name];

							if($request instanceof DuelRequest){

								$pName = $event->getName();

								$opponentName = ($pName === $request->getToName()) ? $request->getFromName() : $request->getToName();

								if(($opponent = MineceitUtil::getPlayerExact($opponentName)) instanceof MineceitPlayer && $opponent->isOnline()
									&& ($opponent->isInHub() || $opponent->isADuelSpec()) && !$opponent->isInParty() && !$opponent->isWatchingReplay() && !$opponent->isInEvent()
								){

									if($event->isInHub()){

										if($opponent->isADuelSpec()){
											$duel = $duelHandler->getDuelFromSpec($opponent);
											$duel->removeSpectator($opponent, false, false);
										}elseif($opponent->isWatchingReplay()){
											$replay = MineceitCore::getReplayManager()->getReplayFrom($opponent);
											$replay->endReplay(false);
										}

										$duelHandler->getRequestHandler()->acceptRequest($request);
										$duelHandler->placeInDuel($event, $opponent, $request->getQueue(), $request->isRanked(), false);
									}else $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::ACCEPT_FAIL_NOT_IN_LOBBY));
								}else{

									$message = null;
									if($opponent === null || !$opponent->isOnline())
										$message = $language->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $opponentName]);
									elseif($opponent->isInDuel())
										$message = $language->generalMessage(Language::ACCEPT_FAIL_PLAYER_IN_DUEL, ["name" => $opponentName]);
									elseif($opponent->isInBot())
										$message = $language->generalMessage(Language::ACCEPT_FAIL_PLAYER_IN_DUEL, ["name" => $opponentName]);
									elseif($opponent->isInArena())
										$message = $language->generalMessage(Language::ACCEPT_FAIL_PLAYER_IN_ARENA, ["name" => $opponentName]);
									elseif($opponent->isInParty())
										$message = $language->generalMessage(Language::ACCEPT_FAIL_PLAYER_IN_PARTY, ["name" => $opponentName]);
									elseif($opponent->isWatchingReplay())
										$message = $language->generalMessage(Language::ACCEPT_FAIL_PLAYER_WATCH_REPLAY, ["name" => $opponentName]);
									elseif($opponent->isInEvent())
										$message = $language->generalMessage(Language::ACCEPT_FAIL_PLAYER_IN_EVENT, ["name" => $opponentName]);

									if($message !== null) $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
								}
							}
						}
					}
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();

		$count = count($requestInbox);

		$name = $language->formWindow(Language::FORM_TITLE_REQUEST_ACCEPT);

		$desc = $language->formWindow(Language::FORM_CLICK_REQUEST_ACCEPT);

		// $len = strlen($clean);
		// if ($len > 30 && $language->shortenString()) {
		//     $clean = substr($clean, 0, 25) . "...";
		// }

		$form->setTitle($name);
		$form->setContent($desc);

		if($count <= 0){
			$form->addButton($language->generalMessage(Language::NONE));
			return $form;
		}

		$keys = array_keys($requestInbox);

		$queueStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_QUEUE);

		foreach($keys as $name){

			$name = (string) $name;

			$request = $requestInbox[$name];

			$ranked = $language->getRankedStr($request->isRanked());
			$queue = $ranked . ' ' . $request->getQueue();

			$theQueueStr = TextFormat::LIGHT_PURPLE . $queueStr . TextFormat::WHITE . ': ' . TextFormat::LIGHT_PURPLE . $queue;

			if(($player = MineceitUtil::getPlayerExact($name)) instanceof Player){
				$name = $player->getDisplayName();
			}

			$sentBy = $language->formWindow(Language::FORM_SENT_BY, [
				"name" => $name
			]);

			$text = $sentBy . "\n" . $theQueueStr;

			$form->addButton($text, 0, $request->getTexture());
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer     $player
	 * @param MineceitDuel|array $duels
	 *
	 * @return SimpleForm
	 */
	public static function getSpectateForm(MineceitPlayer $player, $duels = []) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				$lang = $event->getLanguageInfo()->getLanguage();

				if($data !== null){

					$index = (int) $data;

					if(isset($formData[$index]['text'])){
						$firstText = $formData[$index]['text'];
					}else{
						return;
					}

					if($lang->generalMessage(Language::NONE) !== $firstText){

						if(isset($formData['duels'][$index])){
							$duel = $formData['duels'][$index];
						}else{
							return;
						}

						if($duel instanceof MineceitDuel){

							$duelHandler = MineceitCore::getDuelHandler();

							$worldId = $duel->getWorldId();

							$currentDuels = $duelHandler->getDuels();

							if(isset($currentDuels[$worldId]) && $currentDuels[$worldId]->equals($duel)){

								$duel = $currentDuels[$worldId];
								$duelHandler->addSpectatorTo($event, $duel);
							}else{

								$msg = $lang->generalMessage(Language::DUEL_ALREADY_ENDED);
								$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
							}
						}
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::SPECTATE_FORM_TITLE));

		$form->setContent(TextFormat::WHITE . $lang->formWindow(Language::SPECTATE_FORM_DESC));

		$size = count($duels);

		if($size <= 0){
			$form->addButton($lang->generalMessage(Language::NONE));
			return $form;
		}

		foreach($duels as $duel){

			$texture = $duel->getTexture();

			$name = TextFormat::GRAY . $duel->getP1DisplayName() . TextFormat::LIGHT_PURPLE . ' vs ' . TextFormat::GRAY . $duel->getP2DisplayName();

			$form->addButton($name, 0, $texture);
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getDuelHistoryForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$length = count($event->getDuelHistory()) - 1;

					$index = $length - (int) $data;

					$info = $event->getDuelInfo($index);

					if($info !== null){

						$form = self::getDuelInfoForm($event, $info);
						$event->sendFormWindow($form, [
							'info-index' => $index,
							'size' => isset($info['replay']) ? 4 : 3
						]);
					}
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($language->formWindow(Language::DUEL_HISTORY_FORM_TITLE));
		$form->setContent($language->formWindow(Language::DUEL_HISTORY_FORM_DESC));

		$duelHistory = $player->getDuelHistory();

		$size = count($duelHistory);

		if($size <= 0){
			$form->addButton($language->generalMessage(Language::NONE));
			return $form;
		}

		$end = $size - 20 >= 0 ? $size - 20 : 0;

		for($index = $size - 1; $index >= $end; $index--){

			$resultingDuel = $duelHistory[$index];
			$winnerInfo = $resultingDuel['winner'];
			$loserInfo = $resultingDuel['loser'];
			$drawBool = (bool) $resultingDuel['draw'];

			if($winnerInfo instanceof DuelInfo && $loserInfo instanceof DuelInfo){

				$winnerDisplayName = $winnerInfo->getDisplayName();
				$loserDisplayName = $loserInfo->getDisplayName();

				$playerVsString = TextFormat::GRAY . '%player%' . TextFormat::DARK_GRAY . ' vs ' . TextFormat::GRAY . '%opponent%';

				$queue = $language->getRankedStr($winnerInfo->isRanked()) . ' ' . $winnerInfo->getQueue();

				$resultStr = TextFormat::DARK_GRAY . 'D';

				if($drawBool){
					$playerVsString = str_replace('%player%', $winnerDisplayName, str_replace('%opponent%', $loserDisplayName, $playerVsString));
				}else{

					if($winnerInfo->getPlayerName() === $player->getName()){
						$playerVsString = str_replace('%player%', $winnerDisplayName, str_replace('%opponent%', $loserDisplayName, $playerVsString));
						$resultStr = TextFormat::GREEN . 'W';
					}elseif($loserInfo->getPlayerName() === $player->getName()){
						$playerVsString = str_replace('%player%', $loserDisplayName, str_replace('%opponent%', $winnerDisplayName, $playerVsString));
						$resultStr = TextFormat::RED . 'L';
					}
				}

				$queueStr = $language->scoreboard(Language::SPAWN_SCOREBOARD_QUEUE);

				$theQueueStr = TextFormat::LIGHT_PURPLE . $queueStr . TextFormat::WHITE . ': ' . TextFormat::LIGHT_PURPLE . $queue;

				$form->addButton(
					$playerVsString . "\n" . $resultStr . TextFormat::WHITE . ' | ' . $theQueueStr,
					0,
					$winnerInfo->getTexture()
				);
			}
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param array          $info
	 *
	 * @return SimpleForm
	 */
	public static function getDuelInfoForm(MineceitPlayer $player, $info = []) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					$index = (int) $data;

					if(!isset($formData['size'])) return;

					$size = (int) $formData['size'];

					if(!isset($formData['info-index'])) return;

					$inventoryInfoIndex = (int) $formData['info-index'];

					$info = $event->getDuelInfo($inventoryInfoIndex);

					$goBackIndex = $size - 1;

					if($info !== null && $index !== $goBackIndex){

						$language = $event->getLanguageInfo()->getLanguage();

						$i = $index === 0 ? 'winner' : 'loser';

						if($size === 4){

							if($index != 2){

								$inventory = new PostMatchInv($info[$i], $language);

								$inventory->sendTo($event);
							}else{

								/** @var DuelReplayInfo $duelReplayInfo */
								$duelReplayInfo = $info['replay'];

								$replayManager = MineceitCore::getReplayManager();

								$replayManager->startReplay($event, $duelReplayInfo);
							}
						}else{

							$inventory = new PostMatchInv($info[$i], $language);

							$inventory->sendTo($event);
						}
					}else{

						if($index === $goBackIndex){
							$form = self::getDuelHistoryForm($event);
							$event->sendFormWindow($form);
						}
					}
				}
			}
		});

		/* @var DuelInfo $winnerInfo */
		$winnerInfo = $info['winner'];
		/* @var DuelInfo $loserInfo */
		$loserInfo = $info['loser'];

		$draw = (bool) $info['draw'];

		$language = $player->getLanguageInfo()->getLanguage();

		$winnerDisplayName = $winnerInfo->getDisplayName();
		$loserDisplayName = $loserInfo->getDisplayName();

		$playerVsString = TextFormat::LIGHT_PURPLE . '%player%' . TextFormat::GRAY . ' vs ' . TextFormat::LIGHT_PURPLE . '%opponent%';

		$isWinner = !$draw && $winnerInfo->getPlayerName() === $player->getName();

		if($isWinner || $draw){
			$playerVsString = str_replace('%player%', $winnerDisplayName, str_replace('%opponent%', $loserDisplayName, $playerVsString));
		}elseif($loserInfo->getPlayerName() === $player->getName()){
			$playerVsString = str_replace('%player%', $loserDisplayName, str_replace('%opponent%', $winnerDisplayName, $playerVsString));
		}

		$form->setTitle(TextFormat::GRAY . '» ' . TextFormat::LIGHT_PURPLE . $playerVsString . TextFormat::GRAY . ' «');

		$winnerColor = ($draw) ? TextFormat::LIGHT_PURPLE : TextFormat::GREEN;
		$loserColor = ($draw) ? TextFormat::LIGHT_PURPLE : TextFormat::RED;

		$winnerDesc = $language->formWindow(
			Language::DUEL_INVENTORY_FORM_VIEW,
			[
				"name" => $winnerColor . $winnerDisplayName . TextFormat::GRAY
			]
		);

		$loserDesc = $language->formWindow(Language::DUEL_INVENTORY_FORM_VIEW, [
			"name" => $loserColor . $loserDisplayName . TextFormat::GRAY
		]);

		$form->addButton(
			$winnerDesc,
			0,
			'textures/blocks/trapped_chest_front.png'
		);

		$form->addButton(
			$loserDesc,
			0,
			'textures/blocks/trapped_chest_front.png'
		);

		if(isset($info['replay'])){
			$form->addButton($language->formWindow(Language::FORM_VIEW_REPLAY), 0, 'textures/ui/timer.png');
		}

		$form->addButton($language->formWindow(Language::GO_BACK), 0, 'textures/gui/newgui/XPress.png');

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param MineceitReplay $replay
	 *
	 * @return CustomForm
	 */
	public static function getReplaySettingsForm(MineceitPlayer $player, MineceitReplay $replay) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					$replay = $formData['replay'];

					$replay->setReplaySecs((int) $data[0]);
					$replay->setTimeScale(((int) $data[1]) * 0.1);
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::REPLAY_FORM_TITLE));
		$form->addSlider($lang->formWindow(Language::REPLAY_SKIP_SECONDS), 1, 10, 1, (int) $replay->getReplaySecs());
		$form->addSlider($lang->formWindow(Language::REPLAY_TIME_SCALE), 1, 20, 1, (int) ($replay->getTimeScale() * 10));
		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param array|string[] $onlinePlayers
	 *
	 * @return CustomForm
	 */
	public static function getGamemodeForm(MineceitPlayer $player, $onlinePlayers = []) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					$senderLang = $event->getLanguageInfo()->getLanguage();

					$playerNameIndex = (int) $data[0];
					$gamemodeIndex = (int) $data[1];

					$name = $formData['online-players'][$playerNameIndex];

					if(($player = MineceitUtil::getPlayerExact($name, true)) !== null && $player instanceof MineceitPlayer){

						$lang = $player->getLanguageInfo()->getLanguage();

						$gamemodes = [
							0 => $lang->getMessage(Language::GAMEMODE_SURVIVAL) ?? "Survival",
							1 => $lang->getMessage(Language::GAMEMODE_CREATIVE) ?? "Creative",
							2 => $lang->getMessage(Language::GAMEMODE_ADVENTURE) ?? "Adventure",
							3 => $lang->getMessage(Language::GAMEMODE_SPECTATOR) ?? "Spectator"
						];

						$msg = $lang->generalMessage(Language::GAMEMODE_CHANGE, [
							"gamemode" => $gamemodes[$gamemodeIndex]
						]);

						if($gamemodeIndex === 1 && (!$player->hasAdminPermissions() && !$player->hasBuilderPermissions())){
							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $senderLang->getPermissionMessage());
							return;
						}

						if($player->getGamemode() !== $gamemodeIndex){
							$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
						}

						$player->setGamemode($gamemodeIndex);
					}else{
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $senderLang->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $name]));
					}
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();

		$form->setTitle(TextFormat::GRAY . '» ' . TextFormat::LIGHT_PURPLE . 'GamemodeUI' . TextFormat::GRAY . ' «');

		$playerLabel = TextFormat::LIGHT_PURPLE . $language->formWindow(Language::PLAYERS_LABEL) . TextFormat::WHITE . ':';

		$form->addDropdown($playerLabel, $onlinePlayers, array_search($player->getDisplayName(), $onlinePlayers));

		$menuLabel = TextFormat::LIGHT_PURPLE . $language->formWindow(Language::GAMEMODE_FORM_MENU_LABEL) . TextFormat::WHITE . ':';

		$gray = TextFormat::DARK_GRAY;

		$survival = $language->getMessage(Language::GAMEMODE_SURVIVAL) ?? "Survival";
		$creative = $language->getMessage(Language::GAMEMODE_CREATIVE) ?? "Creative";
		$adventure = $language->getMessage(Language::GAMEMODE_ADVENTURE) ?? "Adventure";
		$spectator = $language->getMessage(Language::GAMEMODE_SPECTATOR) ?? "Spectator";

		$form->addDropdown($menuLabel, [$gray . "{$survival} [Gm = 0]", $gray . "{$creative} [Gm = 1]", $gray . "{$adventure} [Gm = 2]", $gray . "{$spectator} [Gm = 3]"], $player->getGamemode());

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param array|string[] $onlinePlayers
	 *
	 * @return CustomForm
	 */
	public static function getFreezeForm(MineceitPlayer $player, $onlinePlayers = []) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					$frozenIndex = (int) $data[1];

					$name = $formData['online-players'][(int) $data[0]];

					if(($player = MineceitUtil::getPlayerExact($name, true)) !== null && $player instanceof MineceitPlayer){

						$frozenVal = $frozenIndex === 0;

						if($frozenVal !== $player->isFrozen()){

							$language = $player->getLanguageInfo()->getLanguage();

							switch($frozenIndex){

								case 0:
									$title = $language->generalMessage(Language::FREEZE_TITLE);
									$subtitle = $language->generalMessage(Language::FREEZE_SUBTITLE);
									$player->setFrozenNameTag();
									$player->addTitle(TextFormat::LIGHT_PURPLE . $title, TextFormat::WHITE . $subtitle, 5, 20, 5);
									break;
								case 1:
									$title = $language->generalMessage(Language::UNFREEZE_TITLE);
									$subtitle = $language->generalMessage(Language::UNFREEZE_SUBTITLE);
									$player->setNormalNameTag();
									$player->addTitle(TextFormat::LIGHT_PURPLE . $title, TextFormat::WHITE . $subtitle, 5, 20, 5);
									break;
							}
						}

						$player->setFrozen($frozenVal);

						$title = DiscordUtil::boldText($frozenVal ? "Frozen" : "Unfrozen");
						$name = $event->getDisplayName();
						$name = $event->getDisguiseInfo()->isDisguised() ? "$name ({$event->getName()})" : $name;

						$description = DiscordUtil::boldText("User:") . " " . $name . "\n" . DiscordUtil::boldText($frozenVal ? "Frozen:" : "Unfrozen:") . " " . $player->getName();

						DiscordUtil::sendBan($title, $description, DiscordUtil::BLUE);
					}else{
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $event->getLanguageInfo()->getLanguage()->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $name]));
					}
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();

		$form->setTitle(TextFormat::GRAY . '» ' . TextFormat::LIGHT_PURPLE . 'FreezeUI' . TextFormat::GRAY . ' «');

		$playerLabel = TextFormat::LIGHT_PURPLE . $language->formWindow(Language::PLAYERS_LABEL) . TextFormat::WHITE . ':';

		if(count($onlinePlayers) === 0){

			$form->addLabel($language->formWindow(Language::REQUEST_FORM_NOBODY_ONLINE));
			return $form;
		}

		$form->addDropdown($playerLabel, $onlinePlayers);

		$menuLabel = TextFormat::LIGHT_PURPLE . $language->formWindow(Language::GAMEMODE_FORM_MENU_LABEL) . TextFormat::WHITE . ':';

		$form->addDropdown($menuLabel, [
			TextFormat::DARK_GRAY . $language->formWindow(Language::FREEZE_FORM_ENABLE),
			TextFormat::DARK_GRAY . $language->formWindow(Language::FREEZE_FORM_DISABLE)
		]);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param array|string[] $onlinePlayers
	 *
	 * @return CustomForm
	 */
	public static function getFollowForm(MineceitPlayer $player, $onlinePlayers = []) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					$name = $formData['online-players'][(int) $data[0]];

					if(($player = MineceitUtil::getPlayerExact($name, true)) !== null && $player instanceof MineceitPlayer){

						$player->setFollower($event->getName());
						$event->setFollowing($player->getName());
						$event->teleport($player->getLocation());
					}else{
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $event->getLanguageInfo()->getLanguage()->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $name]));
					}
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();

		$form->setTitle(TextFormat::GRAY . '» ' . TextFormat::LIGHT_PURPLE . 'FollowUI' . TextFormat::GRAY . ' «');

		$playerLabel = TextFormat::LIGHT_PURPLE . $language->formWindow(Language::PLAYERS_LABEL) . TextFormat::WHITE . ':';

		if(count($onlinePlayers) === 0){

			$form->addLabel($language->formWindow(Language::REQUEST_FORM_NOBODY_ONLINE));
			return $form;
		}

		$form->addDropdown($playerLabel, $onlinePlayers);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getDefaultPartyForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null && $event->isInHub()){

					$partyManager = MineceitCore::getPartyManager();

					if(!isset($formData['party-option'])) return;

					$option = $formData['party-option'];

					$index = (int) $data;

					if($index === 0){
						// TODO CHECK FOR PERMISSION TO CREATE A NEW PARTY
						if(!$event->isInParty()){
							$form = FormUtil::getCreatePartyForm($event);
							$event->sendFormWindow($form);
						}
					}elseif($index === 1){
						if($option === 'join'){

							$form = FormUtil::getJoinPartyForm($event);
							$event->sendFormWindow($form, ['parties' => $partyManager->getParties()]);
						}else{
							$party = $event->getParty();
							if($party !== null)
								$party->removePlayer($event);
						}
					}elseif($index === 2){
						$requestHandler = $partyManager->getRequestHandler();
						$requests = $requestHandler->getRequestsOf($event);
						$form = FormUtil::getPartyInbox($event, $requests);
						$event->sendFormWindow($form, ['requests' => $requests]);
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$title = $lang->formWindow(Language::FORM_PARTIES_DEFAULT_TITLE);

		$form->setTitle($title);

		$content = $lang->formWindow(Language::FORM_PARTIES_DEFAULT_CONTENT);

		$form->setContent($content);

		$p = $player->getParty();

		$leave = $lang->formWindow(Language::FORM_PARTIES_DEFAULT_LEAVE);
		$join = $lang->formWindow(Language::FORM_PARTIES_DEFAULT_JOIN);
		$create = $lang->formWindow(Language::FORM_PARTIES_CREATE);
		$inbox = $lang->formWindow(Language::FORM_PARTIES_INBOX);

		$text = ($p !== null) ? $leave : $join;

		$form->addButton($create, 0, "textures/ui/pencil_edit_icon.png");
		$form->addButton($text, 0, "textures/ui/FriendsDiversity.png");
		$form->addButton($inbox, 0, "textures/blocks/trapped_chest_front.png");

		return $form;
	}

	/** --------------------------------------------------------------------------------------- */

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return CustomForm
	 */
	public static function getCreatePartyForm(MineceitPlayer $player) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null && $event->isInHub()){

					$partyManager = MineceitCore::getPartyManager();

					$partyName = TextFormat::clean((string) $data[0]);
					$maxPlayers = (int) $data[1];
					$inviteOnly = (bool) $data[2];

					$lang = $event->getLanguageInfo()->getLanguage();


					if(!preg_match('/[^A-Za-z0-9]\'\ /', $partyName)){
						if(strlen($partyName) <= 25){
							$party = $partyManager->getPartyFromName($partyName);

							if($party === null){
								$partyManager->createParty($event, $partyName, $maxPlayers, !$inviteOnly);
							}else{
								$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::PARTIES_ALREADY_TAKEN));
							}
						}else{
							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::MORE_ALPHABETS_25));
						}
					}else{
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::ONLY_ENG));
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$name = $player->getName();

		$form->setTitle($lang->formWindow(Language::FORM_PARTIES_CREATE_TITLE));

		$default = "$name's Party";

		$partyName = $lang->formWindow(Language::FORM_PARTIES_PARTYNAME) . TextFormat::RESET . ':';

		$maxPlayers = $lang->formWindow(Language::FORM_PARTIES_MAX_PLAYERS) . TextFormat::RESET;

		$inviteOnly = $lang->formWindow(Language::FORM_PARTIES_INVITE_ONLY);

		$max = 4;

		$ranks = $player->getRanks(true);

		foreach($ranks as $rank){
			if($rank === "owner" || $rank === "admin" || $rank === "dev" || $rank === "mod" || $rank === "helper" || $rank === "famous" || $rank === "donatorplus" || $rank === "donator"){
				if($max < 8) $max = 8;
			}elseif($rank === "media" || $rank === "booster" || $rank === "voter" || $rank === "designer" || $rank === "builder"){
				if($max < 6) $max = 6;
			}
		}

		$form->addInput($partyName, $default, $default);

		$form->addSlider($maxPlayers, 2, $max, 1, 2);

		$form->addToggle($inviteOnly, false);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getJoinPartyForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null && $event->isInHub()){

					$index = (int) $data;

					$text = (string) $formData[$index]['text'];

					$lang = $event->getLanguageInfo()->getLanguage();

					if($text !== $lang->generalMessage(Language::NONE)){

						/* @var MineceitParty[] $parties */
						if(!isset($formData['parties']))
							return;

						$parties = array_values($formData['parties']);

						if(!isset($parties[$index]))
							return;

						$partyManager = MineceitCore::getPartyManager();


						$party = $parties[$index];
						$name = $party->getName();

						$party = $partyManager->getPartyFromName($name);

						if($party === null){
							$msg = $lang->generalMessage(Language::PARTIES_ALREADY_EXIST);
							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
							return;
						}

						$maxPlayers = $party->getMaxPlayers();

						$currentPlayers = (int) $party->getPlayers(true);

						$blacklisted = $party->isBlackListed($event);

						if($party->isOpen() && $currentPlayers < $maxPlayers && !$blacklisted){
							if($partyManager->getEventManager()->isInQueue($party)){
								$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::PARTIES_ALREADY_INQUEUE));
								return;
							}elseif($partyManager->getEventManager()->getPartyEvent($party) !== null){
								$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->generalMessage(Language::PARTIES_ALREADY_INGAME));
								return;
							}
							$party->addPlayer($event);
						}else{
							if($currentPlayers >= $maxPlayers){
								$msg = $lang->generalMessage(Language::PARTIES_ALREADY_FULL);
								$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
							}elseif(!$party->isOpen()){
								$msg = $lang->generalMessage(Language::PARTIES_INVITE_ONLY);
								$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
							}elseif($blacklisted){
								$msg = $lang->generalMessage(Language::PARTIES_ALREADY_BLACKLIST);
								$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
							}
						}
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::FORM_PARTIES_JOIN_TITLE));

		$listOfParties = $lang->formWindow(Language::FORM_PARTIES_JOIN_LIST);

		$form->setContent($listOfParties);

		$partyManager = MineceitCore::getPartyManager();

		$parties = $partyManager->getParties();

		$size = count($parties);

		if($size <= 0){
			$none = $lang->generalMessage(Language::NONE);
			$form->addButton($none);
			return $form;
		}

		$openStr = $lang->formWindow(Language::OPEN);
		$closedStr = $lang->formWindow(Language::CLOSED);
		$blacklistedStr = $lang->formWindow(Language::BLACKLISTED);

		foreach($parties as $party){
			$name = TextFormat::BOLD . TextFormat::LIGHT_PURPLE . $party->getName();
			$numPlayers = $party->getPlayers(true);
			$maxPlayers = $party->getMaxPlayers();
			$isBlacklisted = $party->isBlackListed($player);
			$blacklisted = ($isBlacklisted) ? TextFormat::WHITE . '[' . $blacklistedStr . TextFormat::WHITE . '] ' : '';
			$open = $party->isOpen() ? $openStr : $closedStr;
			$text = $blacklisted . $name . "\n" . TextFormat::RESET . TextFormat::LIGHT_PURPLE . $numPlayers . TextFormat::WHITE . '/' . $maxPlayers . TextFormat::GRAY . ' | ' . $open;
			$form->addButton($text);
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer       $player
	 * @param PartyRequest[]|array $requestInbox
	 *
	 * @return SimpleForm
	 */
	public static function getPartyInbox(MineceitPlayer $player, $requestInbox = []) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				$language = $event->getLanguageInfo()->getLanguage();

				$partyManager = MineceitCore::getPartyManager();

				if($data !== null){

					$index = (int) $data;
					if($index !== $language->generalMessage(Language::NONE)){

						if(isset($formData['requests'])){
							$requests = $formData['requests'];
							$keys = array_keys($requests);
							if(!isset($keys[$index])) return;
							$name = $keys[$index];
							$request = $requests[$name];

							if($request instanceof PartyRequest){

								$pName = $event->getName();

								$opponentName = ($pName === $request->getToName()) ? $request->getFromName() : $request->getToName();

								if(($opponent = MineceitUtil::getPlayerExact($opponentName)) instanceof MineceitPlayer && $opponent->isOnline()){

									if($event->isInHub()){

										$party = $request->getParty();

										if($party === null){
											$msg = $language->generalMessage(Language::PARTIES_ALREADY_EXIST);
											$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
											return;
										}

										$maxPlayers = $party->getMaxPlayers();

										$currentPlayers = (int) $party->getPlayers(true);

										$blacklisted = $party->isBlackListed($event);

										if($currentPlayers < $maxPlayers && !$blacklisted){
											if($partyManager->getEventManager()->isInQueue($party)){
												$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::PARTIES_ALREADY_INQUEUE));
												return;
											}elseif($partyManager->getEventManager()->getPartyEvent($party) !== null){
												$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::PARTIES_ALREADY_INGAME));
												return;
											}
											$partyManager->getRequestHandler()->acceptRequest($request);
											$party->addPlayer($event);
										}else{
											if($currentPlayers >= $maxPlayers){
												$msg = $language->generalMessage(Language::PARTIES_ALREADY_FULL);
												$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
											}elseif($blacklisted){
												$msg = $language->generalMessage(Language::PARTIES_ALREADY_BLACKLIST);
												$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
											}
										}
									}else $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::ACCEPT_FAIL_NOT_IN_LOBBY));
								}else{

									$message = null;
									if($opponent === null || !$opponent->isOnline())
										$message = $language->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $opponentName]);

									if($message !== null) $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
								}
							}
						}
					}
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();

		$count = count($requestInbox);

		$form->setTitle($language->formWindow(Language::FORM_PARTIES_INBOX_TITLE));
		$form->setContent('');

		if($count <= 0){
			$form->addButton($language->generalMessage(Language::NONE));
			return $form;
		}

		$keys = array_keys($requestInbox);

		foreach($keys as $name){

			$name = (string) $name;

			$request = $requestInbox[$name];

			$party = $request->getParty()->getName();

			if(($player = MineceitUtil::getPlayerExact($name)) instanceof Player){
				$name = $player->getDisplayName();
			}

			$sentBy = $language->formWindow(Language::FORM_SENT_BY, [
				"name" => $name
			]);

			$text = $sentBy . "\n" . TextFormat::GREEN . $party;

			$form->addButton($text, 0, $request->getTexture());
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $isOwner
	 *
	 * @return SimpleForm
	 */
	public static function getLeavePartyForm(MineceitPlayer $player, bool $isOwner) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$party = $event->getParty();

					$index = (int) $data;

					if($index === 0 && $party !== null)
						$party->removePlayer($event);
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$content = ($isOwner ? $lang->formWindow(Language::FORM_QUESTION_LEAVE_OWNER) : $lang->formWindow(Language::FORM_QUESTION_LEAVE));

		$form->setTitle($lang->formWindow(Language::FORM_PARTIES_LEAVE_TITLE));
		$form->setContent($content);

		$form->addButton($lang->formWindow(Language::YES));
		$form->addButton($lang->formWindow(Language::NO));

		return $form;
	}

	/**
	 * @param MineceitParty $party
	 *
	 * @return SimpleForm
	 */
	public static function getPartyOptionsForm(MineceitParty $party) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$index = (int) $data;

					$party = $event->getParty();

					$name = $event->getName();

					switch($index){

						case 0:

							$form = FormUtil::getPartySettingsForm($party);
							$event->sendFormWindow($form);
							break;

						case 1:

							$form = FormUtil::getPartyInviteForm($event);
							$event->sendFormWindow($form);
							break;

						case 2:

							$players = $party->getPlayers();
							$listPlayers = [];

							foreach($players as $p){
								$pName = $p->getName();
								if($pName !== $name)
									$listPlayers[] = $pName;
							}

							$form = FormUtil::getKickPlayerForm($party, $listPlayers);
							$event->sendFormWindow($form, ['players' => $listPlayers]);
							break;

						case 3:

							$blacklisted = $party->getBlacklisted();

							$form = FormUtil::getBlackListedForm($event, $blacklisted);
							$event->sendFormWindow($form);
							break;

						case 4:

							$players = $party->getPlayers();

							$possiblePromotions = [];

							$ownerName = $event->getName();

							foreach($players as $p){
								$name = $p->getName();
								if($ownerName !== $name)
									$possiblePromotions[] = $name;
							}

							$form = FormUtil::getPartyPromotePlayerForm($event, $possiblePromotions);
							$event->sendFormWindow($form, ['players' => $possiblePromotions]);
							break;
					}
				}
			}
		});

		$owner = $party->getOwner();
		$lang = $owner->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::FORM_PARTIES_OPTION_TITLE));

		$form->setContent($lang->formWindow(Language::FORM_PARTIES_OPTION_CONTENT));

		$form->addButton($lang->formWindow(Language::FORM_PARTIES_OPTION_SETTINGS));

		$form->addButton($lang->formWindow(Language::FORM_PARTIES_OPTION_INVITE));

		$form->addButton($lang->formWindow(Language::FORM_PARTIES_OPTION_KICK));

		$form->addButton($lang->formWindow(Language::FORM_PARTIES_OPTION_BLACKLIST));

		$form->addButton($lang->formWindow(Language::FORM_PARTIES_OPTION_OWNER));

		return $form;
	}

	/**
	 * @param MineceitParty $party
	 *
	 * @return CustomForm
	 */
	public static function getPartySettingsForm(MineceitParty $party) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$inviteOnly = (bool) $data[1];

					$maxPlayers = (int) $data[2];

					$party = $event->getParty();

					$party->setOpen(!$inviteOnly);

					$party->setMaxPlayers($maxPlayers);
				}
			}
		});

		$owner = $party->getOwner();
		$lang = $owner->getLanguageInfo()->getLanguage();

		$inviteOnly = !$party->isOpen();

		$partyName = $lang->formWindow(Language::FORM_PARTIES_PARTYNAME) . TextFormat::RESET . ":\n";

		$inviteOnlyStr = $lang->formWindow(Language::FORM_PARTIES_INVITE_ONLY);

		$maxPlayersStr = $lang->formWindow(Language::FORM_PARTIES_MAX_PLAYERS) . TextFormat::RESET;

		$max = 4;

		$ranks = $owner->getRanks(true);

		foreach($ranks as $rank){
			if($rank === "owner" || $rank === "admin" || $rank === "dev" || $rank === "mod" || $rank === "helper" || $rank === "famous" || $rank === "donatorplus" || $rank === "donator"){
				if($max < 8) $max = 8;
			}elseif($rank === "media" || $rank === "booster" || $rank === "voter" || $rank === "designer" || $rank === "builder"){
				if($max < 6) $max = 6;
			}
		}

		$form->setTitle($lang->formWindow(Language::FORM_PARTIES_SETTINGS_TITLE));

		$form->addLabel($partyName . TextFormat::RESET . " " . $party->getName());

		$form->addToggle($inviteOnlyStr, $inviteOnly);

		$form->addSlider($maxPlayersStr, 2, $max, 1, $party->getMaxPlayers());

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return CustomForm
	 */
	public static function getPartyInviteForm(MineceitPlayer $player) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){


			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					if(!isset($formData[0])) return;

					$firstIndex = $formData[0];

					if(!isset($firstIndex['type'])) return;

					if($firstIndex['type'] !== 'label'){

						$senderLang = $event->getLanguageInfo()->getLanguage();

						$playerName = $firstIndex['options'][(int) $data[0]];

						$partyManager = MineceitCore::getPartyManager();

						$party = $event->getParty();

						$requestHandler = $partyManager->getRequestHandler();

						if(($to = MineceitUtil::getPlayerExact($playerName, true)) !== null && $to instanceof MineceitPlayer){
							if($to->isInParty()){
								$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $senderLang->generalMessage(Language::PLAYER_IN_PARTY, ["name" => $to->getDisplayName()]));
							}else{
								$requestHandler->sendRequest($event, $to, $party);
							}
						}else{
							$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $senderLang->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $playerName]));
						}
					}
				}
			}
		});

		$onlinePlayers = $player->getServer()->getOnlinePlayers();

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::FORM_PARTIES_INVITE));

		$dropdownArr = [];

		$name = $player->getDisplayName();

		$size = count($onlinePlayers);

		foreach($onlinePlayers as $p){
			$pName = $p->getDisplayName();
			if($pName !== $name)
				$dropdownArr[] = $pName;
		}

		if(($size - 1) > 0){
			$form->addDropdown($lang->formWindow(Language::REQUEST_FORM_SEND_TO), $dropdownArr);
		}else
			$form->addLabel($lang->formWindow(Language::REQUEST_FORM_NOBODY_ONLINE));

		return $form;
	}

	/**
	 * @param MineceitParty  $party
	 * @param string[]|array $players
	 *
	 * @return CustomForm
	 */
	public static function getKickPlayerForm(MineceitParty $party, $players = []) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null && isset($data[0], $data[1], $data[2])){

					$index = (int) $data[0];

					$player = $formData['players'][$index];

					$blackList = (bool) $data[2];

					$party = $event->getParty();

					if($party->isPlayer($player)){

						$reason = (string) $data[1];

						$p = $party->getPlayer($player);

						$party->removePlayer($p, $reason, $blackList);
					}else{

						$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $player . ' is no longer in your party!';
						$event->sendMessage($msg);
					}
				}
			}
		});

		$owner = $party->getOwner();
		$lang = $owner->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::FORM_PARTIES_KICK_TITLE));

		$size = count($players);

		if($size <= 0){
			$form->addLabel($lang->formWindow(Language::FORM_PARTIES_NO_PLAYERS));
			return $form;
		}

		$playerLabel = TextFormat::LIGHT_PURPLE . $lang->formWindow(Language::PLAYERS_LABEL) . TextFormat::WHITE . ':';

		$form->addDropdown($playerLabel, $players);

		$form->addInput($lang->formWindow(Language::FORM_PARTIES_REASON));

		$form->addToggle($lang->formWindow(Language::FORM_PARTIES_BLACKLIST), false);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param array|string[] $blacklisted
	 *
	 * @return SimpleForm
	 */
	public static function getBlackListedForm(MineceitPlayer $player, $blacklisted = []) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$party = $event->getParty();

					$server = $event->getServer();

					$index = (int) $data;

					switch($index){

						case 0:

							$players = [];

							$onlinePlayers = $server->getOnlinePlayers();

							foreach($onlinePlayers as $p){
								$name = $p->getName();
								if(!$party->isBlackListed($name) && !$party->isPlayer($name))
									$players[] = $name;
							}

							$form = FormUtil::getEditBlackListForm($event, 'add', $players);
							$event->sendFormWindow($form, ['option' => 'add', 'players' => $players]);
							break;

						case 1:

							$players = $party->getBlacklisted();

							$form = FormUtil::getEditBlackListForm($event, 'remove', $players);
							$event->sendFormWindow($form, ['option' => 'remove', 'players' => $players]);

							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::FORM_PARTIES_BLACKLIST_TITLE));

		$blacklistedStr = $lang->formWindow(Language::BLACKLISTED);

		$content = $blacklistedStr . TextFormat::WHITE . ': ';

		$size = count($blacklisted);

		if($size <= 0){

			$none = $lang->generalMessage(Language::NONE);
			$content .= $none;
		}else{

			$size = count($blacklisted) - 1;
			$count = 0;

			foreach($blacklisted as $player){
				$comma = $count === $size ? '' : ', ';
				$content .= $player . $comma;
				$count++;
			}
		}

		$form->setContent($content);

		$form->addButton($lang->formWindow(Language::FORM_PARTIES_BLACKLIST_ADD));
		$form->addButton($lang->formWindow(Language::FORM_PARTIES_BLACKLIST_REMOVE));

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param string         $type
	 * @param array|string[] $players
	 *
	 * @return CustomForm
	 */
	public static function getEditBlackListForm(MineceitPlayer $player, string $type, array $players) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null && isset($data[0])){

					$index = (int) $data[0];

					$name = (string) $formData['players'][$index];

					$option = (string) $formData['option'];

					$party = $event->getParty();

					if($option === 'add'){
						$party->addToBlacklist($name);
					}elseif($option === 'remove'){
						$party->removeFromBlacklist($name);
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$size = count($players);

		$title = ($type === 'add') ? $lang->formWindow(Language::FORM_PARTIES_BLACKLIST_ADD_TITLE) : $lang->formWindow(Language::FORM_PARTIES_BLACKLIST_REMOVE_TITLE);

		$form->setTitle($title);

		$playerLabel = TextFormat::LIGHT_PURPLE . $lang->formWindow(Language::PLAYERS_LABEL) . TextFormat::WHITE . ':';

		if($size <= 0){
			$label = ($type === 'add') ? $lang->formWindow(Language::FORM_PARTIES_CANT_BLACKLIST) : $lang->formWindow(Language::FORM_PARTIES_NO_BLACKLIST);
			$form->addLabel($label);
			return $form;
		}

		$form->addDropdown($playerLabel, $players);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param array|string[] $possiblePromotions
	 *
	 * @return CustomForm
	 */
	public static function getPartyPromotePlayerForm(MineceitPlayer $player, $possiblePromotions = []) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null && isset($data[0])){

					$index = $data[0];

					$name = (string) $formData['players'][$index];

					$party = $event->getParty();

					if($party->isPlayer($name)){

						$player = $party->getPlayer($name);

						$party->promoteToOwner($player);

						// TODO SEND MESSAGE

					}else{

						// TODO SEND MESSAGE SAYING PLAYER IS NO LONGER IN YOUR PARTY

					}
				}
			}
		});

		// TODO ADD MESSAGES
		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::FORM_PARTIES_OWNER_TITLE));

		$playerLabel = TextFormat::LIGHT_PURPLE . $lang->formWindow(Language::PLAYERS_LABEL) . TextFormat::GRAY . ':';

		$size = count($possiblePromotions);

		if($size <= 0){
			$form->addLabel($lang->formWindow(Language::FORM_PARTIES_NO_PLAYERS));
			return $form;
		}

		$form->addDropdown($playerLabel, $possiblePromotions);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getPartyDuelsForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null && $formData !== null){

					if(isset($formData[$data]['text'])){
						$size = TextFormat::clean(explode("\n", $formData[$data]['text'])[0]);
					}else{
						return;
					}

					if($event->isInHub() && $event->isInParty()){

						$form = FormUtil::getPartyDuelForm($event, intval($size));
						$event->sendFormWindow($form);
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::FORM_PARTIES_DUELS_TITLE));
		$form->setContent($lang->formWindow(Language::DUELS_FORM_DESC));

		$format = TextFormat::WHITE . '%iq% ' . TextFormat::GRAY . $lang->formWindow(Language::DUELS_FORM_INQUEUES);

		$partyManager = MineceitCore::getPartyManager();

		$eventManager = $partyManager->getEventManager();

		$form->addButton(TextFormat::LIGHT_PURPLE . '2vs2' . "\n" . str_replace('%iq%', $eventManager->getPartysInQueue(2), $format), 0);
		$form->addButton(TextFormat::LIGHT_PURPLE . '3vs3' . "\n" . str_replace('%iq%', $eventManager->getPartysInQueue(3), $format), 0);
		$form->addButton(TextFormat::LIGHT_PURPLE . '4vs4' . "\n" . str_replace('%iq%', $eventManager->getPartysInQueue(4), $format), 0);
		if($player->hasVipPermissions() || $player->hasVipPlusPermissions() || $player->hasCreatorPermissions() || $player->hasHelperPermissions() || $player->hasBuilderPermissions()){
			$form->addButton(TextFormat::LIGHT_PURPLE . '5vs5' . "\n" . str_replace('%iq%', $eventManager->getPartysInQueue(5), $format), 0);
		}


		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param int            $size
	 *
	 * @return SimpleForm
	 */
	public static function getPartyDuelForm(MineceitPlayer $player, int $size) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null) use ($size){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null && $formData !== null){

					if(isset($formData[$data]['text'])){
						$queue = TextFormat::clean(explode("\n", $formData[$data]['text'])[0]);
					}else{
						return;
					}


					if($event->isInHub() && $event->isInParty()){

						$partyManager = MineceitCore::getPartyManager();
						$party = $event->getParty();
						$partyManager->getEventManager()->placeInQueue($party, $queue, $size);
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::FORM_PARTIES_DUELS_TITLE));
		$form->setContent($lang->formWindow(Language::DUELS_FORM_DESC));

		$format = TextFormat::WHITE . '%iq% ' . TextFormat::GRAY . $lang->formWindow(Language::DUELS_FORM_INQUEUES);

		$partyManager = MineceitCore::getPartyManager();

		$eventManager = $partyManager->getEventManager();

		$list = MineceitCore::getKits()->getKits();

		foreach($list as $kit){
			if($kit->getMiscKitInfo()->isDuelsEnabled()){

				$name = $kit->getName();
				$numInQueue = $eventManager->getPartysInQueue($size, $name);

				$form->addButton(
					TextFormat::LIGHT_PURPLE . $name . "\n" . str_replace('%iq%', $numInQueue, $format),
					0,
					$kit->getMiscKitInfo()->getTexture()
				);
			}
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getPartyGamesForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					if(isset($formData[$data]['text'])){
						$arena = (string) $formData[$data]['text'];
					}else return;

					$arena = TextFormat::clean(explode("\n", $arena)[0]);
					if($arena === 'One In The Chamber-PG') $arena = 'OITC-PG';

					if($event->isInHub() && $event->isInParty()){

						$party = $event->getParty();
						if($party->getPlayers(true) < 2){
							$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $event->getLanguageInfo()->getLanguage()->generalMessage(Language::PARTIES_PLAYER_NOT_ENOUGH);
							$event->sendMessage($msg);
							return;
						}

						$form = FormUtil::getPartyGamesSizeForm($event, $arena);
						$event->sendFormWindow($form);
					}
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($language->formWindow(Language::FORM_PARTIES_GAMES_TITLE));

		$form->setContent($language->formWindow(Language::FFA_FORM_DESC));

		$arenaHandler = MineceitCore::getArenas();

		$arenas = $arenaHandler->getGamesArenas();

		$size = count($arenas);

		if($size <= 0){
			$form->addButton($language->generalMessage(Language::NONE));
			return $form;
		}

		foreach($arenas as $arena){

			$name = $arena->getName();

			if($name === 'OITC-PG') $name = 'One In The Chamber-PG';

			$form->addButton(TextFormat::LIGHT_PURPLE . $name, 0, $arena->getTexture());
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param string         $arena
	 *
	 * @return CustomForm|null
	 */
	public static function getPartyGamesSizeForm(MineceitPlayer $player, string $arena) : ?CustomForm{

		$form = new CustomForm(function(Player $event, $data = null) use ($arena){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					if(!isset($data[1])) return;

					$size = (int) $data[1];

					if($event->isInHub() && $event->isInParty()){

						$partyManager = MineceitCore::getPartyManager();
						$party = $event->getParty();
						$partyManager->getEventManager()->placeInGames($party, $arena, $size);
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$party = $player->getParty();

		if($party === null) return null;

		$size = $party->getPlayers(true);

		$size = ($size % 2 === 0) ? (int) ($size / 2) : (int) ($size / 2) + 1;

		$form->setTitle($lang->formWindow(Language::FORM_PARTIES_SETTINGS_TITLE));

		$form->addLabel("");

		$form->addSlider("Size", 1, $size, 1, 1);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return CustomForm
	 *
	 * Gets the form for creating ranks.
	 *
	 * TODO: UPDATE WITH NEW PERMISSIONS -> LATER
	 */
	public static function getCreateRankForm(MineceitPlayer $player) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				$language = $event->getLanguageInfo()->getLanguage();

				if($data !== null){

					$name = $data[1];
					$localName = strtolower($name);
					$format = $data[2];
					$permissionIndex = (int) $data[3];

					$permission = Rank::PERMISSION_INDEXES[$permissionIndex];

					$rankHandler = MineceitCore::getRankHandler();

					$rank = $rankHandler->getRank($localName);
					if($rank === null){
						$rank = $rankHandler->getRank($name);
					}

					if($rank !== null){
						$msg = MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->rankMessage($rank->getName(), Language::RANK_EXISTS);
						$event->sendMessage($msg);
						return;
					}

					$created = $rankHandler->createRank($name, $format, $permission);

					if($created){
						$msg = $language->rankMessage($name, Language::RANK_CREATE_SUCCESS);
					}else{
						$msg = $language->rankMessage($name, Language::RANK_CREATE_FAIL);
					}

					$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($language->getMessage(Language::CREATE_RANK_TITLE));

		$form->addLabel($language->getMessage(Language::CREATE_RANK_DESC));

		$form->addInput($language->getMessage(Language::CREATE_RANK_NAME));

		$form->addInput($language->getMessage(Language::CREATE_RANK_FORMAT));

		$form->addDropdown($language->getMessage(Language::CREATE_RANK_PERMS), [
			"Owner",
			"Admin",
			"Mod",
			"Helper",
			"Builder",
			"Content Creator",
			"VIP+",
			"VIP",
			"Normal"
		], 8);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param array          $name
	 *
	 * @return CustomForm
	 *
	 * The ban form.
	 */
	public static function getBanForm(MineceitPlayer $player, array $name = ["name" => ""]) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){

					$type = (int) $data[0];

					$name = (string) $data[1];

					$reason = (string) $data[2];

					if($name === null){
						return true;
					}

					if($reason === null){
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RED . 'You can\'t ban player for no reason');
						return true;
					}
					$day = (int) $data[4];
					$hour = (int) $data[5];
					$min = (int) $data[6];

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

					switch($type){
						case 0:

							if(MineceitCore::MYSQL_ENABLED){
								if($banTime === null) $ban = [0 => '', 1 => strtolower($name), 2 => $reason, 3 => $banTime];
								else $ban = [0 => '', 1 => strtolower($name), 2 => $reason, 3 => $banTime->format('Y-m-d-H-i')];
								MineceitCore::getBanHandler()->addBanList($ban);
							}else{
								$event->getServer()->getNameBans()->addBan($name, $reason, $banTime, $event->getName());
							}

							if(($player = MineceitUtil::getPlayerExact($name)) instanceof Player){
								$player->kick($theReason, false);
							}

							$sendername = $event->getName();
							$announce = TextFormat::GRAY . "-------------------------\n" . TextFormat::RED . "$sendername banned $name\n" . "Reason: " . TextFormat::WHITE . $reason . TextFormat::GRAY . "\n-------------------------";
							if((bool) $data[7]) $event->getServer()->broadcastMessage($announce);
							$title = DiscordUtil::boldText("Ban");
							$description = DiscordUtil::boldText("User:") . " {$sendername} \n\n" . DiscordUtil::boldText("Banned:") . " {$name}\n" . DiscordUtil::boldText("Reason:") . " {$reason}\n" . DiscordUtil::boldText("Expires in:") . " {$expires}\n";
							DiscordUtil::sendBan($title, $description, DiscordUtil::RED);

							Command::broadcastCommandMessage($event, new TranslationContainer("%commands.ban.success", [$player !== null ? $player->getName() : $name]));
							break;
						case 1:
							if(preg_match("/^([01]?\\d\\d?|2[0-4]\\d|25[0-5])\\.([01]?\\d\\d?|2[0-4]\\d|25[0-5])\\.([01]?\\d\\d?|2[0-4]\\d|25[0-5])\\.([01]?\\d\\d?|2[0-4]\\d|25[0-5])$/", $name)){
								$event->getServer()->getIPBans()->addBan($name, $reason, $banTime, $event->getName());

								foreach($event->getServer()->getOnlinePlayers() as $player){
									if($player->getAddress() === $name){
										$playername = $player->getName();
										$event->getServer()->getNameBans()->addBan($playername, $reason, $banTime, $event->getName());
										$player->kick($theReason, false);
										$sendername = $event->getName();
										$title = DiscordUtil::boldText("Ban-IP");
										$description = DiscordUtil::boldText("User:") . " {$sendername} \n\n" . DiscordUtil::boldText("Banned:") . " {$playername}\n" . DiscordUtil::boldText("Reason:") . " {$reason}\n" . DiscordUtil::boldText("Expires in:") . " {$expires} \n";
										DiscordUtil::sendBan($title, $description, DiscordUtil::RED);
									}
								}

								$event->getServer()->getNetwork()->blockAddress($name, -1);
								Command::broadcastCommandMessage($event, new TranslationContainer("commands.banip.success", [$name]));
							}else{
								if(($player = $event->getServer()->getPlayer($name)) instanceof Player){
									$ip = $player->getAddress();
									$event->getServer()->getIPBans()->addBan($ip, $reason, $banTime, $event->getName());

									foreach($event->getServer()->getOnlinePlayers() as $player){
										if($player->getAddress() === $ip){
											$playername = $player->getName();
											$event->getServer()->getNameBans()->addBan($playername, $reason, $banTime, $event->getName());
											$player->kick($theReason, false);
											$sendername = $event->getName();
											$title = DiscordUtil::boldText("Ban-IP");
											$description = DiscordUtil::boldText("User:") . " {$sendername} \n\n" . DiscordUtil::boldText("Banned:") . " {$playername}\n" . DiscordUtil::boldText("Reason:") . " {$reason}\n" . DiscordUtil::boldText("Expires in:") . " {$expires} \n";
											DiscordUtil::sendBan($title, $description, DiscordUtil::RED);

											$event->getServer()->getNetwork()->blockAddress($name, -1);
											Command::broadcastCommandMessage($event, new TranslationContainer("commands.banip.success.players", [$ip, $player->getName()]));
										}
									}
								}else{
									$event->sendMessage(new TranslationContainer("commands.banip.invalid"));
									return false;
								}
							}
							break;
					}
				}
			}
		});

		$form->setTitle('BanUI');
		$form->addDropdown('BanType:', ['Ban', 'Ban-IP']);
		$form->addInput('Enter name: ', '', array_shift($name));
		$form->addInput('Reason: ');
		$form->addLabel("Leave all with 0 for forever ban");
		$form->addSlider("Day/s", 0, 30, 1);
		$form->addSlider("Hour/s", 0, 24, 1);
		$form->addSlider("Minute/s", 0, 60, 5);
		$form->addToggle("Ban Announcement", true);
		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function getShopForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					switch((int) $data){
						case 0:
							$form = self::getTagShopForm($event);
							$event->sendFormWindow($form);
							break;
						case 1:
							$form = self::getCapeShopForm($event);
							$event->sendFormWindow($form);
							break;
						case 2:
							$form = self::getArtifactShopForm($event);
							$event->sendFormWindow($form);
							break;
						case 3:
							$form = self::getShardShopForm($event);
							$event->sendFormWindow($form);
							break;
						case 4:
							$form = AuctionForm::mainAuctionForm($event);
							$event->sendFormWindow($form);
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::ZEQA_SHOP_TITLE));

		$form->setContent("Your Coins: " . TextFormat::YELLOW . $player->getStatsInfo()->getCoins() . TextFormat::RESET . "\nYour Shard: " . TextFormat::AQUA . $player->getStatsInfo()->getShards());
		$form->addButton($lang->formWindow(Language::TAG_SHOP), 0, 'textures/items/name_tag.png');
		$form->addButton($lang->formWindow(Language::CAPE_SHOP), 0, 'textures/ui/dressing_room_capes.png');
		$form->addButton($lang->formWindow(Language::ARTIFACT_SHOP), 0, 'textures/ui/dressing_room_customization.png');
		$form->addButton($lang->formWindow(Language::SHARD_SHOP), 0, 'textures/items/emerald.png');
		$form->addButton($lang->formWindow(Language::AUCTION_HOUSE), 0, 'textures/ui/anvil_icon.png');
		return $form;
	}

	public static function getTagShopForm(MineceitPlayer $player) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){
			$type = 'tag';
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					switch((int) $data){
						case 0:
							$box_item = [
								[TextFormat::DARK_GRAY . TextFormat::BOLD . 'PEASANT', 2499],
								[TextFormat::RED . TextFormat::BOLD . 'KNIGHT', 2000],
								[TextFormat::YELLOW . TextFormat::BOLD . 'NOBLE', 2000],
								[TextFormat::LIGHT_PURPLE . TextFormat::BOLD . 'PRINCESS', 1250],
								[TextFormat::BLUE . TextFormat::BOLD . 'PRINCE', 1250],
								[TextFormat::LIGHT_PURPLE . TextFormat::BOLD . 'QUEEN', 500],
								[TextFormat::GOLD . TextFormat::BOLD . 'KING', 500],
								[TextFormat::RED . TextFormat::BOLD . 'BLAZE', 1]
							];
							$form = self::GachaDetailForm($event, $box_item, TextFormat::GOLD . 'Medieval Honors', 800, $type);
							$event->sendFormWindow($form);
							break;
						case 1:
							$box_item = [
								[TextFormat::DARK_GRAY . TextFormat::BOLD . 'MONKEY', 1500],
								[TextFormat::RED . TextFormat::BOLD . 'PANDA', 500],
								[TextFormat::YELLOW . TextFormat::BOLD . 'SHEEP', 1250],
								[TextFormat::LIGHT_PURPLE . TextFormat::BOLD . 'GOAT', 1500],
								[TextFormat::BLUE . TextFormat::BOLD . 'TURTLE', 1250],
								[TextFormat::LIGHT_PURPLE . TextFormat::BOLD . 'SLOTH', 500],
								[TextFormat::GOLD . TextFormat::BOLD . 'CAT', 1500],
								[TextFormat::GOLD . TextFormat::BOLD . 'DOGE', 500],
								[TextFormat::GOLD . TextFormat::BOLD . 'BEAR', 1500]
							];
							$form = self::GachaDetailForm($event, $box_item, TextFormat::GREEN . 'Zoomania', 400, $type);
							$event->sendFormWindow($form);
							break;
						case 2:
							$box_item = [
								[TextFormat::GRAY . TextFormat::BOLD . 'T' . TextFormat::WHITE . 'e' . TextFormat::GRAY . 's' . TextFormat::WHITE . 'l' . TextFormat::GRAY . 'a', 500],
								[TextFormat::YELLOW . TextFormat::BOLD . 'Galileo', 1250],
								[TextFormat::GOLD . TextFormat::BOLD . 'Alchemist', 1250],
								[TextFormat::GREEN . TextFormat::BOLD . 'Einstein', 1000],
								[TextFormat::RED . TextFormat::BOLD . 'Newton', 1000],
								[TextFormat::BLUE . TextFormat::BOLD . 'DaVinci', 500],
								[TextFormat::LIGHT_PURPLE . TextFormat::BOLD . 'Aristotle', 1250],
								[TextFormat::GRAY . TextFormat::BOLD . 'Hawking', 1250],
								[TextFormat::YELLOW . TextFormat::BOLD . 'MarieCurie', 2000]
							];
							$form = self::GachaDetailForm($event, $box_item, TextFormat::RED . 'Sci A' . TextFormat::AQUA . 'nd Teach', 800, $type);
							$event->sendFormWindow($form);
							break;
						case 3:
							$box_item = [
								[TextFormat::BLUE . TextFormat::BOLD . 'DONTpingStaff', 500],
								[TextFormat::LIGHT_PURPLE . TextFormat::BOLD . 'Distracted' . TextFormat::WHITE . 'BF', 1500],
								[TextFormat::DARK_RED . TextFormat::BOLD . 'N' . TextFormat::RED . 'y' . TextFormat::GOLD . 'a' . TextFormat::YELLOW . 'n' . TextFormat::GREEN . 'Z' . TextFormat::DARK_GREEN . 'C' . TextFormat::AQUA . 'a' . TextFormat::DARK_AQUA . 't', 500],
								[TextFormat::RED . TextFormat::BOLD . 'Troll' . TextFormat::WHITE . 'Face', 1000],
								[TextFormat::YELLOW . TextFormat::BOLD . 'Coffin' . TextFormat::WHITE . 'Dance', 1000],
								[TextFormat::RED . TextFormat::BOLD . 'Trololo', 1500],
								[TextFormat::AQUA . TextFormat::BOLD . 'Ice' . TextFormat::WHITE . 'Bucket', 2000],
								[TextFormat::BLUE . TextFormat::BOLD . 'Dabbing', 2000]
							];
							$form = self::GachaDetailForm($event, $box_item, TextFormat::LIGHT_PURPLE . 'Don\'t Be Mean Be MEME ', 1000, $type);
							$event->sendFormWindow($form);
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::TAG_SHOP_TITLE));

		$form->setContent("Your Coins: " . TextFormat::YELLOW . $player->getStatsInfo()->getCoins() . TextFormat::RESET . "\nYour Shard: " . TextFormat::AQUA . $player->getStatsInfo()->getShards());
		$form->addButton(TextFormat::GOLD . 'Medieval Honors ' . TextFormat::YELLOW . '800', 0, 'textures/ui/icon_best3.png');
		$form->addButton(TextFormat::GREEN . 'Zoomania ' . TextFormat::YELLOW . '400', 0, 'textures/ui/promo_chicken.png');
		$form->addButton(TextFormat::RED . 'Sci A' . TextFormat::AQUA . 'nd Teach ' . TextFormat::YELLOW . '800', 0, 'textures/items/cauldron.png');
		$form->addButton(TextFormat::LIGHT_PURPLE . 'Don\'t Be Mean Be MEME ' . TextFormat::YELLOW . '1000', 0, 'textures/ui/dressing_room_customization.png');

		return $form;
	}

	public static function GachaDetailForm(MineceitPlayer $player, array $box_item, string $box_name, int $price, $type) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null) use ($box_item, $price, $type){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					switch((int) $data){
						case 0:
							$event->gachaBox($box_item, $price, $type);
							break;
						case 1:
							$onlinePlayers = $event->getServer()->getOnlinePlayers();
							$online = [];

							foreach($onlinePlayers as $player){
								$name = $player->getDisplayName();
								$online[] = $name;
							}

							$form = self::getGiftForm($event, $online, $box_item, $price, $type);
							$event->sendFormWindow($form, ['online-players' => $online, 'items' => $box_item, 'price' => $price, 'type' => $type]);
							break;
					}
				}
			}
		});

		$form->setTitle($box_name);

		$random_range = 0;
		foreach($box_item as $item)
			$random_range = $random_range + $item[1];

		$drop_rates = [];
		foreach($box_item as $item)
			$drop_rates[] = $item[1];

		array_multisort($drop_rates, $box_item);

		$content = '';
		foreach($box_item as $item){
			$content_line = $item[0] . TextFormat::RESET . TextFormat::AQUA . ' ' . (($item[1] / $random_range) * 100) . TextFormat::YELLOW . " percent\n" . TextFormat::RESET;
			$content = $content . $content_line;
		}

		$form->setContent("Your Coins: " . TextFormat::YELLOW . $player->getStatsInfo()->getCoins() . TextFormat::RESET . "\nYour Shard: " . TextFormat::AQUA . $player->getStatsInfo()->getShards() . TextFormat::RESET . "\n\n" . $content . "\n");
		$form->addButton(TextFormat::GREEN . "Buy\n" . TextFormat::YELLOW . $price);
		$form->addButton(TextFormat::GREEN . "Gift\n" . TextFormat::YELLOW . $price);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param array|string[] $onlinePlayers
	 * @param array          $box_item
	 * @param int            $price
	 * @param                $type
	 *
	 * @return CustomForm
	 */
	public static function getGiftForm(MineceitPlayer $player, $onlinePlayers = [], array $box_item, int $price, $type) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					$name = $formData['online-players'][(int) $data[0]];
					$box_item = $formData['items'];
					$price = $formData['price'];
					$type = $formData['type'];

					if(($player = MineceitUtil::getPlayerExact($name, true)) !== null && $player instanceof MineceitPlayer){
						$event->gachaBox($box_item, $price, $type, $player);
					}else{
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $event->getLanguageInfo()->getLanguage()->generalMessage(Language::PLAYER_NOT_ONLINE, ["name" => $name]));
					}
				}
			}
		});

		$language = $player->getLanguageInfo()->getLanguage();

		$form->setTitle(TextFormat::GRAY . '» ' . TextFormat::LIGHT_PURPLE . 'GiftUI' . TextFormat::GRAY . ' «');

		$playerLabel = TextFormat::LIGHT_PURPLE . $language->formWindow(Language::PLAYERS_LABEL) . TextFormat::WHITE . ':';

		$form->addDropdown($playerLabel, $onlinePlayers, array_search($player->getDisplayName(), $onlinePlayers));

		return $form;
	}

	public static function getCapeShopForm(MineceitPlayer $player) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){
			$type = 'cape';
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					switch((int) $data){
						case 0:
							$box_item = [
								['Bear', 2000],
								['Duck', 2000],
								['Fox', 1500],
								['Kitty', 500],
								['Penguin', 1500],
								['Turtle', 500],
								['Whitewolf', 2000]
							];
							$form = self::GachaDetailForm($event, $box_item, TextFormat::GREEN . 'Wild Cuteness', 1200, $type);
							$event->sendFormWindow($form);
							break;
						case 1:
							$box_item = [
								['Enchant', 1500],
								['Enderman', 1500],
								['Endermanattack', 2000],
								['Energy', 2500],
								['Eva', 2500]
							];
							$form = self::GachaDetailForm($event, $box_item, TextFormat::GREEN . 'As E As Possible', 1200, $type);
							$event->sendFormWindow($form);
							break;
						case 2:
							$box_item = [
								['3Blade', 2000],
								['DarkBlueL', 2000],
								['DevilSmile', 1500],
								['AdamWarlock', 500],
								['TheCutestRabbit', 1500],
								['Bruhhh', 500],
								['TheDragonSmile', 2000]
							];
							$form = self::GachaDetailForm($event, $box_item, TextFormat::GOLD . 'Go Bruhhh & Smile', 1500, $type);
							$event->sendFormWindow($form);
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::CAPE_SHOP_TITLE));

		$form->setContent("Your Coins: " . TextFormat::YELLOW . $player->getStatsInfo()->getCoins() . TextFormat::RESET . "\nYour Shard: " . TextFormat::AQUA . $player->getStatsInfo()->getShards());
		$form->addButton(TextFormat::GREEN . 'Wild Cuteness ' . TextFormat::YELLOW . '1200', 0, 'textures/ui/promo_wolf.png');
		$form->addButton(TextFormat::LIGHT_PURPLE . 'As E as Possible ' . TextFormat::YELLOW . '1200', 0, 'textures/ui/slow_falling_effect.png');
		$form->addButton(TextFormat::GOLD . 'Go Bruhhh & Smile ' . TextFormat::YELLOW . '1500', 0, 'textures/ui/icon_deals.png');

		return $form;
	}

	public static function getArtifactShopForm(MineceitPlayer $player) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){
			$type = 'artifact';
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					switch((int) $data){
						case 0:
							$box_item = [
								['Halo', 500],
								['Crown', 1000],
								['BackCap', 2500],
								['Viking', 3000],
								['ThunderCloud', 3000]
							];
							$form = self::GachaDetailForm($event, $box_item, TextFormat::LIGHT_PURPLE . 'Intern Got', 1200, $type);
							$event->sendFormWindow($form);
							break;
						case 1:
							$box_item = [
								['MiniAngelWing', 1000],
								['AngelWing', 1000],
								['EnderWing', 3000],
								['DevilWing', 2000],
								['PhantomWing', 3000]
							];
							$form = self::GachaDetailForm($event, $box_item, TextFormat::AQUA . 'YOU CAN FRIED', 1200, $type);
							$event->sendFormWindow($form);
							break;
						case 2:
							$box_item = [
								['Questionmark', 1000],
								['Santa', 1000],
								['Necktie', 3000],
								['Backpack', 2000],
								['Headphones', 3000]
							];
							$form = self::GachaDetailForm($event, $box_item, TextFormat::RED . 'XOOP' . TextFormat::WHITE . 'REME', 1500, $type);
							$event->sendFormWindow($form);
							break;
						case 3:
							$box_item = [
								['HeadphoneNote', 1000],
								['BlazeRod', 1000],
								['Bubble', 1000],
								['Katana', 3000],
								['Sickle', 2000],
								['SWAT Shield', 2000]
							];
							$form = self::GachaDetailForm($event, $box_item, TextFormat::GREEN . 'PARTIES' . TextFormat::RED . 'CLES', 1700, $type);
							$event->sendFormWindow($form);
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::ARTIFACT_SHOP_TITLE));

		$form->setContent("Your Coins: " . TextFormat::YELLOW . $player->getStatsInfo()->getCoins() . TextFormat::RESET . "\nYour Shard: " . TextFormat::AQUA . $player->getStatsInfo()->getShards());
		$form->addButton(TextFormat::LIGHT_PURPLE . 'Intern Got ' . TextFormat::YELLOW . '1200', 0, 'textures/ui/icon_best3.png');
		$form->addButton(TextFormat::AQUA . 'YOU CAN FRIED ' . TextFormat::YELLOW . '1200', 0, 'textures/ui/icon_trending.png');
		$form->addButton(TextFormat::RED . 'XOOP' . TextFormat::WHITE . 'REME ' . TextFormat::YELLOW . '1500', 0, 'textures/ui/icon_panda.png');
		$form->addButton(TextFormat::GREEN . 'PARTIES' . TextFormat::RED . 'CLES ' . TextFormat::YELLOW . '1700', 0, 'textures/ui/time_5night.png');

		return $form;
	}

	public static function getShardShopForm(MineceitPlayer $player) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					switch((int) $data){
						case 0:
							$event->BuyShard(TextFormat::DARK_PURPLE . TextFormat::BOLD . 'BadLucker', 2000, 'tag');
							break;
						case 1:
							$event->BuyShard(TextFormat::GOLD . TextFormat::BOLD . 'FORTUNE', 3500, 'tag');
							break;
						case 2:
							$event->BuyShard('Sad', 5000, 'cape');
							break;
						case 3:
							$event->BuyShard('Rainbow', 7000, 'cape');
							break;
						case 4:
							$event->BuyShard('Hacker', 6000, 'cape');
							break;
						case 5:
							$event->BuyShard('Koala', 5000, 'artifact');
							break;
						case 6:
							$event->BuyShard('LightSaber', 5000, 'artifact');
							break;
						case 7:
							$event->BuyShard('Witchhat', 7000, 'artifact');
							break;
						case 8:
							if($event->getStatsInfo()->getShards() >= 1000){
								$event->getStatsInfo()->removeShards(1000);
								$event->getStatsInfo()->addCoins(rand(250, 1000));
							}else $event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RED . 'Not enough Shards.');
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::SHARD_SHOP_TITLE));
		$form->setContent("Your Coins: " . TextFormat::YELLOW . $player->getStatsInfo()->getCoins() . TextFormat::RESET . "\nYour Shard: " . TextFormat::AQUA . $player->getStatsInfo()->getShards());
		$form->addButton('[TAG] ' . TextFormat::DARK_PURPLE . TextFormat::BOLD . 'BadLucker ' . TextFormat::AQUA . '2000', 0, 'textures/ui/bad_omen_effect.png');
		$form->addButton('[TAG] ' . TextFormat::GOLD . TextFormat::BOLD . 'FORTUNE ' . TextFormat::AQUA . '3500', 0, 'textures/ui/promo_gift_big.png');
		$form->addButton('[CAPE] ' . TextFormat::DARK_GRAY . TextFormat::BOLD . 'Sad ' . TextFormat::AQUA . '5000', 0, 'textures/ui/promo_creeper.png');
		$form->addButton('[CAPE] ' . TextFormat::LIGHT_PURPLE . TextFormat::BOLD . 'Rainbow ' . TextFormat::AQUA . '7000', 0, 'textures/ui/portalBg.png');
		$form->addButton('[CAPE] ' . TextFormat::WHITE . TextFormat::BOLD . 'Hacker ' . TextFormat::AQUA . '6000', 0, 'textures/ui/portalBg.png');
		$form->addButton('[ARTIFACT] ' . TextFormat::GREEN . TextFormat::BOLD . 'Koala ' . TextFormat::AQUA . '5000', 0, 'textures/ui/icon_panda.png');
		$form->addButton('[ARTIFACT] ' . TextFormat::RED . TextFormat::BOLD . 'LightSaber ' . TextFormat::AQUA . '5000', 0, 'textures/items/diamond_sword.png');
		$form->addButton('[ARTIFACT] ' . TextFormat::DARK_GREEN . TextFormat::BOLD . 'Witchhat ' . TextFormat::AQUA . '7000', 0, 'textures/items/potion_bottle_splash_harm.png');
		$form->addButton('[RANDOM] ' . TextFormat::YELLOW . TextFormat::BOLD . 'Lucky Coins ' . TextFormat::AQUA . '1000', 0, 'textures/ui/icon_minecoin_9x9.png');
		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param                $bot
	 *
	 * @return CustomForm
	 */
	public static function getClutchBotForm(MineceitPlayer $player, $bot) : CustomForm{

		$form = new CustomForm(function(Player $event, $data = null){

			if($event instanceof MineceitPlayer){

				$formData = $event->removeFormData();

				if($data !== null){

					$bot = $formData['bot'];

					$bot->setKnockBack((int) $data[0]);
					$bot->setHitReg((int) $data[1]);
					$bot->setAttackCoolDown((int) $data[2]);
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::CLUTCH_SETTING_TITLE));
		$form->addSlider($lang->formWindow(Language::CLUTCH_SETTING_KNOCKBACK), 1, 6, 1, $bot->getKnockBack());
		$form->addSlider($lang->formWindow(Language::CLUTCH_SETTING_HITREG), 1, 10, 1, $bot->getHitReg());
		$form->addSlider($lang->formWindow(Language::CLUTCH_SETTING_ATCOOLDOWN), 0, 5, 1, $bot->getAttackCoolDown());
		return $form;
	}

	public static function getBattlePassForm(MineceitPlayer $player) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					switch((int) $data){
						case 0:
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->formWindow(Language::BATTLE_PASS_TITLE));

		$BpDetail = [
			[500, 'free', TextFormat::YELLOW . 'Coins', 500],
			[500, 'buy', TextFormat::YELLOW . 'Coins', 1000],
			[1000, 'free', TextFormat::AQUA . 'Shards', 350],
			[1000, 'buy', TextFormat::AQUA . 'Shards', 700],
			[2000, 'free', TextFormat::YELLOW . 'Coins', 1000],
			[2000, 'buy', TextFormat::YELLOW . 'Coins', 1500],
			[3000, 'free', TextFormat::AQUA . 'Shards', 500],
			[3000, 'buy', TextFormat::AQUA . 'Shards', 1000],
			[4500, 'free', TextFormat::BLUE . 'Tag', TextFormat::BOLD . TextFormat::YELLOW . 'GOATED'],
			[4500, 'buy', TextFormat::BLUE . 'Tag', TextFormat::BOLD . TextFormat::GREEN . 'GRIN' . TextFormat::WHITE . 'DER'],
			[6000, 'free', TextFormat::YELLOW . 'Coins', 1500],
			[6000, 'buy', TextFormat::YELLOW . 'Coins', 2500],
			[8000, 'free', TextFormat::AQUA . 'Shards', 500],
			[8000, 'buy', TextFormat::AQUA . 'Shards', 1000],
			[10000, 'free', TextFormat::YELLOW . 'Coins', 2000],
			[10000, 'buy', TextFormat::YELLOW . 'Coins', 3500],
			[12000, 'free', TextFormat::AQUA . 'Shards', 1000],
			[12000, 'buy', TextFormat::AQUA . 'Shards', 1700],
			[15000, 'free', TextFormat::RED . 'Cape', 'Moonlight'],
			[15000, 'buy', TextFormat::RED . 'Cape', 'Try'],
			[18000, 'free', TextFormat::YELLOW . 'Coins', 3000],
			[18000, 'buy', TextFormat::YELLOW . 'Coins', 4200],
			[20000, 'free', TextFormat::AQUA . 'Shards', 1500],
			[20000, 'buy', TextFormat::AQUA . 'Shards', 2500],
			[22000, 'free', TextFormat::YELLOW . 'Coins', 3500],
			[22000, 'buy', TextFormat::YELLOW . 'Coins', 5000],
			[25000, 'free', TextFormat::AQUA . 'Shards', 2500],
			[25000, 'buy', TextFormat::AQUA . 'Shards', 5000],
			[28000, 'free', TextFormat::LIGHT_PURPLE . 'Artifact', 'RedWing'],
			[28000, 'buy', TextFormat::LIGHT_PURPLE . 'Artifact', 'DragonWing'],
			[30000, 'free', TextFormat::YELLOW . 'Coins', 7000],
			[30000, 'buy', TextFormat::YELLOW . 'Coins', 10000],
			[35000, 'free', TextFormat::AQUA . 'Shards', 3500],
			[35000, 'buy', TextFormat::AQUA . 'Shards', 7000],
			[38000, 'free', TextFormat::BLUE . 'Tag', TextFormat::BOLD . TextFormat::RED . 'L'],
			[38000, 'buy', TextFormat::BLUE . 'Tag', TextFormat::BOLD . TextFormat::GOLD . 'TERRES' . TextFormat::WHITE . 'TRIAL'],
			[40000, 'free', TextFormat::RED . 'Cape', 'Sunset'],
			[40000, 'buy', TextFormat::RED . 'Cape', 'Assasin'],
			[45000, 'free', TextFormat::LIGHT_PURPLE . 'Artifact', 'SusanooBlue'],
			[45000, 'buy', TextFormat::LIGHT_PURPLE . 'Artifact', 'Kagune']
		];

		$regular_pass = TextFormat::DARK_GRAY . 'Regular ' . TextFormat::WHITE . 'Pass' . TextFormat::RESET . "\n\n";
		$elite_pass = TextFormat::GOLD . 'Elite ' . TextFormat::WHITE . 'Pass' . TextFormat::RESET . "\n\n";
		$i = 0;
		foreach($BpDetail as $BpItem){

			if($player->getStatsInfo()->getExp() >= $BpItem[0] && !$player->isBpClaimed($i) && ($BpItem[1] === 'free' || ($BpItem[1] === 'buy' && $player->isBuyBattlePass()))){
				switch(TextFormat::clean($BpItem[2])){
					case 'Tag':
						$player->setValidTags($BpItem[3]);
						break;
					case 'Cape':
						$player->setValidCapes($BpItem[3]);
						break;
					case 'Artifact':
						$player->setValidStuffs($BpItem[3]);
						break;
					case 'Coins':
						$player->getStatsInfo()->addCoins($BpItem[3]);
						break;
					case 'Shards':
						$player->getStatsInfo()->addShards($BpItem[3]);
						break;
				}
				$player->setBpClaimed($i);
			}

			if($player->isBpClaimed($i)) $content_line = TextFormat::WHITE . $BpItem[0] . TextFormat::GREEN . ' EXP' . TextFormat::DARK_GRAY . ' - ' . TextFormat::RESET;
			else $content_line = TextFormat::GRAY . $BpItem[0] . TextFormat::GREEN . ' EXP' . TextFormat::DARK_GRAY . ' - ' . TextFormat::RESET;

			$content_line = $content_line . $BpItem[3] . ' ' . TextFormat::RESET;
			$content_line = $content_line . $BpItem[2] . "\n" . TextFormat::RESET;

			if($BpItem[1] === 'free') $regular_pass = $regular_pass . $content_line;
			else $elite_pass = $elite_pass . $content_line;

			$i = $i + 1;
		}

		$form->setContent("Your EXP: " . TextFormat::GREEN . $player->getStatsInfo()->getExp() . TextFormat::RESET . "\n\n" . $regular_pass . "\n" . $elite_pass . "\n");
		$form->addButton(TextFormat::RED . "Close", 0, 'textures/gui/newgui/XPress.png');

		return $form;
	}
}
