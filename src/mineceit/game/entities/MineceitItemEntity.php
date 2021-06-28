<?php

namespace mineceit\game\entities;

use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\Player;

class MineceitItemEntity extends ItemEntity{

	public function onCollideWithPlayer(Player $player) : void{
		if($this->getPickupDelay() !== 0)
			return;

		if($player instanceof MineceitPlayer && $player->getExtensions()->isSpectator())
			return;

		$item = $this->getItem();
		$playerInventory = $player->getInventory();

		if($player->isSurvival() && !$playerInventory->canAddItem($item)){
			return;
		}

		$ev = new InventoryPickupItemEvent($playerInventory, $this);
		$ev->call();
		if($ev->isCancelled()){
			return;
		}

		switch($item->getId()){
			case Item::WOOD:
				$player->awardAchievement("mineWood");
				break;
			case Item::DIAMOND:
				$player->awardAchievement("diamond");
				break;
		}

		$pk = new TakeItemActorPacket();
		$pk->eid = $player->getId();
		$pk->target = $this->getId();
		$this->server->broadcastPacket($this->getViewers(), $pk);

		$playerInventory->addItem(clone $item);

		if($player instanceof MineceitPlayer && $player->isInDuel()){
			$duelHandler = MineceitCore::getDuelHandler()->getDuel($player);
			$duelHandler->setPickupItem($player, $this->getItem());
		}

		$this->flagForDespawn();
	}
}
