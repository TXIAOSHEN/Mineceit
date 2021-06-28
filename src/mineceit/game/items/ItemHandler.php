<?php

declare(strict_types=1);

namespace mineceit\game\items;

use mineceit\MineceitCore;
use mineceit\parties\MineceitParty;
use mineceit\player\MineceitPlayer;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;

class ItemHandler{

	public const HUB_PLAY_FFA = 'hub.ffa';
	public const HUB_PLAY_DUEL = 'hub.duel';
	public const HUB_PLAY_UNRANKED_DUELS = 'hub.duels.unranked';
	public const HUB_PLAY_RANKED_DUELS = 'hub.duels.ranked';
	public const HUB_PLAY_BOT = 'hub.bot';
	public const HUB_PLAY_EVENT = 'hub.event';
	public const HUB_PLAYER_SETTINGS = 'hub.settings';
	public const HUB_WAIT_QUEUE = 'hub.wait-queue';
	public const HUB_LEAVE_QUEUE = 'hub.leave-queue';
	public const HUB_DUEL_HISTORY = 'hub.duels.history';
	public const HUB_REQUEST_INBOX = 'hub.request.inbox';
	public const HUB_PARTY_ITEM = 'hub.party';
	public const HUB_REPORTS_ITEM = 'hub.report';
	public const HUB_SHOP_ITEM = 'hub.shop';
	public const HUB_BATTLE_ITEM = 'hub.battle';

	public const HUB_PLAY = 'hub.play';

	public const PARTY_SETTINGS = 'party.settings';
	public const PARTY_LEAVE = 'party.leave';
	public const PARTY_GAMES = 'party.games';
	public const PARTY_DUEL = 'party.duel';
	public const PARTY_EVENT = 'party.event';

	public const PLAY_REPLAY = 'replay.play';
	public const PAUSE_REPLAY = 'replay.pause';
	public const REWIND_REPLAY = 'replay.rewind';
	public const FAST_FORWARD_REPLAY = 'replay.fast_forward';
	public const SETTINGS_REPLAY = 'replay.settings';

	public const SPEC_LEAVE = 'spec.leave';

	public const BOT_START = 'bot.start';
	public const BOT_PLAY = 'bot.play';
	public const BOT_PAUSE = 'bot.pause';
	public const BOT_LEAVE = 'bot.leave';
	public const BOT_SETTING = 'bot.setting';

	/* @var MineceitItem[]|array */
	private $items;

	/** string[]|array */
	private $hubKeys;

	/** @var string[]|array */
	private $partyKeys;

	/** @var string[]|array */
	private $replayKeys;

	/** @var string[]|array */
	private $botKeys;

	public function __construct(){
		$this->items = [];
		$this->hubKeys = [];
		$this->partyKeys = [];
		$this->replayKeys = [];
		$this->botKeys = [];
		$this->initItems();
	}

	/**
	 * Initializes the items.
	 */
	private function initItems() : void{
		$this->initItemsWithActions();
		$this->registerNewItems();
	}

	/**
	 * Initializes the items with actions.
	 */
	private function initItemsWithActions() : void{

		//$this->registerItem(0, self::HUB_PLAY, Item::get(Item::NETHER_STAR, 0, 1));
		$this->registerItem(0, self::HUB_PLAY_FFA, Item::get(Item::DIAMOND_SWORD));
		$this->registerItem(1, self::HUB_PLAY_DUEL, Item::get(Item::IRON_SWORD));
		$this->registerItem(2, self::HUB_PLAY_BOT, Item::get(Item::GOLDEN_SWORD));
		$this->registerItem(3, self::HUB_PLAY_EVENT, Item::get(Item::NETHER_STAR));
		if(MineceitCore::PARTIES_ENABLED){
			$this->registerItem(4, self::HUB_PARTY_ITEM, Item::get(Item::BOOK));
		}else{
			$this->registerItem(4, self::HUB_DUEL_HISTORY, Item::get(Item::BOOK));
		}
		$this->registerItem(5, self::HUB_BATTLE_ITEM, Item::get(Item::PAPER));
		$this->registerItem(6, self::HUB_SHOP_ITEM, Item::get(Item::MINECART));

		$this->registerItem(7, self::HUB_PLAYER_SETTINGS, Item::get(Item::COMPASS));


		/* $this->registerItem(0, self::HUB_PLAY_FFA, Item::get(Item::NETHER_STAR, 0, 1));
		$this->registerItem(1, self::HUB_PLAY_UNRANKED_DUELS, Item::get(Item::NETHER_STAR, 0, 1));
		$this->registerItem(2, self::HUB_PLAY_RANKED_DUELS, Item::get(Item::NETHER_STAR, 0, 1)); */


		$this->registerItem(8, self::HUB_WAIT_QUEUE, Item::get(Item::REDSTONE_DUST));
		$this->registerItem(8, self::HUB_LEAVE_QUEUE, Item::get(Item::REDSTONE_DUST));
		$this->registerItem(8, self::SPEC_LEAVE, Item::get(Item::DYE, 1));

		$this->registerItem(0, self::PARTY_GAMES, Item::get(Item::DIAMOND_SWORD));
		$this->registerItem(1, self::PARTY_DUEL, Item::get(Item::IRON_SWORD));
		$this->registerItem(2, self::PARTY_EVENT, Item::get(Item::NETHER_STAR));
		$this->registerItem(7, self::PARTY_SETTINGS, Item::get(Item::REPEATER));
		$this->registerItem(8, self::PARTY_LEAVE, Item::get(Item::REDSTONE_DUST));
		$this->registerItem(0, self::PLAY_REPLAY, Item::get(Item::DYE, 10));
		$this->registerItem(0, self::PAUSE_REPLAY, Item::get(Item::DYE, 8));
		$this->registerItem(3, self::REWIND_REPLAY, Item::get(Item::CLOCK));
		$this->registerItem(4, self::SETTINGS_REPLAY, Item::get(Item::COMPASS));
		$this->registerItem(5, self::FAST_FORWARD_REPLAY, Item::get(Item::CLOCK));

		$this->registerItem(0, self::BOT_START, Item::get(Item::SNOWBALL));
		$this->registerItem(7, self::BOT_PLAY, Item::get(Item::EMERALD));
		$this->registerItem(7, self::BOT_PAUSE, Item::get(Item::DIAMOND));
		$this->registerItem(8, self::BOT_LEAVE, Item::get(Item::IRON_DOOR));
		$this->registerItem(7, self::BOT_SETTING, Item::get(Item::BOOK));
	}

	/**
	 * @param int    $slot
	 * @param string $localName
	 * @param Item   $item
	 */
	private function registerItem(int $slot, string $localName, Item $item) : void{

		$item = new MineceitItem($slot, $localName, $item);
		$this->items[$localName] = $item;

		if(strpos($localName, 'hub.') !== false){
			$this->hubKeys[] = $localName;
		}elseif(strpos($localName, 'party.') !== false){
			$this->partyKeys[] = $localName;
		}elseif(strpos($localName, 'replay.') !== false){
			$this->replayKeys[] = $localName;
		}elseif(strpos($localName, 'bot.') !== false){
			$this->botKeys[] = $localName;
		}
	}

	/**
	 * Registers new items to the item factory.
	 */
	private function registerNewItems() : void{

		// Golden apple.
		ItemFactory::registerItem(new GoldenApple(), true);
		Item::addCreativeItem(GoldenApple::create());
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $clearInv
	 */
	public function spawnHubItems(MineceitPlayer $player, bool $clearInv = true) : void{

		$inv = $player->getInventory();

		if($clearInv){
			$player->getExtensions()->clearAll();
		}

		$lang = $player->getLanguageInfo()->getLanguage();
		$locale = $lang->getLocale();

		$inQueue = $player->isInQueue();

		foreach($this->hubKeys as $localName){

			$item = $this->items[$localName];
			$slot = $item->getSlot();

			if(!$inQueue && (strpos($localName, '.leave-queue') !== false || strpos($localName, '.wait-queue') !== false)){
				continue;
			}

			$name = $item->getName($locale);
			$i = clone $item->getItem()->setCustomName($name);
			$inv->setItem($slot, $i);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $clearInv
	 *
	 * Gives the player the bot items.
	 */
	public function spawnBotItems(MineceitPlayer $player, bool $clearInv = true) : void{

		$inv = $player->getInventory();

		if($clearInv){
			$player->getExtensions()->clearAll();
		}

		$lang = $player->getLanguageInfo()->getLanguage();
		$locale = $lang->getLocale();

		$botKeys = $this->botKeys;

		foreach($botKeys as $localName){

			$item = $this->items[$localName];
			$slot = $item->getSlot();

			if($localName === self::BOT_PLAY || $localName === self::BOT_PAUSE){
				continue;
			}

			$name = $item->getName($locale);
			$i = clone $item->getItem()->setCustomName($name);
			$inv->setItem($slot, $i);
			$inv->setItem(1, Item::get(Item::SANDSTONE, 0, 64));
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $clearInv
	 */
	public function spawnEventItems(MineceitPlayer $player, bool $clearInv = true) : void{

		$inv = $player->getInventory();

		if($clearInv){
			$player->getExtensions()->clearAll();
		}

		$lang = $player->getLanguageInfo()->getLanguage();

		$keys = [self::HUB_PLAYER_SETTINGS => 4, self::SPEC_LEAVE => 8];

		foreach($keys as $key => $slot){
			$item = $this->items[$key];
			if($item instanceof MineceitItem){
				$name = $item->getName($lang->getLocale());
				$i = clone $item->getItem()->setCustomName($name);
				$inv->setItem($slot, $i);
			}
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $clearInv
	 *
	 * Gives the player the replay items.
	 */
	public function spawnReplayItems(MineceitPlayer $player, bool $clearInv = true) : void{

		$inv = $player->getInventory();

		if($clearInv){
			$player->getExtensions()->clearAll();
		}

		$lang = $player->getLanguageInfo()->getLanguage();
		$locale = $lang->getLocale();

		$replayKeys = array_merge($this->replayKeys, [self::SPEC_LEAVE]);

		foreach($replayKeys as $localName){

			$item = $this->items[$localName];
			$slot = $item->getSlot();

			if($localName === self::PLAY_REPLAY){
				continue;
			}

			$name = $item->getName($locale);
			$i = clone $item->getItem()->setCustomName($name);
			$inv->setItem($slot, $i);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function givePlayItem(MineceitPlayer $player) : void{

		$item = $this->items[self::PLAY_REPLAY];

		$i = clone $item->getItem()->setCustomName(
			$item->getName(
				$player->getLanguageInfo()->getLanguage()->getLocale()
			)
		);

		$player->getInventory()->setItem($item->getSlot(), $i);
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function givePauseItem(MineceitPlayer $player) : void{

		$item = $this->items[self::PAUSE_REPLAY];

		$i = clone $item->getItem()->setCustomName(
			$item->getName(
				$player->getLanguageInfo()->getLanguage()->getLocale()
			)
		);

		$player->getInventory()->setItem($item->getSlot(), $i);
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function addLeaveQueueItem(MineceitPlayer $player) : void{

		if(!$player->isInQueue()){
			return;
		}

		$item = $this->items[self::HUB_LEAVE_QUEUE];

		$i = clone $item->getItem()->setCustomName(
			$item->getName(
				$player->getLanguageInfo()->getLanguage()->getLocale()
			)
		);

		$player->getInventory()->setItem($item->getSlot(), $i);
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function removeQueueItem(MineceitPlayer $player) : void{

		if($player->isInQueue()){
			return;
		}

		$inv = $player->getInventory();

		$item = $inv->getItem(8);

		$mineceitItem = $this->getItem($item);

		if($mineceitItem !== null && $mineceitItem->getLocalName() === self::HUB_LEAVE_QUEUE){
			$inv->setItem(8, Item::get(Item::AIR));
		}
	}

	/**
	 * @param Item $item
	 *
	 * @return MineceitItem|null
	 */
	public function getItem(Item $item) : ?MineceitItem{
		foreach($this->items as $mineceitItem){
			if($this->compareItems($mineceitItem, $item)){
				return $mineceitItem;
			}
		}
		return null;
	}

	/**
	 * @param MineceitItem $i1
	 * @param Item         $i2
	 *
	 * @return bool
	 */
	private function compareItems(MineceitItem $i1, Item $i2) : bool{
		$name = $i2->getName();
		if($i1->getItem()->getId() === $i2->getId()){
			return $i1->isName($name);
		}

		return false;
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function giveLeaveDuelItem(MineceitPlayer $player){
		$item = $this->items[self::SPEC_LEAVE];

		$player->getExtensions()->clearAll();

		$player->getInventory()->setItem($item->getSlot(), clone $item->getItem()->setCustomName($item->getName(
			$player->getLanguageInfo()->getLanguage()->getLocale()
		)));
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function givePauseBotItem(MineceitPlayer $player) : void{

		$player->getExtensions()->clearAll();

		$keys = [self::BOT_PAUSE => 7, self::BOT_LEAVE => 8];

		$lang = $player->getLanguageInfo()->getLanguage();

		$inv = $player->getInventory();

		foreach($keys as $key => $slot){
			$item = $this->items[$key];
			if($item instanceof MineceitItem){
				$name = $item->getName($lang->getLocale());
				$i = clone $item->getItem()->setCustomName($name);
				$inv->setItem($slot, $i);
			}
		}

		$player->getInventory()->setItem(0, Item::get(Item::SANDSTONE, 0, 64));
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function givePlayBotItem(MineceitPlayer $player) : void{

		$player->getExtensions()->clearAll();

		$keys = [self::BOT_PLAY => 7, self::BOT_LEAVE => 8];

		$lang = $player->getLanguageInfo()->getLanguage();

		$inv = $player->getInventory();

		foreach($keys as $key => $slot){
			$item = $this->items[$key];
			if($item instanceof MineceitItem){
				$name = $item->getName($lang->getLocale());
				$i = clone $item->getItem()->setCustomName($name);
				$inv->setItem($slot, $i);
			}
		}

		$player->getInventory()->setItem(0, Item::get(Item::SANDSTONE, 0, 64));
	}

	/**
	 * @param MineceitParty $party
	 */
	public function addLeaveQueuePartyItem(MineceitParty $party) : void{

		$members = $party->getPlayers();

		foreach($members as $player){
			$player->getExtensions()->clearAll();

			if($party->isOwner($player)){
				$item = $this->items[self::PARTY_DUEL];
				$i = clone $item->getItem()->setCustomName(
					$item->getName(
						$player->getLanguageInfo()->getLanguage()->getLocale()
					)
				);
				$player->getInventory()->setItem(0, $i);

				$item = $this->items[self::HUB_LEAVE_QUEUE];
				$i = clone $item->getItem()->setCustomName(
					$item->getName(
						$player->getLanguageInfo()->getLanguage()->getLocale()
					)
				);
			}else{
				$item = $this->items[self::HUB_WAIT_QUEUE];
				$i = clone $item->getItem()->setCustomName(
					$item->getName(
						$player->getLanguageInfo()->getLanguage()->getLocale()
					)
				);
			}

			$player->getInventory()->setItem($item->getSlot(), $i);
		}
	}

	/**
	 * @param MineceitParty $party
	 */
	public function removeQueuePartyItem(MineceitParty $party) : void{

		$members = $party->getPlayers();

		foreach($members as $player){
			$inv = $player->getInventory();

			$item = $inv->getItem(8);

			$mineceitItem = $this->getItem($item);

			if($mineceitItem !== null && ($mineceitItem->getLocalName() === self::HUB_LEAVE_QUEUE || $mineceitItem->getLocalName() === self::HUB_WAIT_QUEUE)){
				$inv->setItem(8, Item::get(Item::AIR));
			}

			$this->spawnPartyItems($player, false);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $clearInv
	 */
	public function spawnPartyItems(MineceitPlayer $player, bool $clearInv = true) : void{

		$party = $player->getParty();

		if($party === null) return;

		$inv = $player->getInventory();

		if($clearInv){
			$player->getExtensions()->clearAll();
		}

		$lang = $player->getLanguageInfo()->getLanguage();
		$locale = $lang->getLocale();

		$sendSettings = $party->isOwner($player);

		$partyKeys = $this->partyKeys;

		foreach($partyKeys as $localName){

			$item = $this->items[$localName];
			if(in_array($localName, [self::PARTY_SETTINGS, self::PARTY_GAMES, self::PARTY_DUEL, self::PARTY_EVENT]) && !$sendSettings){
				continue;
			}

			$slot = $item->getSlot();
			$name = $item->getName($locale);
			$i = clone $item->getItem()->setCustomName($name);
			$inv->setItem($slot, $i);
		}
	}
}
