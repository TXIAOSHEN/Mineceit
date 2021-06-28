<?php

declare(strict_types=1);

namespace mineceit\player\info\duels;

use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\utils\TextFormat;

class DuelInfo{

	/* @var int */
	private $numHits;

	/* @var int */
	private $potionCount;

	/* @var int */
	private $stewCount;

	/* @var int */
	private $health;

	/* @var int */
	private $hunger;

	/* @var string */
	private $name;

	/* @var Item[]|array */
	private $items;

	/* @var Item[]|array */
	private $armor;

	/* @var string */
	private $queue;

	/* @var bool */
	private $ranked;

	/* @var string */
	private $displayName;

	public function __construct(MineceitPlayer $player, string $queue, bool $ranked, int $numHits){
		$this->name = $player->getName();
		$this->numHits = $numHits;
		$this->queue = $queue;
		$this->health = (int) $player->getHealth();
		$this->hunger = (int) $player->getFood();
		$this->potionCount = 0;
		$this->stewCount = 0;

		$this->ranked = $ranked;
		$this->displayName = $player->getDisplayName();

		if($player->isOnline()){
			$inv = $player->getInventory();
			if($inv !== null){
				$this->items = $inv->getContents(true);
			}else{
				for($i = 0; $i < 36; $i++){
					$this->items[$i] = new ItemBlock(0, 0);
				}
			}
		}else{
			for($i = 0; $i < 36; $i++){
				$this->items[$i] = new ItemBlock(0, 0);
			}
		}


		if($player->isOnline()){
			$armorInv = $player->getArmorInventory();
			if($armorInv !== null){
				$this->armor = $armorInv->getContents(true);
			}else{
				for($i = 0; $i < 4; $i++){
					$this->armor[$i] = new ItemBlock(0, 0);
				}
			}
		}else{
			for($i = 0; $i < 4; $i++){
				$this->armor[$i] = new ItemBlock(0, 0);
			}
		}


		foreach($this->items as $item){
			$id = $item->getId();
			if($id === Item::SPLASH_POTION) $this->potionCount++;
			if($id === Item::MUSHROOM_STEW) $this->stewCount++;
		}
	}

	/**
	 * @return string
	 */
	public function getPlayerName() : string{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getDisplayName() : string{
		return $this->displayName;
	}

	/**
	 * @return int
	 */
	public function getHealth() : int{
		return $this->health;
	}

	/**
	 * @return int
	 */
	public function getHunger() : int{
		return $this->hunger;
	}

	/**
	 * @return bool
	 */
	public function isRanked() : bool{
		return $this->ranked;
	}

	/**
	 * @return string
	 */
	public function getTexture() : string{
		$kit = MineceitCore::getKits()->getKit($this->queue);
		return $kit !== null ? $kit
			->getMiscKitInfo()->getTexture() : '';
	}

	/**
	 * @return string
	 */
	public function getQueue() : string{
		return $this->queue;
	}

	/**
	 * @return array|Item[]
	 */
	public function getStatsItems() : array{

		$head = Item::get(Item::MOB_HEAD, 3, 1)->setCustomName(TextFormat::YELLOW . $this->displayName . TextFormat::RESET);

		$healthItem = Item::get(Item::GLISTERING_MELON, 1, $this->properCount($this->health))->setCustomName(TextFormat::RED . "$this->health HP");

		$numHitsItem = Item::get(Item::PAPER, 0, $this->properCount($this->numHits))->setCustomName(TextFormat::GOLD . "$this->numHits Hits");

		$hungerItem = Item::get(Item::STEAK, 0, $this->properCount($this->hunger))->setCustomName(TextFormat::GREEN . "$this->hunger Hunger-Points");

		$numPots = Item::get(Item::SPLASH_POTION, 21, $this->properCount($this->potionCount))->setCustomName(TextFormat::AQUA . "$this->potionCount Pots");

		$numStews = Item::get(Item::MUSHROOM_STEW, 0, $this->properCount($this->stewCount))->setCustomName(TextFormat::AQUA . "$this->stewCount Stews");

		$arr = [$head, $healthItem, $hungerItem, $numHitsItem];

		if($this->displayPots()) $arr[] = $numPots;

		if($this->displayStews()) $arr[] = $numStews;

		return $arr;
	}

	/**
	 * @param int $count
	 *
	 * @return int
	 */
	private function properCount(int $count) : int{
		return $count <= 0 ? 1 : $count;
	}

	/**
	 * @return bool
	 */
	private function displayPots() : bool{
		return $this->queue === 'NoDebuff';
	}

	/**
	 * @return bool
	 */
	private function displayStews() : bool{
		return $this->queue === 'Soup';
	}

	/**
	 * @return array|Item[]
	 */
	public function getArmor() : array{
		return $this->armor;
	}

	/**
	 * @return array|Item[]
	 */
	public function getItems() : array{
		return $this->items;
	}
}
