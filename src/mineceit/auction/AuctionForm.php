<?php

declare(strict_types=1);

namespace mineceit\auction;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AuctionForm{
	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function mainAuctionForm(MineceitPlayer $player) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					switch((int) $data){
						case 0:
							$form = self::createAuctionForm($event);
							$event->sendFormWindow($form);
							break;
						case 1:
							$form = self::ongoingAuctionForm($event);
							$event->sendFormWindow($form);
							break;
						case 2:
							$form = self::endedAuctionForm($event);
							$event->sendFormWindow($form);
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->getMessage(Language::AUCTION_FORM_TITLE));
		$form->setContent($lang->getMessage(Language::AUCTION_FORM_DESC));
		$form->addButton($lang->getMessage(Language::AUCTION_FORM_CREATE), 0, 'textures/ui/icon_deals.png');
		$form->addButton($lang->getMessage(Language::AUCTION_FORM_ONGOING), 0, 'textures/ui/anvil_icon.png');
		$form->addButton($lang->getMessage(Language::AUCTION_FORM_ENDED), 0, 'textures/ui/creative_icon.png');

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return CustomForm|null
	 */
	public static function createAuctionForm(MineceitPlayer $player) : ?CustomForm{
		$banList = [
			'Tag:' . TextFormat::BOLD . TextFormat::AQUA . 'FRIED',
			'Tag:' . TextFormat::BOLD . TextFormat::RED . 'DEM' . TextFormat::DARK_RED . 'ONIC',
			'Tag:' . TextFormat::RED . TextFormat::BOLD . 'BOSSY',
			'Tag:' . TextFormat::GREEN . TextFormat::BOLD . 'WRESTLER',
			'Tag:' . TextFormat::YELLOW . TextFormat::BOLD . 'EATER',
			'Tag:' . TextFormat::RED . TextFormat::BOLD . 'POTTER',
			'Tag:' . TextFormat::BOLD . TextFormat::DARK_RED . 'X' . TextFormat::RED . 'O' . TextFormat::GOLD . 'O' . TextFormat::YELLOW . 'P' . TextFormat::GREEN . 'E' . TextFormat::DARK_GREEN . 'R' . TextFormat::AQUA . 'M' . TextFormat::DARK_AQUA . 'A' . TextFormat::BLUE . 'N',
			'Tag:' . TextFormat::BOLD . TextFormat::DARK_AQUA . 'BUTCHER',
			'Tag:' . TextFormat::BOLD . TextFormat::YELLOW . 'SLAUGHTER',
			'Tag:' . TextFormat::BOLD . TextFormat::DARK_PURPLE . 'BALD',
			'Tag:' . TextFormat::BOLD . TextFormat::DARK_RED . 'B' . TextFormat::RED . 'E' . TextFormat::GOLD . 'S' . TextFormat::YELLOW . 'T ' . TextFormat::GREEN . 'W' . TextFormat::DARK_GREEN . 'W',
			'Tag:' . TextFormat::BOLD . TextFormat::GOLD . 'BOX' . TextFormat::YELLOW . 'ER',
			'Tag:' . TextFormat::BOLD . TextFormat::AQUA . 'RUSH' . TextFormat::DARK_AQUA . 'MANIA',
			'Tag:' . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'B' . TextFormat::WHITE . 'U' . TextFormat::LIGHT_PURPLE . 'R' . TextFormat::WHITE . 'N' . TextFormat::LIGHT_PURPLE . 'T',
			'Tag:' . TextFormat::BOLD . TextFormat::RED . 'HEAVY' . TextFormat::WHITE . 'WEIGHT',
			'Tag:' . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'SUPPORTER',
			'Tag:' . TextFormat::BOLD . TextFormat::RED . 'CONTRIBUTOR',
			'Tag:' . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'SUBSCRIBER',
			'Tag:' . TextFormat::BOLD . TextFormat::RED . 'BEQUEATH',
			'Tag:' . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'ZE' . TextFormat::WHITE . 'QA',
			'Tag:' . TextFormat::BOLD . TextFormat::DARK_RED . 'C' . TextFormat::RED . 'L' . TextFormat::GOLD . 'O' . TextFormat::YELLOW . 'U' . TextFormat::GREEN . 'T' . TextFormat::DARK_GREEN . 'E' . TextFormat::AQUA . 'D',
			'Cape:Lunar',
			'Cape:Galaxy',
			'Cape:Pepe',
			'Cape:Chimera',
			'Artifact:Adidas',
			'Artifact:Boxing',
			'Artifact:Nike',
			'Artifact:LouisVuitton',
			'Artifact:Gudoudame',
			'Tag:None',
			'Cape:None',
			'Artifact:None'
		];

		$itemList = [];
		$validtags = $player->getValidTags();
		$validcapes = $player->getValidCapes();
		$validstuffs = $player->getValidStuffs();

		foreach($validtags as $tag){
			$itemList[] = 'Tag:' . $tag;
		}

		foreach($validcapes as $cape){
			$itemList[] = 'Cape:' . $cape;
		}

		foreach($validstuffs as $stuff){
			$itemList[] = 'Artifact:' . $stuff;
		}

		$itemList = array_values(array_diff($itemList, $banList));

		$auctionHouse = MineceitCore::getAuctionHouse();
		$ongoingItems = $auctionHouse->getItems();
		$myOngoingItems = 0;
		foreach($ongoingItems as $item){
			if($item->getOwner()->equalsPlayer($player)) $myOngoingItems = $myOngoingItems + 1;
		}

		$form = new CustomForm(function(Player $event, $data = null) use ($itemList, $auctionHouse, $myOngoingItems){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null && $event->isInHub() && isset($itemList[(int) $data[0]])){

					$lang = $event->getLanguageInfo()->getLanguage();
					if($myOngoingItems >= 3){
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->getMessage(Language::LIMIT_ONGOING_ITEM));
						return null;
					}

					$itemInfo = explode(':', $itemList[(int) $data[0]]);
					$item = (string) $itemInfo[1];
					$itemType = (string) $itemInfo[0];
					$currency = $data[1] === 0 ? 'Coins' : 'Shards';
					$startPrice = ['0', '100', '1000', '3000', '5000', '10000'];
					$startPrice = (int) $startPrice[(int) $data[2]];
					$minBid = ['1', '10', '100', '300', '500', '1000'];
					$minBid = (int) $minBid[(int) $data[3]];
					$duration = ['1', '3', '5', '10', '15', '30', '60'];
					$duration = (int) $duration[(int) $data[4]];
					$itemTitle = (string) $item . TextFormat::RESET . TextFormat::WHITE . ' by ' . $event->getDisplayName();

					$auctionItem = new AuctionItem(
						$itemTitle,
						$item,
						$itemType,
						$currency,
						$startPrice,
						$minBid,
						$duration,
						$event
					);
					$auctionHouse->addItem($auctionItem);
					$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $lang->getMessage(Language::AUCTION_CREATE_SUCCESS));
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->getMessage(Language::AUCTION_FORM_CREATE_TITLE));
		$form->addDropdown($lang->getMessage(Language::AUCTION_FORM_CREATE_ITEM), $itemList);
		$form->addDropdown($lang->getMessage(Language::AUCTION_FORM_CREATE_CURRENCY), ['Coins', 'Shards']);
		$form->addDropdown($lang->getMessage(Language::AUCTION_FORM_CREATE_PRICE), ['0', '100', '1000', '3000', '5000', '10000']);
		$form->addDropdown($lang->getMessage(Language::AUCTION_FORM_CREATE_BID), ['1', '10', '100', '300', '500', '1000']);
		$form->addDropdown($lang->getMessage(Language::AUCTION_FORM_CREATE_DURATION), ['1', '3', '5', '10', '15', '30', '60']);

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function ongoingAuctionForm(MineceitPlayer $player) : SimpleForm{
		$form = new SimpleForm(function(Player $event, $data = null){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				$event->getLanguageInfo()->getLanguage();

				if($data !== null){
					$auctionHouse = MineceitCore::getAuctionHouse();
					$itemList = $auctionHouse->getItems();
					if(isset($itemList[(int) $data])){
						$form = self::auctionItemDetailForm($event, $itemList[(int) $data]);
						$event->sendFormWindow($form);
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$form->setTitle($lang->getMessage(Language::AUCTION_FORM_ONGOING_TITLE));
		$form->setContent("Your Coins: " . TextFormat::YELLOW . $player->getStatsInfo()->getCoins() . TextFormat::RESET . "\nYour Shard: " . TextFormat::AQUA . $player->getStatsInfo()->getShards());

		$auctionHouse = MineceitCore::getAuctionHouse();
		$itemList = $auctionHouse->getItems();

		if(count($itemList) === 0){
			$form->addButton($lang->generalMessage(Language::NONE));
			return $form;
		}

		$currenttime = new \DateTime('NOW');
		foreach($itemList as $item){
			$endedtime = $item->getEndTime();
			$remaintime = $currenttime->diff($endedtime);
			$remaintime = $remaintime->format("%i minute(s), %s second(s)");
			$form->addButton($item->getTitle() . TextFormat::RESET . ' ' . $item->getTopPrice(true) . TextFormat::RED . "\n$remaintime", 0, "", (string) $item->getId());
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param AuctionItem    $item
	 *
	 * @return SimpleForm
	 */
	public static function auctionItemDetailForm(MineceitPlayer $player, AuctionItem $item) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null) use ($item){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					switch((int) $data){
						case 0:
							if($item->getOwner()->equalsPlayer($event)){
								$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $event->getLanguageInfo()
										->getLanguage()->getMessage(Language::CANNOT_BID_OWN_AUCTION));
							}else{
								$form = self::makingBidForm($event, $item);
								$event->sendFormWindow($form);
							}
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$content = "Title : " . $item->getTitle() . "\n";
		$content = $content . "Item : " . $item->getItem() . TextFormat::RESET . "\n";
		$content = $content . "Type : " . $item->getType() . "\n";
		$content = $content . "Owner : " . $item->getOwner()->getDisplayName() . "\n";
		$content = $content . "Min per bid : " . $item->getMinBid(true) . " " . $item->getCurrency() . "\n";
		$content = $content . "Top Bider : " . $item->getTopBider()->getDisplayName() . "\n";
		$content = $content . "Top Price : " . $item->getTopPrice(true) . " " . $item->getCurrency() . "\n";
		$currenttime = new \DateTime('NOW');
		$endedtime = $item->getEndTime();
		$remaintime = $currenttime->diff($endedtime);
		$remaintime = $remaintime->format("%i minute(s), %s second(s)");
		$content = $content . "End Time : " . $remaintime . "\n";

		$form->setTitle($item->getTitle());
		$form->setContent("Your Coins: " . TextFormat::YELLOW . $player->getStatsInfo()->getCoins() . TextFormat::RESET .
			"\nYour Shard: " . TextFormat::AQUA . $player->getStatsInfo()->getShards() . TextFormat::RESET . "\n\n" . $content . "\n");

		$form->addButton(TextFormat::GREEN . "Bid");

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param AuctionItem    $item
	 *
	 * @return CustomForm|null
	 */
	public static function makingBidForm(MineceitPlayer $player, AuctionItem $item) : ?CustomForm{

		$form = new CustomForm(function(Player $event, $data = null) use ($item){

			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					if(!is_numeric($data[0])){
						$event->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $event->
							getLanguageInfo()->getLanguage()->getMessage(Language::ONLY_PUT_NUMBER));
						return null;
					}
					$bidPrice = (int) $data[0];
					$auctionHouse = MineceitCore::getAuctionHouse();
					$auctionHouse->biding($item, $event, $bidPrice);
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();
		$form->setTitle($lang->getMessage(Language::AUCTION_FORM_BID_TITLE));
		$form->addInput("How many " . $item->getCurrency(true) . " do you want to bid.");

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return SimpleForm
	 */
	public static function endedAuctionForm(MineceitPlayer $player) : SimpleForm{
		$auctionHouse = MineceitCore::getAuctionHouse();
		$itemList = $auctionHouse->getEndedItems();

		$form = new SimpleForm(function(Player $event, $data = null) use ($itemList){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					if(isset($itemList[(int) $data])){
						$form = self::endedAuctionItemDetailForm($event, $itemList[(int) $data]);
						$event->sendFormWindow($form);
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();
		$form->setTitle($lang->getMessage(Language::AUCTION_FORM_ENDED_TITLE));
		$form->setContent("Your Coins: " . TextFormat::YELLOW . $player->getStatsInfo()->getCoins() . TextFormat::RESET . "\nYour Shard: " . TextFormat::AQUA . $player->getStatsInfo()->getShards());

		if(count($itemList) === 0){
			$form->addButton($lang->generalMessage(Language::NONE));
			return $form;
		}

		foreach($itemList as $item){
			$form->addButton($item->getTitle() . ' ' . $item->getTopPrice(true));
		}

		return $form;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param AuctionItem    $item
	 *
	 * @return SimpleForm
	 */
	public static function endedAuctionItemDetailForm(MineceitPlayer $player, AuctionItem $item) : SimpleForm{

		$form = new SimpleForm(function(Player $event, $data = null) use ($item){
			if($event instanceof MineceitPlayer){

				$event->removeFormData();

				if($data !== null){
					switch((int) $data){
						case 0:
							$form = self::EndedAuctionForm($event);
							$event->sendFormWindow($form);
							break;
					}
				}
			}
		});

		$lang = $player->getLanguageInfo()->getLanguage();

		$content = "Title : " . $item->getTitle() . "\n";
		$content = $content . "Item : " . $item->getItem() . TextFormat::RESET . "\n";
		$content = $content . "Type : " . $item->getType() . "\n";
		$content = $content . "Owner : " . $item->getOwner()->getDisplayName() . "\n";
		$content = $content . "Min per bid : " . $item->getMinBid(true) . " " . $item->getCurrency() . "\n";
		$content = $content . "Top Bider : " . $item->getTopBider()->getDisplayName() . "\n";
		$content = $content . "Top Price : " . $item->getTopPrice(true) . " " . $item->getCurrency() . "\n";
		$content = $content . "End Time : : " . $item->getEndTime()->format('Y-m-d H:i:s') . "\n";

		$form->setTitle($item->getTitle());
		$form->setContent("Your Coins: " . TextFormat::YELLOW . $player->getStatsInfo()->getCoins() . TextFormat::RESET .
			"\nYour Shard: " . TextFormat::AQUA . $player->getStatsInfo()->getShards() . TextFormat::RESET . "\n\n" . $content . "\n");

		$form->addButton(TextFormat::RED . "BACK");

		return $form;
	}
}
