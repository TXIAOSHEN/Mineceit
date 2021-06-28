<?php

declare(strict_types=1);

namespace mineceit;

use mineceit\commands\MineceitCommand;
use mineceit\discord\DiscordUtil;
use mineceit\game\entities\bots\AbstractCombatBot;
use mineceit\game\entities\replay\ReplayHuman;
use mineceit\game\FormUtil;
use mineceit\game\inventories\menus\inventory\MineceitBaseInv;
use mineceit\game\items\ItemHandler;
use mineceit\parties\events\types\match\data\MineceitTeam;
use mineceit\parties\events\types\PartyDuel;
use mineceit\parties\events\types\PartyGames;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\DelayScoreboardUpdate;
use mineceit\scoreboard\Scoreboard;
use mineceit\scoreboard\ScoreboardUtil;
use mineceit\utils\Math;
use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\Skin;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CraftingGrid;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\CraftingTransaction;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MineceitListener implements Listener{

	/* @var Server */
	private $server;

	/* @var MineceitCore */
	private $core;

	public function __construct(MineceitCore $core){
		$this->server = $core->getServer();
		$this->core = $core;
	}

	/**
	 * @param PlayerCreationEvent $event
	 */
	public function onPlayerCreation(PlayerCreationEvent $event) : void{
		$event->setPlayerClass(MineceitPlayer::class);
	}

	/**
	 * @param PlayerChangeSkinEvent $event
	 */
	public function onPlayerChangeSkin(PlayerChangeSkinEvent $event) : void{
		$case = 0;
		$player = $event->getPlayer();
		$name = $player->getName();
		$event->setCancelled();
		$cosmetic = MineceitCore::getCosmeticHandler();
		if($player instanceof MineceitPlayer){
			if(strlen($event->getNewSkin()->getSkinData()) >= 131072 || strlen($event->getNewSkin()->getSkinData()) <= 8192 || $cosmetic->getSkinTransparencyPercentage($event->getNewSkin()->getSkinData()) > 6){
				copy($cosmetic->stevePng, $cosmetic->saveSkin . "$name.png");
				$cosmetic->resetSkin($player);
				$case = 1;
			}else{
				$skin = new Skin($event->getNewSkin()->getSkinId(), $event->getNewSkin()->getSkinData(), '', $event->getNewSkin()->getGeometryName() !== 'geometry.humanoid.customSlim' ? 'geometry.humanoid.custom' : $event->getNewSkin()->getGeometryName(), '');
				$cosmetic->saveSkin($skin->getSkinData(), $name);
			}

			if($player->getDisguiseInfo()->isDisguised()){
				if($case === 1){
					$player->setSkin(new Skin($player->getSkin()->getSkinId(), $player->getSkin()->getSkinData(), '', $player->getSkin()->getGeometryName() !== 'geometry.humanoid.customSlim' ? 'geometry.humanoid.custom' : $player->getSkin()->getGeometryName(), ''));
				}else{
					$player->setSkin(new Skin($event->getNewSkin()->getSkinId(), $event->getNewSkin()->getSkinData(), '', $event->getNewSkin()->getGeometryName() !== 'geometry.humanoid.customSlim' ? 'geometry.humanoid.custom' : $event->getNewSkin()->getGeometryName(), ''));
				}
				return;
			}

			if($player->getStuff() !== ""){
				$cosmetic->setSkin($player, $player->getStuff());
			}else if($player->getCape() !== ""){
				$capedata = $cosmetic->getCapeData($player->getCape());
				if($case === 1){
					$player->setSkin(new Skin($player->getSkin()->getSkinId(), $player->getSkin()->getSkinData(), $capedata, $player->getSkin()->getGeometryName() !== 'geometry.humanoid.customSlim' ? 'geometry.humanoid.custom' : $player->getSkin()->getGeometryName(), ''));
				}else{
					$player->setSkin(new Skin($event->getNewSkin()->getSkinId(), $event->getNewSkin()->getSkinData(), $capedata, $event->getNewSkin()->getGeometryName() !== 'geometry.humanoid.customSlim' ? 'geometry.humanoid.custom' : $event->getNewSkin()->getGeometryName(), ''));
				}
			}else{
				if($case === 1){
					$player->setSkin(new Skin($player->getSkin()->getSkinId(), $player->getSkin()->getSkinData(), '', $player->getSkin()->getGeometryName() !== 'geometry.humanoid.customSlim' ? 'geometry.humanoid.custom' : $player->getSkin()->getGeometryName(), ''));
				}else{
					$player->setSkin(new Skin($event->getNewSkin()->getSkinId(), $event->getNewSkin()->getSkinData(), '', $event->getNewSkin()->getGeometryName() !== 'geometry.humanoid.customSlim' ? 'geometry.humanoid.custom' : $event->getNewSkin()->getGeometryName(), ''));
				}
			}
		}
	}

	/**
	 * @param PlayerPreLoginEvent $event
	 */
	public function onPreLogin(PlayerPreLoginEvent $event) : void{
		$player = $event->getPlayer();
		$name = $player->getName();

		if(!$player->isOp()){
			if(MineceitCore::MYSQL_ENABLED){
				if(count($ban = MineceitCore::getBanHandler()->isInBanList($player)) !== 0){

					$reason = $ban[2];
					$remaintime = "Forever";
					$bantime = new \DateTime('NOW');

					$flag = false;
					if($ban[3] !== null){
						$expiretime = date_create_from_format('Y-m-d-H-i', $ban[3]);
						if($expiretime instanceof \DateTime){
							if($expiretime < $bantime){
								$flag = true;
								MineceitCore::getBanHandler()->removeBanList(strtolower($name));
							}else{
								$remaintime = $bantime->diff($expiretime);
								$remaintime = $remaintime->format("%d day(s) , %h hour(s) , %i minute(s)");
							}
						}
					}

					if(!$flag){
						$theReason = TextFormat::BOLD . TextFormat::RED . 'Network Ban' . "\n\n" . TextFormat::RESET;
						$theReason .= TextFormat::RED . 'Reason ' . TextFormat::DARK_GRAY . '» ' . TextFormat::GRAY . $reason . "\n";
						$theReason .= TextFormat::RED . 'Duration ' . TextFormat::DARK_GRAY . '» ' . TextFormat::GRAY . $remaintime . "\n";
						$theReason .= TextFormat::GRAY . 'Appeal at: ' . TextFormat::RED . 'https://www.zeqa.net/Appeal';
						$player->kick($theReason, false);
						return;
					}
				}
			}else{
				if($this->server->getNameBans()->isBanned($name)){
					$reason = $this->server->getNameBans()->getEntry($name)->getReason();
					$remaintime = "Forever";
					$bantime = new \DateTime('NOW');

					if(($expiretime = $this->server->getNameBans()->getEntry($name)->getExpires()) instanceof \DateTime){
						$remaintime = $bantime->diff($expiretime);
						$remaintime = $remaintime->format("%d day(s) , %h hour(s) , %i minute(s)");
					}

					$theReason = TextFormat::BOLD . TextFormat::RED . 'Network Ban' . "\n\n" . TextFormat::RESET;
					$theReason .= TextFormat::RED . 'Reason ' . TextFormat::DARK_GRAY . '» ' . TextFormat::GRAY . $reason . "\n";
					$theReason .= TextFormat::RED . 'Duration ' . TextFormat::DARK_GRAY . '» ' . TextFormat::GRAY . $remaintime . "\n";
					$theReason .= TextFormat::GRAY . 'Appeal at: ' . TextFormat::RED . 'https://www.zeqa.net/Appeal';
					$player->kick($theReason, false);
					return;
				}
			}
		}


		if($player instanceof MineceitPlayer){

			//MineceitCore::getPlayerHandler()->getIPManager()->checkIPSafe($player);

			$cosmetic = MineceitCore::getCosmeticHandler();

			if(strlen($player->getSkin()->getSkinData()) >= 131072 || strlen($player->getSkin()->getSkinData()) <= 8192 || $cosmetic->getSkinTransparencyPercentage($player->getSkin()->getSkinData()) > 6){
				copy($cosmetic->stevePng, $cosmetic->saveSkin . "$name.png");
				$cosmetic->resetSkin($player);
			}else{
				$skin = new Skin($player->getSkin()->getSkinId(), $player->getSkin()->getSkinData(), '', $player->getSkin()->getGeometryName() !== 'geometry.humanoid.customSlim' ? 'geometry.humanoid.custom' : $player->getSkin()->getGeometryName(), '');
				$cosmetic->saveSkin($skin->getSkinData(), $name);
			}
		}
	}

	/**
	 * @param PlayerJoinEvent $event
	 */
	public function onJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		if($player instanceof MineceitPlayer){

			$playerHandler = MineceitCore::getPlayerHandler();

			$event->setJoinMessage("");

			// if ($playerHandler->doKickOnJoin($player)) {
			//     return;
			// }

			$player->getExtensions()->clearAll();

			$level = $this->server->getDefaultLevel();

			$pos = $level->getSpawnLocation();

			$player->teleport($pos);

			$playerHandler->loadPlayerData($player);

			$player->setImmobile(true);

			$player->onJoin();

			ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::ONLINE_PLAYERS, $player);
		}
	}

	/**
	 * @param PlayerQuitEvent $event
	 */
	public function onLeave(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if($player instanceof MineceitPlayer){
			$duelHandler = MineceitCore::getDuelHandler();

			$botHandler = MineceitCore::getBotHandler();

			$playerHandler = MineceitCore::getPlayerHandler();

			$partyManager = MineceitCore::getPartyManager();

			if($player->isInQueue()){
				$duelHandler->removeFromQueue($player, false);
			}

			// if($player->isFollowing()){
			//     $followed = $player->getFollowing();
			//     if (($follow = MineceitUtil::getPlayerExact($followed,true)) !== null && $follow instanceof MineceitPlayer) {
			//         $follow->setFollower($player->getName(), false);
			//     }
			// }

			// if($player->isFollowed()){
			//     $follows = $player->getFollower();
			//     foreach($follows as $follow){
			//         $follow->setFollowing();
			//     }
			// }

			if($player->isFrozen()){
				$name = $player->getName();
				$banTime = new \DateTime('NOW');
				$banTime->modify("+30 days");
				$banTime->format(\DateTime::ISO8601);

				$reason = "Logout while frozen";
				$expires = "30 day(s) 0 hour(s) 0 min(s)";
				$theReason = TextFormat::BOLD . TextFormat::RED . 'Network Ban' . "\n\n" . TextFormat::RESET;
				$theReason .= TextFormat::RED . 'Reason ' . TextFormat::DARK_GRAY . '» ' . TextFormat::GRAY . $reason . "\n";
				$theReason .= TextFormat::RED . 'Duration ' . TextFormat::DARK_GRAY . '» ' . TextFormat::GRAY . $expires . "\n";
				$theReason .= TextFormat::GRAY . 'Appeal at: ' . TextFormat::RED . 'https://www.zeqa.net/Appeal';

				if(MineceitCore::MYSQL_ENABLED){
					$ban = [0 => '', 1 => strtolower($name), 2 => "Frozen", 3 => $banTime->format('Y-m-d-H-i')];
					MineceitCore::getBanHandler()->addBanList($ban);
				}else{
					$this->server->getNameBans()->addBan($name, $reason, $banTime, "Frozen");
				}

				$player->kick($theReason, false);

				$title = DiscordUtil::boldText("Ban");
				$description = DiscordUtil::boldText("User:") . " Frozen \n\n" . DiscordUtil::boldText("Banned:") . " {$name}\n" . DiscordUtil::boldText("Reason:") . " {$reason}\n" . DiscordUtil::boldText("Expires in:") . " {$expires} \n";
				DiscordUtil::sendBan($title, $description, DiscordUtil::RED);
			}

			if($player->isInParty()){

				$party = $player->getParty();

				$eventManager = $partyManager->getEventManager();

				$eventManager->removeFromQueue($party);

				if(($partyEvent = $player->getPartyEvent()) !== null){
					$partyEvent->removeFromEvent($player);
				}

				$party->removePlayer($player);
			}

			if($player->isInEvent()){
				$theEvent = MineceitCore::getEventManager()->getEventFromPlayer($player);
				$theEvent->removePlayer($player, false);
			}

			if($player->isInDuel()){
				$duel = $duelHandler->getDuel($player);
				$opponent = $duel->getOpponent($player);
				if($duel->getQueue() === 'MLGRush'){
					if($duel->isCountingDown()) $duel->EndMLG();
					if($duel->isRunning() && $opponent !== null) $duel->EndMLG();
				}else{
					if($duel->isRunning() && $opponent !== null){
						$duel->setEnded($opponent);
					}elseif($duel->isCountingDown()){
						$duel->setEnded(null, false);
					}
				}
			}

			if($player->isInBot()){
				$duel = $botHandler->getDuel($player);
				$duel->setEnded(false);
			}

			if($player->isInCombat() && $player->isInArena()){
				if($player->hasTarget()){
					$cause = $player->getTarget();
					if($cause instanceof MineceitPlayer && $cause->isOnline() && $cause->isInArena()){
						$cause->getStatsInfo()->addKill();
						$cause->setInCombat(false);
						$cause->setThrowPearl(true, false);
						$cause->setEatGap(true, false);
						$cause->setArrowCD(true, false);
						$cause->getKitHolder()->setKit($cause->getArena()->getKit());
					}
				}
				$player->getStatsInfo()->addDeath();
				$player->setInCombat(false, false);
			}

			$duelHandler->getRequestHandler()->removeAllRequestsWith($player);
			$partyManager->getRequestHandler()->removeAllRequestsWith($player);
			$playerHandler->savePlayerData($player);

			$player->getKitHolder()->saveEditedKits();

			$player->getExtensions()->clearAll();

			$event->setQuitMessage(MineceitUtil::getLeaveMessage($player));

			if($event->getQuitReason() === 'Login timeout'){
				$title = DiscordUtil::boldText("Timeout");
				$description = DiscordUtil::boldText("Region: ") . MineceitCore::getRegion();
				DiscordUtil::sendSmth($title, $description, DiscordUtil::RED);
			}

			$this->core->getScheduler()->scheduleDelayedTask(new DelayScoreboardUpdate(ScoreboardUtil::ONLINE_PLAYERS, $player), 3);
		}
	}

	/**
	 * @param PlayerMoveEvent $event
	 */
	public function onMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		if($player instanceof MineceitPlayer){
			if($player->getSettingsInfo()->isAutoSprintEnabled()){
				// Gets the difference between the two vectors.
				$difference = $event->getTo()->subtract($event->getFrom());
				$distance = $difference->length();
				$lookDirection = $player->getDirectionVector()->normalize();
				$dotProductResult = Math::dot($lookDirection, $difference->normalize());
				// Result of dot product:
				// if result is 1 in this case, player is moving forward
				// if result is 0 in this case, player is moving side to side
				// if result is -1 in this case, player is moving backward
				// TODO: Change 0.5 for dot product result to some threshold for
				//       determining if player is moving forward
				$player->setSprinting($distance > 0 && $dotProductResult >= 0.5);
			}

			if($player->isInArena() && $player->getKitHolder()->hasKit()){
				if($player->getArena()->getName() === 'Knock' && $player->getFloorY() <= 0){
					$player->onDeath();
					$player->getExtensions()->clearAll();
					$player->respawn();
					return;
				}elseif($player->getArena()->getName() === 'Build' && ($player->getFloorY() <= 57 || $player->getFloorY() >= 87)){
					$player->onDeath();
					$player->getExtensions()->clearAll();
					$player->respawn();
					return;
				}elseif($player->getArena()->getName() === 'Sumo' && ($player->getFloorY() <= 50)){
					$player->onDeath();
					$player->getExtensions()->clearAll();
					$player->respawn();
					return;
				}
			}elseif($player->isInHub() && ($player->getLevel()->getBlock(new Vector3($player->x, $player->y - 1.5, $player->z))->getId() == 138 && ($player->getLevel()->getBlock(new Vector3($player->x, $player->y - 0.5, $player->z))->getId() == 241 && $player->getLevel()->getBlock(new Vector3($player->x, $player->y - 0.5, $player->z))->getDamage() == 2))){
				$player->setValidTags(TextFormat::BOLD . TextFormat::AQUA . 'FRIED');
				$itemHandler = MineceitCore::getItemHandler();
				$player->reset(true, true);
				$player->isInParty() ? $itemHandler->spawnPartyItems($player) : $itemHandler->spawnHubItems($player);
			}
		}
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onInteract(PlayerInteractEvent $event) : void{
		$action = $event->getAction();
		$player = $event->getPlayer();
		$item = $event->getItem();

		$itemHandler = MineceitCore::getItemHandler();

		$duelHandler = MineceitCore::getDuelHandler();

		if($player instanceof MineceitPlayer){

			$mineceitItem = $itemHandler->getItem($item);

			if($mineceitItem !== null){
				$localName = $mineceitItem->getLocalName();
				$eventManager = MineceitCore::getEventManager();
				$partyManager = MineceitCore::getPartyManager();

				if($player->isInParty()){

					$party = $player->getParty();

					switch($localName){
						case ItemHandler::HUB_WAIT_QUEUE:
						case ItemHandler::PARTY_LEAVE:
							$form = FormUtil::getLeavePartyForm($player, $party->isOwner($player));
							$player->sendFormWindow($form);
							break;
						case ItemHandler::PARTY_SETTINGS:
							$form = FormUtil::getPartyOptionsForm($party);
							$player->sendFormWindow($form);
							break;
						case ItemHandler::PARTY_GAMES:
							$form = FormUtil::getPartyGamesForm($player);
							$player->sendFormWindow($form);
							break;
						case ItemHandler::PARTY_DUEL:
							$form = FormUtil::getPartyDuelsForm($player);
							$player->sendFormWindow($form);
							break;
						case ItemHandler::PARTY_EVENT:
							$player->sendMessage(MineceitUtil::getPrefix() . TextFormat::RED . ' Coming soon...');
							break;
						case ItemHandler::HUB_LEAVE_QUEUE:
							$partyManager->getEventManager()->removeFromQueue($party);
							break;
					}
				}elseif($player->isInEvent()){
					$theEvent = $eventManager->getEventFromPlayer($player);
					switch($localName){
						case ItemHandler::SPEC_LEAVE:
							$theEvent->removePlayer($player);
							break;
						case ItemHandler::HUB_PLAYER_SETTINGS:
							$form = FormUtil::getSettingsMenu($player);
							$player->sendFormWindow($form);
							break;
					}
				}elseif($player->isInHub()){
					switch($localName){
						case ItemHandler::HUB_PLAY_BOT:
							$form = FormUtil::getBotForm($player);
							$player->sendFormWindow($form);
							break;
						case ItemHandler::HUB_SHOP_ITEM:
							$form = FormUtil::getShopForm($player);
							$player->sendFormWindow($form);
							break;
						case ItemHandler::HUB_PLAY_FFA:
							$form = FormUtil::getFFAForm($player);
							$player->sendFormWindow($form);
							break;
						case ItemHandler::HUB_PLAY_DUEL:
							$form = FormUtil::getDuelsForm($player);
							$player->sendFormWindow($form);
							break;
						case ItemHandler::HUB_PLAY_UNRANKED_DUELS:
							$form = FormUtil::getDuelForm($player, $player->getLanguageInfo()
								->getLanguage()->formWindow(Language::DUELS_UNRANKED_FORM_TITLE), false);
							$player->sendFormWindow($form, ['ranked' => false]);
							break;
						case ItemHandler::HUB_PLAY_RANKED_DUELS:
							$form = FormUtil::getDuelForm($player, $player->getLanguageInfo()
								->getLanguage()->formWindow(Language::DUELS_RANKED_FORM_TITLE), true);
							$player->sendFormWindow($form, ['ranked' => true]);
							break;
						case ItemHandler::HUB_PLAY_EVENT:
							$form = FormUtil::getEventsForm($player);
							$player->sendFormWindow($form);
							break;
						case ItemHandler::HUB_PLAYER_SETTINGS:
							$form = FormUtil::getSettingsMenu($player);
							$player->sendFormWindow($form);
							break;
						case ItemHandler::HUB_LEAVE_QUEUE:
							$duelHandler->removeFromQueue($player);
							break;
						case ItemHandler::HUB_DUEL_HISTORY:
							$form = FormUtil::getDuelHistoryForm($player);
							$player->sendFormWindow($form);
							break;
						case ItemHandler::HUB_REQUEST_INBOX:
							$requestHandler = MineceitCore::getDuelHandler()->getRequestHandler();
							$requests = $requestHandler->getRequestsOf($player);
							$form = FormUtil::getDuelInbox($player, $requests);
							$player->sendFormWindow($form, ['requests' => $requests]);
							break;
						case ItemHandler::HUB_BATTLE_ITEM:
							$form = FormUtil::getBattlePassForm($player);
							$player->sendFormWindow($form);
							break;
						case ItemHandler::HUB_PARTY_ITEM:
							$form = FormUtil::getDefaultPartyForm($player);
							$party = $partyManager->getPartyFromPlayer($player);
							$option = $party !== null ? 'leave' : 'join';
							$player->sendFormWindow($form, ['party-option' => $option]);
							break;
					}
				}elseif($player->isADuelSpec()){
					$duel = $duelHandler->getDuelFromSpec($player);
					switch($localName){
						case ItemHandler::SPEC_LEAVE:
							$duel->removeSpectator($player);
							$player->getScoreboardInfo()->setScoreboard(
								Scoreboard::SCOREBOARD_SPAWN
							);
							break;
					}
				}elseif($player->isWatchingReplay()){
					$replayManager = MineceitCore::getReplayManager();
					$replay = $replayManager->getReplayFrom($player);
					switch($localName){
						case ItemHandler::SPEC_LEAVE:
							$replay->endReplay();
							break;
						case ItemHandler::PAUSE_REPLAY:
							$replay->setPaused(true);
							break;
						case ItemHandler::PLAY_REPLAY:
							$replay->setPaused(false);
							break;
						case ItemHandler::REWIND_REPLAY:
							$replay->rewind();
							break;
						case ItemHandler::FAST_FORWARD_REPLAY:
							$replay->fastForward();
							break;
						case ItemHandler::SETTINGS_REPLAY:
							$form = FormUtil::getReplaySettingsForm($player, $replay);
							$player->sendFormWindow($form, ['replay' => $replay]);
							break;
					}
				}elseif($player->isInBot()){
					$botHandler = MineceitCore::getBotHandler();
					$duel = $botHandler->getDuel($player);
					$bot = $duel->getBot();
					switch($localName){
						case ItemHandler::BOT_LEAVE:
							$duel->setEnded(true);
							break;
						case ItemHandler::BOT_PAUSE:
							if($bot !== null && $bot->isAlive() && $duel->getBName() === 'ClutchBot'){
								$bot->setCanMove(false);
								$itemHandler->givePlayBotItem($player);
							}
							break;
						case ItemHandler::BOT_PLAY:
							if($bot !== null && $bot->isAlive() && $duel->getBName() === 'ClutchBot'){
								$bot->setCanMove(true);
								$itemHandler->givePauseBotItem($player);
							}
							break;
						case ItemHandler::BOT_START:
							if($bot !== null && $bot->isAlive() && $duel->getBName() === 'ClutchBot') $duel->clutchStart();
							break;
						case ItemHandler::BOT_SETTING:
							if($bot !== null && $bot->isAlive() && $duel->getBName() === 'ClutchBot'){
								$form = FormUtil::getClutchBotForm($player, $bot);
								$player->sendFormWindow($form, ['bot' => $bot]);
							}
							break;
					}
				}
				$event->setCancelled();
			}elseif(!$player->isInHub()){
				$id = $item->getId();

				if($id === Item::FISHING_ROD){
					$use = false;

					if($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK || $action === PlayerInteractEvent::RIGHT_CLICK_AIR){
						if($player->getClientInfo()->getDeviceOS() === MineceitPlayer::WINDOWS_10)
							$use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK;
						else $use = true;
					}

					if($use)
						$player->useRod();

					$event->setCancelled();
				}elseif($id === Item::ENDER_PEARL){
					$use = false;

					if($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK || $action === PlayerInteractEvent::RIGHT_CLICK_AIR){
						if($player->getClientInfo()->getDeviceOS() === MineceitPlayer::WINDOWS_10)
							$use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK;
						else $use = true;
					}

					if($use && $player->canThrowPearl())
						$player->throwPearl($item);

					$event->setCancelled();
				}elseif($id === Item::SPLASH_POTION){
					$use = false;

					if($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK || $action === PlayerInteractEvent::RIGHT_CLICK_AIR){
						if($player->getClientInfo()->getDeviceOS() === MineceitPlayer::WINDOWS_10)
							$use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK;
						else $use = true;
					}

					if($use)
						$player->throwPotion($item);

					$event->setCancelled();
				}elseif($id === Item::MUSHROOM_STEW){
					$use = false;

					if($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK || $action === PlayerInteractEvent::RIGHT_CLICK_AIR){
						if($player->getClientInfo()->getDeviceOS() === MineceitPlayer::WINDOWS_10)
							$use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK;
						else $use = true;
					}

					if($use){
						$player->setHealth($player->getHealth() + 8);
						$player->getInventory()->setItemInHand(Item::get(Item::AIR));
					}

					$event->setCancelled();
				}elseif($id === Item::MOB_HEAD && $item->getDamage() === 4){
					$use = false;

					if($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK || $action === PlayerInteractEvent::RIGHT_CLICK_AIR){
						if($player->getClientInfo()->getDeviceOS() === MineceitPlayer::WINDOWS_10)
							$use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK;
						else $use = true;
					}

					if($use && $player->canEatGap()){
						$player->setEatGap(false);
						$player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 150, 0, false));
						$player->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 100, 2, false));
						$inv = $player->getInventory();
						$count = $item->getCount() - 1;
						if($count === 0) $inv->setItem($inv->getHeldItemIndex(), Item::get(0));
						else $inv->setItem($inv->getHeldItemIndex(), Item::get($item->getId(), $item->getDamage(), $count));
					}

					$event->setCancelled();
				}elseif(($item instanceof Armor || $id === Item::ELYTRA) && $event->getBlock()->getId() !== Block::ITEM_FRAME_BLOCK){
					$use = false;

					if($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK || $action === PlayerInteractEvent::RIGHT_CLICK_AIR){
						if($player->getClientInfo()->getDeviceOS() === MineceitPlayer::WINDOWS_10)
							$use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK;
						else $use = true;
					}

					if($use)
						MineceitUtil::setArmorByType($event->getItem(), $player);

					$event->setCancelled();
				}elseif($player->isInDuel() && $id === Item::BUCKET && $item->getDamage() === 0){
					$blockClicked = $event->getBlock();
					$duel = $duelHandler->getDuel($player);
					if($blockClicked !== null && ($blockClicked instanceof Liquid))
						$duel->setBlockAt($blockClicked, true);
				}
			}elseif($player->isInHub() && $player->getKitHolder()->isEditingKit()){
				$event->setCancelled();
			}elseif($player->isInHub() && $action === PlayerInteractEvent::RIGHT_CLICK_BLOCK && !$player->canBuild() && in_array($event->getBlock()->getId(), [Block::TRAPDOOR, Block::IRON_TRAPDOOR, Block::FENCE_GATE, Block::SPRUCE_FENCE_GATE, Block::BIRCH_FENCE_GATE, Block::JUNGLE_FENCE_GATE, Block::DARK_OAK_FENCE_GATE, Block::ACACIA_FENCE_GATE])){
				$event->setCancelled();
			}
		}
	}

	/**
	 * @param PlayerExhaustEvent $event
	 */
	public function onExhaust(PlayerExhaustEvent $event) : void{
		$event->setCancelled();
		$player = $event->getPlayer();
		if($player instanceof MineceitPlayer){
			$player->setFood($player->getMaxFood());
			$player->setSaturation($player->getExtensions()->getMaxSaturation());
		}
	}

	/**
	 * @param PlayerItemConsumeEvent $event
	 */
	public function onConsume(PlayerItemConsumeEvent $event) : void{
		$player = $event->getPlayer();
		if($player instanceof MineceitPlayer){
			$item = $event->getItem();
			$id = $event->getItem()->getId();

			if($player->getKitHolder()->isEditingKit()){
				$event->setCancelled();
			}elseif($id === Item::POTION){
				$event->setCancelled();
				$effects = $item->getAdditionalEffects();
				foreach($effects as $effect){
					if($player->isInArena()){
						$effect->setDuration(120000);
						$effect->setVisible(false);
					}
					$player->addEffect($effect);
				}
				$player->getInventory()->setItemInHand(Item::get(Item::AIR));
			}elseif($id === Item::GOLDEN_APPLE || $id === Item::ENCHANTED_GOLDEN_APPLE){
				if($player->canEatGap()){
					$player->eatGap();
				}else{
					$event->setCancelled();
				}
			}
		}
	}

	/**
	 * @priority LOWEST
	 *
	 * @param EntityDamageEvent $event
	 */
	public function onEntityDamaged(EntityDamageEvent $event) : void{
		$player = $event->getEntity();
		$cause = $event->getCause();

		if($player instanceof MineceitPlayer){
			if(
				$event->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) < 0.0
				|| $cause === EntityDamageEvent::CAUSE_FALL
				|| $cause === EntityDamageEvent::CAUSE_VOID
				|| $player->isInHub()
				|| $player->isImmobile()
				|| $player->isFrozen()
				|| $player->getExtensions()->isSpectator()
				|| ($player->isInEvent() && !$player->isInEventDuel() && !$player->isInEventBoss())
				|| ($player->isInArena() && $player->getArena()->getName() === 'Knock' && $player->getArena()->isWithinProtection($player))
				|| (($partyEvent = $player->getPartyEvent()) instanceof PartyGames && $partyEvent->getKit() === 'Knock' && $partyEvent->isWithinProtection($player))
			){
				$event->setCancelled();
				if($cause === EntityDamageEvent::CAUSE_VOID){
					$level = $player->getLevel();
					if($level === null) $level = $this->server->getDefaultLevel();
					$player->teleport($level->getSpawnLocation());
				}
				return;
			}

			if($event->isCancelled()) return;

			if($event instanceof EntityDamageByEntityEvent){
				$damager = $event->getDamager();
				if($damager instanceof MineceitPlayer){
					if(
						$player->getName() === $damager->getName()
						|| $player->getExtensions()->isSpectator()
						|| $damager->getExtensions()->isSpectator()
					){
						$event->setCancelled();
						return;
					}

					if(
						!$damager->isSprinting()
						&& !$damager->isFlying()
						&& $damager->fallDistance > 0
						&& !$damager->hasEffect(Effect::BLINDNESS)
						&& !$damager->isUnderwater()
						&& ($event->getFinalDamage() / 2) < $player->getHealth()
					){
						$player->setHealth($player->getHealth() + ($event->getFinalDamage() / 2));
					}

					if($player->isInArena() && $damager->isInArena()){
						$arena = $player->getArena();
						if($arena->canInterrupt()){
							if($player->hasTarget() === false) $player->setCombatNameTag();
							$player->setTarget($damager->getName());
							if($damager->hasTarget() === false) $damager->setCombatNameTag();
							$damager->setTarget($player->getName());
						}else{
							if($player->hasTarget() === false && $damager->hasTarget() === false){
								$player->setCombatNameTag();
								$player->setTarget($damager->getName());
								$damager->setCombatNameTag();
								$damager->setTarget($player->getName());
							}elseif($player->hasTarget() === true && $damager->hasTarget() === false){
								$event->setCancelled();
								$damager->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $damager->getLanguageInfo()->getLanguage()->generalMessage(Language::DONT_INTERRUPT));
								return;
							}elseif($damager->hasTarget() && $damager->getTarget()->getName() !== $player->getName()){
								$event->setCancelled();
								$damager->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $damager->getLanguageInfo()->getLanguage()->generalMessage(Language::INCORRECT_TARGET) . ' ' . $damager->getTarget()->getDisplayName());
								return;
							}
						}
						$damager->setInCombat(true);
						$player->setInCombat(true);
					}elseif(($duel = MineceitCore::getDuelHandler()->getDuel($player)) !== null){
						$duel->addHitTo($damager, $event->getFinalDamage());
					}elseif($player->isInEventBoss() && !MineceitCore::getEventManager()->getEventFromPlayer($player)->getCurrentBoss()->isBoss($player) && !MineceitCore::getEventManager()->getEventFromPlayer($player)->getCurrentBoss()->isBoss($damager)){
						$event->setCancelled();
					}elseif($player->isInParty() && $damager->isInParty()){
						$partyEvent = $player->getPartyEvent();
						if($partyEvent instanceof PartyDuel || $partyEvent instanceof PartyGames){
							$team = $partyEvent->getTeam($player);
							if($team instanceof MineceitTeam && $team->isInTeam($damager)){
								$event->setCancelled();
							}
						}
					}

					if($event instanceof EntityDamageByChildEntityEvent){
						$sound = true;
						if($event->getChild() instanceof Arrow){
							if($damager->isInArena() && $player->isInArena()){
								$arena = $player->getArena();
								if($arena->getName() === 'OITC'){
									$event->setCancelled();
									$player->onDeath();
									$player->getExtensions()->clearAll();
									$player->respawn();
								}
							}elseif(($partyEvent = $player->getPartyEvent()) instanceof PartyGames){
								if($partyEvent->getKit() === 'OITC'){
									$team = $partyEvent->getTeam($player);
									if($team instanceof MineceitTeam){
										if($team->isInTeam($damager)){
											$sound = false;
											$event->setCancelled();
										}else{
											$event->setCancelled();
											$player->onDeath();
											$player->getExtensions()->clearAll();
											$player->respawn();
										}
									}else{
										$event->setCancelled();
										$player->onDeath();
										$player->getExtensions()->clearAll();
										$player->respawn();
									}
								}
							}
							if($sound){
								MineceitUtil::sendArrowDingSound($player);
							}
						}
					}
				}
			}
		}elseif($player instanceof ReplayHuman){
			$event->setCancelled();
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 */
	public function onDeath(EntityDamageEvent $event) : void{
		if($event->isCancelled()){
			return;
		}
		$player = $event->getEntity();

		if($player instanceof MineceitPlayer && $player->getHealth() - $event->getFinalDamage() <= 0){
			$event->setCancelled();
			$player->getExtensions()->clearAll();
			$player->onDeath();
			if($player->isInEventDuel() || $player->isInEventBoss() || $player->isInParty()){
				return;
			}
			$player->respawn();
		}
	}

	/**
	 * @param EntityDeathEvent $event
	 */
	public function BotDeath(EntityDeathEvent $event){
		$entity = $event->getEntity();
		if($entity instanceof AbstractCombatBot){
			$event->setDrops([]);
			$cause = $event->getEntity()->getLastDamageCause();
			if($cause !== null && $cause instanceof EntityDamageByEntityEvent){
				$damager = $cause->getDamager();
				if($damager instanceof MineceitPlayer && ($duel = MineceitCore::getBotHandler()->getDuel($damager)) !== null){
					$duel->setEnded(true);
				}
			}
		}
	}

	/**
	 * @param BlockPlaceEvent $event
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$cancel = true;

		if($player instanceof MineceitPlayer){
			$cancel = $player->getExtensions()->isSpectator() || $player->isFrozen();
			if($cancel){
				$event->setCancelled();
				return;
			}

			$cancel = true;
			if(!$player->canBuild()){
				if(($duel = MineceitCore::getDuelHandler()->getDuel($player)) !== null){
					$cancel = !$duel->canPlaceBlock($player, $block);
					if(!$cancel)
						$duel->setBlockAt($block);
				}elseif($player->isInArena() && $player->getKitHolder()->hasKit() && $player->getArena()->getName() === 'Build'){
					$cancel = false;
					MineceitCore::getDeleteBlocksHandler()->setBlockBuild($block);
				}elseif(($duel = MineceitCore::getBotHandler()->getDuel($player)) !== null){
					$cancel = $duel->canPlaceBlock($player, $block);
				}elseif(($party = $player->getPartyEvent()) !== null){
					if($party instanceof PartyDuel){
						$cancel = !$party->canPlaceBlock($player, $block);
					}elseif($party instanceof PartyGames && $party->getKit() === 'Build'){
						$cancel = false;
						MineceitCore::getDeleteBlocksHandler()->setBlockBuild($block);
					}
				}
			}else{
				$cancel = false;
			}
		}

		if($cancel) $event->setCancelled();
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$cancel = true;

		if($player instanceof MineceitPlayer){
			$cancel = $player->getExtensions()->isSpectator() || $player->isFrozen();
			if($cancel){
				$event->setCancelled();
				return;
			}

			$cancel = true;
			if(!$player->canBuild()){
				if(($duel = MineceitCore::getDuelHandler()->getDuel($player)) !== null){
					$cancel = !$duel->canPlaceBlock($player, $block, true);
					if(!$cancel)
						$duel->setBlockAt($block, true);
				}elseif($player->isInArena() && $player->getKitHolder()->hasKit() && $player->getArena()->getName() === 'Build' && ($block->getId() === 24 || $block->getId() === 30)){
					$cancel = false;
					$event->setDrops([]);
					MineceitCore::getDeleteBlocksHandler()->setBlockBuild($block, true);
				}elseif(($party = $player->getPartyEvent()) !== null){
					if($party instanceof PartyDuel){
						$cancel = !$party->canPlaceBlock($player, $block, true);
					}elseif($party instanceof PartyGames && $party->getKit() === 'Build' && ($block->getId() === 24 || $block->getId() === 30)){
						$cancel = false;
						$event->setDrops([]);
						MineceitCore::getDeleteBlocksHandler()->setBlockBuild($block, true);
					}
				}
			}else{
				$cancel = false;
			}
		}

		if($cancel) $event->setCancelled();
	}

	public function onBucketEmpty(PlayerBucketEmptyEvent $event) : void{
		$player = $event->getPlayer();
		$cancel = true;

		if($player instanceof MineceitPlayer){

			if(($duel = MineceitCore::getDuelHandler()->getDuel($player)) !== null){
				$cancel = !$duel->isRunning();
				if(!$cancel){
					$blockClicked = $event->getBlockClicked();
					$block = new Block($event->getBucket()->getDamage());
					$block->x = $blockClicked->x;
					$block->y = $blockClicked->y;
					$block->z = $blockClicked->z;
					$duel->setBlockAt($block);
				}
			}elseif(($party = $player->getPartyEvent()) instanceof PartyDuel){
				$cancel = !$party->isRunning();
			}elseif($player->canBuild()){
				$cancel = $player->getExtensions()->isSpectator() || $player->isFrozen() || $player->isInArena() || $player->isInEvent();
			}
		}

		if($cancel) $event->setCancelled();
	}

	/**
	 * @param PlayerBucketFillEvent $event
	 */
	public function onBucketFill(PlayerBucketFillEvent $event) : void{
		$player = $event->getPlayer();
		$cancel = true;
		if($player instanceof MineceitPlayer){
			if(($duel = MineceitCore::getDuelHandler()->getDuel($player)) !== null){
				$cancel = !$duel->isRunning();
			}elseif(($party = $player->getPartyEvent()) instanceof PartyDuel){
				$cancel = !$party->isRunning();
			}elseif($player->canBuild()){
				$cancel = $player->getExtensions()->isSpectator() || $player->isFrozen() || $player->isInArena() || $player->isInEvent();
			}
		}

		if($cancel) $event->setCancelled();
	}

	/**
	 * @param BlockSpreadEvent $event
	 */
	public function onSpread(BlockSpreadEvent $event) : void{
		$block = $event->getNewState();
		$pos = $event->getBlock();
		$level = $pos->getLevel();

		$duelHandler = MineceitCore::getDuelHandler();
		$replayHandler = MineceitCore::getReplayManager();

		$duel = $level !== null ? $duelHandler->getDuelFromLevel($level->getName()) : null;
		if($duel !== null && $block instanceof Liquid){
			$newblock = new Block($block->getId(), $block->getDamage());
			$newblock->x = $block->x;
			$newblock->y = $block->y;
			$newblock->z = $block->z;
			$duel->setBlockAt($newblock);
		}elseif(($replay = $replayHandler->getReplayFromLevel($level)) !== null){
			$cancel = $replay->isPaused();
			if($cancel) $event->setCancelled();
		}
	}

	/**
	 * @param BlockFormEvent $event
	 */
	public function onBlockForm(BlockFormEvent $event) : void{
		$block = $event->getBlock();
		$newState = $event->getNewState();

		$duelHandler = MineceitCore::getDuelHandler();

		$level = $block->getLevel();
		if($level !== null && ($duel = $duelHandler->getDuelFromLevel($level->getName())) !== null){
			$newblock = new Block($newState->getId(), $newState->getDamage());
			$newblock->x = $block->x;
			$newblock->y = $block->y;
			$newblock->z = $block->z;
			$duel->setBlockAt($newblock);
		}
	}

	/**
	 * @param EntityTeleportEvent $event
	 */
	public function onTeleport(EntityTeleportEvent $event) : void{
		$player = $event->getEntity();

		if($player instanceof MineceitPlayer){

			// if($player->isFollowed()){
			//     if($event->getFrom()->getLevel()->getName() !== $event->getTo()->getLevel()->getName()){
			//         $follows = $player->getFollower();
			//         foreach($follows as $follow){
			//             $follow->teleport($event->getTo());
			//         }
			//     }
			// }

			if(($duel = MineceitCore::getDuelHandler()->getDuel($player)) !== null){
				$pos = $event->getTo();
				$duel->setTeleportAt($player, new Vector3($pos->x, $pos->y, $pos->z));
			}
		}
	}

	/**
	 * @param DataPacketSendEvent $event
	 */
	public function onDisconnectPacket(DataPacketSendEvent $event){
		$pkt = $event->getPacket();
		if($pkt instanceof DisconnectPacket){
			if($pkt->message === "Internal server error"){
				$pkt->message = TextFormat::RED . "You have encountered a bug.\n" . TextFormat::WHITE . "Contact us: " . TextFormat::LIGHT_PURPLE . "discord.gg/zeqa";
			}elseif($pkt->message === "Server is white-listed"){
				$pkt->message = TextFormat::YELLOW . "Server Restarting...";
			}
		}
	}

	/**
	 * @param ProjectileHitEvent $event
	 */
	public function onArrowHit(ProjectileHitEvent $event) : void{
		$entity = $event->getEntity();
		if($event instanceof ProjectileHitBlockEvent && $entity instanceof Arrow){
			if(
				$entity->getLevel()->getName() === 'Knock'
				|| $entity->getLevel()->getName() === 'OITC'
				|| (strpos($entity->getLevel()->getName(), 'duel') !== false)
				|| (strpos($entity->getLevel()->getName(), 'party') !== false)
			){
				$entity->kill();
			}
		}
	}

	/**
	 * @param PlayerAnimationEvent $event
	 */
	public function onAnimated(PlayerAnimationEvent $event) : void{
		$player = $event->getPlayer();

		if($player instanceof MineceitPlayer && ($duel = MineceitCore::getDuelHandler()->getDuel($player)) !== null){
			$duel->setAnimationFor($player->getName(), $event->getAnimationType());
		}
	}

	/**
	 * @param PlayerChatEvent $event
	 */
	public function onChat(PlayerChatEvent $event) : void{
		$player = $event->getPlayer();
		if($player instanceof MineceitPlayer){

			$format = MineceitCore::getRankHandler()->formatRanksForChat($player);

			if(!$player->canChat()){
				$event->setCancelled(true);
				return;
			}
			$player->setInSpam();
			MineceitUtil::broadcastTranslatedMessage($player, $format . ' ' . TextFormat::RESET, $event->getMessage(), $this->server->getOnlinePlayers());
			$event->setCancelled(true);
		}
	}

	/**
	 * @param PlayerCommandPreprocessEvent $event
	 */
	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event) : void{
		$player = $event->getPlayer();
		$message = $event->getMessage();
		$firstChar = $message[0];

		if($player instanceof MineceitPlayer && $player->getKitHolder()->isEditingKit() && $player->isInHub()){
			$language = $player->getLanguageInfo()->getLanguage();
			if(strtolower($message) === "confirm"){
				$event->setCancelled();
				$player->getKitHolder()->setFinishedEditingKit(false);
				MineceitCore::getItemHandler()->spawnHubItems($player, true);
				$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $language->generalMessage(Language::KIT_EDIT_SUCCESS_MODE));
				return;
			}else{
				$event->setCancelled();
				$player->sendMessage("\n\n" . $language->generalMessage(Language::KIT_EDIT_MODE));
				$player->sendMessage("\n\n");
				return;
			}
		}

		if($firstChar === '/'){
			$cancel = false;
			$split = explode(' ', $message);

			if($split[0] === "/"){
				return;
			}
			$commandName = str_replace('/', '', $split[0]);
			$command = $this->server->getCommandMap()->getCommand($commandName);
			$tellCommands = ['tell' => true, 'msg' => true, 'w' => true];

			if(!($command instanceof MineceitCommand) && $player instanceof MineceitPlayer){
				$cancel = $player->isInDuel() || $player->isInCombat() || $player->isADuelSpec() || $player->isInEventDuel() || $player->isInEventBoss();
			}

			if($cancel && $commandName !== 'me' && !isset($tellCommands[$commandName])){
				$event->setCancelled();
				$msg = null;
				$language = $player->getLanguageInfo()->getLanguage();

				if($player->isInDuel()){
					$msg = $language->generalMessage(Language::COMMAND_FAIL_IN_DUEL);
				}elseif($player->isInCombat()){
					$msg = $language->generalMessage(Language::COMMAND_FAIL_IN_COMBAT);
				}

				if($msg !== null) $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
				return;
			}

			if($commandName === 'me' || isset($tellCommands[$commandName])){
				if(!$player->canChat(true)){
					$event->setCancelled();
					return;
				}

				$player->setInTellSpam();
			}
		}elseif($firstChar === '.'){
			if($player instanceof MineceitPlayer && ($player->hasHelperPermissions() || $player->hasBuilderPermissions())){
				$event->setCancelled();
				$message = substr($message, 1);
				$format = MineceitCore::getRankHandler()->formatStaffForChat($player);
				MineceitUtil::broadcastTranslatedMessage($player, $format . ' ' . TextFormat::RESET, $message, MineceitCore::getPlayerHandler()->getStaffOnline(false));
			}
		}
	}

	/**
	 * @param PlayerDropItemEvent $event
	 */
	public function onItemDroped(PlayerDropItemEvent $event) : void{
		$player = $event->getPlayer();
		if($player instanceof MineceitPlayer){
			if(
				$player->isInHub()
				|| $player->getKitHolder()->isEditingKit()
				|| $player->isInArena()
				|| $player->isInBot()
				|| $player->isInEvent()
				|| $player->isInEventDuel()
				|| $player->isInEventBoss()
			){
				$event->setCancelled();
			}
		}
	}

	/**
	 * @param InventoryTransactionEvent $event
	 */
	public function onItemMoved(InventoryTransactionEvent $event) : void{
		$transaction = $event->getTransaction();
		$player = $transaction->getSource();
		$actions = $transaction->getActions();
		if($player instanceof MineceitPlayer){
			if($transaction instanceof CraftingTransaction){
				$event->setCancelled();
				return;
			}
			if($player->isInHub() || $player->getExtensions()->isSpectator() || $player->isFrozen() || $player->isWatchingReplay()){
				$testInv = false;
				$permToPlaceNBreak = $player->hasBuilderPermissions();
				if($player->getKitHolder()->isEditingKit() && $player->isInHub())
					$permToPlaceNBreak = true;

				if($permToPlaceNBreak){
					$testInv = $player->canBuild();
				}else{
					$event->setCancelled();
				}

				if($player->getKitHolder()->isEditingKit() && $player->isInHub())
					$testInv = true;

				if($testInv && !$event->isCancelled()){
					foreach($actions as $action){
						if($action instanceof SlotChangeAction){
							$inventory = $action->getInventory();
							if($inventory instanceof MineceitBaseInv){
								$menu = $inventory->getMenu();
								$menu->onItemMoved($player, $action);
								if(!$menu->canEdit()){
									$event->setCancelled();
									return;
								}
							}elseif($inventory instanceof ArmorInventory){
								if($player->getKitHolder()->isEditingKit() && $player->isInHub()){
									$event->setCancelled();
								}
							}elseif($inventory instanceof CraftingGrid){
								$event->setCancelled();
							}
						}
					}
				}else{
					$event->setCancelled(!$testInv);
				}
			}elseif(($player->isInEventDuel() || $player->isInEventBoss()) && !$event->isCancelled()){
				foreach($actions as $action){
					if($action instanceof SlotChangeAction){
						$inventory = $action->getInventory();
						if($inventory instanceof ArmorInventory || $inventory instanceof CraftingGrid){
							$event->setCancelled();
						}
					}
				}
			}
		}
	}

	/**
	 * @param EntityShootBowEvent $event
	 */
	public function onShootBow(EntityShootBowEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof MineceitPlayer){

			$cancel = $player->isInHub() || $player->getExtensions()->isSpectator() || ($player->isInEvent() && !$player->isInEventDuel() && !$player->isInEventBoss());

			if($cancel){
				$event->setCancelled();
			}

			if(($duel = MineceitCore::getDuelHandler()->getDuel($player)) !== null){
				if($duel->isCountingDown()){
					$event->setCancelled();
					return;
				}
				$duel->setReleaseBow($player, $event->getForce());
			}

			if(!$event->isCancelled()){
				if($player->isInArena() && $player->getKitHolder()->hasKit() && $player->getArena()->getName() === 'OITC') $player->setArrowCD(false);
				elseif(($party = $player->getPartyEvent()) instanceof PartyGames && $party->getKit() === 'OITC') $player->setArrowCD(false);
			}
		}
	}

	/**
	 * @param PluginDisableEvent $event
	 */
	public function onPluginDisable(PluginDisableEvent $event) : void{
		$plugin = $event->getPlugin();
		if($plugin instanceof MineceitCore){

			$playerHandler = MineceitCore::getPlayerHandler();

			$playerHandler->getIPManager()->save();
			$playerHandler->getAliasManager()->save();

			$players = $plugin->getServer()->getOnlinePlayers();

			foreach($players as $player){
				if($player instanceof MineceitPlayer){
					$playerHandler->savePlayerData($player);
					$player->transfer("zeqa.net", 19132);
				}
			}

			$auctionHouse = MineceitCore::getAuctionHouse();
			$auctionHouse->saveAuctionHouse();

			$guildManager = MineceitCore::getGuildManager();
			$guildManager->saveGuildData();
		}
	}
}
