<?php

declare(strict_types=1);

namespace mineceit\auction;

use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\utils\TextFormat;

class AuctionItem{
	/* @var int */
	private $itemID;

	/* @var string */
	private $itemTitle;

	/* @var string */
	private $itemType;

	/* @var string */
	private $item;

	/* @var int */
	private $minBid;

	/* @var MineceitPlayer */
	private $owner;

	/* @var int */
	private $startPrice;

	/* @var \DateTime */
	private $endTime;

	/* @var MineceitPlayer */
	private $topBider;

	/* @var int */
	private $topPrice;

	/* @var string */
	private $currency;

	public function __construct(
		string $itemTitle,
		string $item,
		string $itemType,
		string $currency,
		int $startPrice,
		int $minBid,
		int $duration,
		MineceitPlayer $owner
	){
		$this->itemID = MineceitCore::getAuctionHouse()->getItems(true);
		$this->itemTitle = $itemTitle;
		$this->item = $item;
		$this->itemType = $itemType;
		$this->minBid = $minBid;
		$this->owner = $owner;
		$this->startPrice = $startPrice;
		$endTime = new \DateTime('NOW');
		$endTime->modify("+{$duration} mins");
		$this->endTime = $endTime;
		$this->topBider = $this->owner;
		$this->topPrice = $startPrice;
		$this->currency = $currency;
	}

	/**
	 * @param int $n
	 */
	public function increaseId(int $n = 1) : void{
		$this->itemID = $this->itemID + $n;
	}

	/**
	 * @return int
	 */
	public function getId() : int{
		return $this->itemID;
	}

	/**
	 * @return string
	 */
	public function getTitle() : string{
		return $this->itemTitle;
	}

	/**
	 * @return string
	 */
	public function getItem() : string{
		return $this->item;
	}

	/**
	 * @return string
	 */
	public function getType() : string{
		return $this->itemType;
	}

	/**
	 * @return MineceitPlayer
	 */
	public function getOwner() : MineceitPlayer{
		return $this->owner;
	}

	/**
	 * @param bool $int
	 *
	 * @return int|string
	 */
	public function getMinBid(bool $int = false){
		$price = null;
		if($this->currency === 'Coins') $price = TextFormat::YELLOW . $this->minBid . TextFormat::RESET;
		elseif($this->currency === 'Shards') $price = TextFormat::AQUA . $this->minBid . TextFormat::RESET;
		return $int ? $price : $this->minBid;
	}

	/**
	 * @return MineceitPlayer
	 */
	public function getTopBider() : MineceitPlayer{
		return $this->topBider;
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function setTopBider(MineceitPlayer $player) : void{
		$this->topBider = $player;
	}

	/**
	 * @param bool $int
	 *
	 * @return int|string
	 */
	public function getTopPrice(bool $int = false){
		$result = ($this->currency === 'Coins') ? TextFormat::YELLOW . $this->topPrice . TextFormat::RESET : TextFormat::AQUA . $this->topPrice . TextFormat::RESET;
		return $int ? $result : $this->topPrice;
	}

	/**
	 * @param int $price
	 */
	public function setTopPrice(int $price) : void{
		$this->topPrice = $price;
	}

	/**
	 * @return \DateTime
	 */
	public function getEndTime() : \DateTime{
		return $this->endTime;
	}

	/**
	 * @param bool $int
	 *
	 * @return int|string
	 */
	public function getCurrency(bool $int = false){
		$result = ($this->currency === "Coins") ? TextFormat::YELLOW . $this->currency . TextFormat::RESET : TextFormat::AQUA . $this->currency . TextFormat::RESET;
		return $int ? $result : $this->currency;
	}

	/**
	 * give the item to player
	 *
	 * @param MineceitPlayer $player
	 * @param AuctionHouse   $auctionHouse
	 * @param bool           $flag
	 */
	public function giveItem(MineceitPlayer $player, AuctionHouse $auctionHouse, bool $flag = false) : void{
		if($flag){
			$auctionHouse->addItemBack($player, $this->itemType . ':' . $this->item);
			return;
		}

		if($player->isOnline()){
			$type = $this->itemType;
			if($type === 'Tag') $player->setValidTags($this->item);
			elseif($type === 'Cape') $player->setValidCapes($this->item);
			elseif($type === 'Artifact') $player->setValidStuffs($this->item);
		}else{
			$auctionHouse->addItemBack($player, $this->itemType . ':' . $this->item);
		}
	}

	/**
	 * give the price to player
	 *
	 * @param MineceitPlayer $player
	 * @param AuctionHouse   $auctionHouse
	 * @param bool           $flag
	 */
	public function giveTopPrice(MineceitPlayer $player, AuctionHouse $auctionHouse, bool $flag = false) : void{
		if($flag){
			if($this->currency === 'Coins') $auctionHouse->addCoinBack($player, $this->topPrice);
			elseif($this->currency === 'Shards') $auctionHouse->addShardBack($player, $this->topPrice);
		}else{
			if($player->isOnline()){
				if($this->currency === 'Coins') $player->getStatsInfo()->addCoins($this->topPrice);
				elseif($this->currency === 'Shards') $player->getStatsInfo()->addShards($this->topPrice);
			}else{
				if($this->currency === 'Coins') $auctionHouse->addCoinBack($player, $this->topPrice);
				elseif($this->currency === 'Shards') $auctionHouse->addShardBack($player, $this->topPrice);
			}
		}
	}
}
