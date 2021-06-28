<?php

declare(strict_types=1);

namespace mineceit\auction;

use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\utils\TextFormat;

class AuctionHouse{
	/* @var AuctionItem[]|array */
	private $items;

	/* @var AuctionItem[]|array */
	private $endedItems;

	/* @var int[]|array */
	private $coinBack;

	/* @var int[]|array */
	private $shardBack;

	/* @var string[]|array */
	private $itemBack;

	/* @var bool */
	private $isRun;

	public function __construct(){
		$this->items = [];
		$this->endedItems = [];
		$this->coinBack = [];
		$this->shardBack = [];
		$this->itemBack = [];
		$this->isRun = true;
		$this->initAuctionHouse();
	}

	/**
	 * Load the current AuctionHouse
	 */
	public function initAuctionHouse() : void{
		if(!is_dir(MineceitCore::getDataFolderPath() . 'auction/'))
			mkdir(MineceitCore::getDataFolderPath() . 'auction/');

		$dataPath = MineceitCore::getDataFolderPath() . 'auction/AuctionHouse.bin';
		if(file_exists($dataPath)){
			$objData = file_get_contents($dataPath);
			$obj = unserialize($objData);
			if(is_object($obj)){
				$this->items = $obj->items;
				$this->endedItems = $obj->endedItems;
				$this->coinBack = $obj->coinBack;
				$this->shardBack = $obj->shardBack;
				$this->itemBack = $obj->itemBack;
				$this->isRun = $obj->isRun;
			}
		}
	}

	/**
	 * Save the current AuctionHouse
	 */
	public function saveAuctionHouse() : void{
		foreach($this->items as $item){
			$item->giveItem($item->getOwner(), $this, true);
			if(!$item->getTopBider()->equalsPlayer($item->getOwner())){
				$item->giveTopPrice($item->getTopBider(), $this, true);
			}
		}

		$this->items = [];
		$this->endedItems = [];

		$dataPath = MineceitCore::getDataFolderPath() . 'auction/AuctionHouse.bin';
		$objData = MineceitCore::getAuctionHouse();
		file_put_contents($dataPath, serialize($objData));
	}

	/**
	 * Update the current AuctionHouse
	 */
	public function updateAuctionHouse() : void{
		$ongoingItem = $this->items;
		$now = new \DateTime('NOW');
		foreach($ongoingItem as $key => $item){
			$item = $ongoingItem[$key];
			if($now > $item->getEndTime()){
				$item->getTopBider()->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET .
					$item->getTitle() . $item->getTopBider()->getLanguageinfo()
						->getLanguage()->getMessage(Language::AUCTION_ENDED));
				$item->giveItem($item->getTopBider(), $this);
				if(!$item->getTopBider()->equalsPlayer($item->getOwner())){
					$item->getOwner()->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET .
						$item->getTitle() . $item->getOwner()->getLanguageInfo()
							->getLanguage()->getMessage(Language::AUCTION_ENDED));
					$item->giveTopPrice($item->getOwner(), $this);
				}
				array_unshift($this->endedItems, $item);
				unset($this->items[$key]);
			}
		}
	}


	/**
	 * @param AuctionItem $item
	 */
	public function addItem(AuctionItem $item) : void{
		while(isset($this->items[$item->getId()]))
			$item->increaseId();

		if(!isset($this->items[$item->getId()])){
			$player = $item->getOwner();
			$itemstr = $item->getItem();
			$type = $item->getType();
			if($type === 'Tag'){
				$player->removeValidTags($itemstr);
				if($player->getTag() === $itemstr) $player->setTag('');
			}elseif($type === 'Cape'){
				$player->removeValidCapes($itemstr);
				if($player->getCape() === $itemstr) $player->setCape('');
			}elseif($type === 'Artifact'){
				$player->removeValidStuffs($itemstr);
				if($player->getStuff() === $itemstr) $player->setStuff('');
			}
			$this->items[$item->getId()] = $item;
		}
	}

	/**
	 * @param bool $int
	 *
	 * @return array|int|AuctionItem[]
	 */
	public function getItems(bool $int = false){
		return ($int) ? count($this->items) : $this->items;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param string         $item
	 */
	public function addItemBack(MineceitPlayer $player, string $item) : void{
		if(isset($this->itemBack[$player->getName()])){
			$this->itemBack[$player->getName()][] = $item;
		}else{
			$this->itemBack[$player->getName()] = [$item];
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param int            $amount
	 */
	public function addCoinBack(MineceitPlayer $player, int $amount) : void{
		if(isset($this->coinBack[$player->getName()])){
			$this->coinBack[$player->getName()] += $amount;
		}else{
			$this->coinBack[$player->getName()] = $amount;
		}
	}

	/**
	 * @param MineceitPlayer $player
	 * @param int            $amount
	 */
	public function addShardBack(MineceitPlayer $player, int $amount) : void{
		if(isset($this->shardBack[$player->getName()])){
			$this->shardBack[$player->getName()] += $amount;
		}else{
			$this->shardBack[$player->getName()] = $amount;
		}
	}

	/**
	 * @param bool $int
	 *
	 * @return array|int|AuctionItem[]
	 */
	public function getEndedItems(bool $int = false){
		return ($int) ? count($this->endedItems) : $this->endedItems;
	}

	/**
	 * @param AuctionItem    $item
	 * @param MineceitPlayer $player
	 * @param int            $bidPrice
	 */
	public function biding(AuctionItem $item, MineceitPlayer $player, int $bidPrice) : void{
		if($item->getCurrency() === 'Coins' && $player->getStatsInfo()->getCoins() < $bidPrice){
			$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $player->getLanguageInfo()->getLanguage()->getMessage(Language::NOT_ENOUGH_COINS));
			return;
		}
		if($item->getCurrency() === 'Shards' && $player->getStatsInfo()->getShards() < $bidPrice){
			$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $player->getLanguageInfo()->getLanguage()->getMessage(Language::NOT_ENOUGH_SHARDS));
			return;
		}

		if(isset($this->items[$item->getId()])){
			$item = $this->items[$item->getId()];
			if($bidPrice - $item->getTopPrice() >= $item->getMinBid()){
				$topBider = $item->getTopBider();

				if(!$topBider->equalsPlayer($item->getOwner())){
					$item->giveTopPrice($topBider, $this);
				}

				if($item->getCurrency() === 'Coins')
					$player->getStatsInfo()->removeCoins($bidPrice);
				elseif($item->getCurrency() === 'Shards')
					$player->getStatsInfo()->removeShards($bidPrice);

				$item->setTopBider($player);
				$item->setTopPrice($bidPrice);

				$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $player->getLanguageInfo()->getLanguage()->getMessage(Language::BID_SUCCESS));
			}else{
				$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $player->getLanguageInfo()->getLanguage()->getMessage(Language::NOT_ENOUGH_BID));
			}
		}else{
			$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $player->getLanguageInfo()->getLanguage()->getMessage(Language::ITEM_ENDED));
		}
	}

	/**
	 * @return bool
	 */
	public function isRunning() : bool{
		return $this->isRun;
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function checkCoinBack(MineceitPlayer $player) : void{
		if(isset($this->coinBack[$player->getName()])){
			$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $player->getLanguageInfo()->getLanguage()->getMessage(Language::COIN_BACK_AUCTION));
			$player->getStatsInfo()->addCoins($this->coinBack[$player->getName()]);
			unset($this->coinBack[$player->getName()]);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function checkShardBack(MineceitPlayer $player) : void{
		if(isset($this->shardBack[$player->getName()])){
			$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $player->getLanguageInfo()->getLanguage()->getMessage(Language::SHARD_BACK_AUCTION));
			$player->getStatsInfo()->addShards($this->shardBack[$player->getName()]);
			unset($this->shardBack[$player->getName()]);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function checkItemBack(MineceitPlayer $player) : void{
		if(isset($this->itemBack[$player->getName()])){
			$items = $this->itemBack[$player->getName()];
			$player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $player->getLanguageInfo()->getLanguage()->getMessage(Language::ITEM_BACK_AUCTION));
			foreach($items as $item){
				$itemInfo = explode(':', $item);
				if($itemInfo[0] === 'Tag') $player->setValidTags($itemInfo[1]);
				elseif($itemInfo[0] === 'Cape') $player->setValidCapes($itemInfo[1]);
				elseif($itemInfo[0] === 'Artifact') $player->setValidStuffs($itemInfo[1]);
			}
			unset($this->itemBack[$player->getName()]);
		}
	}
}
