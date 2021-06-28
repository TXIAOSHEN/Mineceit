<?php

declare(strict_types=1);

namespace mineceit\game\behavior\kits;


use mineceit\kits\DefaultKit;
use mineceit\kits\IKit;
use mineceit\kits\KitsManager;
use mineceit\MineceitCore;
use mineceit\misc\AbstractListener;
use pocketmine\entity\Human;

class KitHolder extends AbstractListener{

	/** @var IKit|null */
	protected $kit = null;
	/** @var KitsManager */
	protected $kitsManager;
	/** @var IKitHolderEntity */
	protected $holderEntity;

	public function __construct(IKitHolderEntity $holderEntity){
		parent::__construct(MineceitCore::getInstance());

		$this->kitsManager = MineceitCore::getKits();
		$this->holderEntity = $holderEntity;
	}

	/**
	 * Clears the kit from the player.
	 *
	 * @param bool $clearFromPlayer - Clears the inventory.
	 */
	public function clearKit(bool $clearFromPlayer = true) : void{
		if($this->kit !== null){
			if($clearFromPlayer){
				$this->clearEntity();
			}
			$this->kit = null;
		}
	}

	/**
	 * Clears the inventory of the player.
	 */
	protected function clearEntity() : void{
		$this->getParentEntity()->getArmorInventory()->clearAll();
		$this->getParentEntity()->getInventory()->clearAll();
		$this->getParentEntity()->removeAllEffects();
	}

	/**
	 * @return Human
	 *
	 * Gets the parent entity.
	 */
	public function getParentEntity() : ?Human{
		return $this->holderEntity->getKitHolderEntity();
	}

	/**
	 * @return DefaultKit|null
	 *
	 * Gets the current kit of the entity.
	 */
	public function getKit() : ?IKit{
		return $this->kit;
	}

	/**
	 * @param string|IKit $kit
	 */
	public function setKit($kit) : void{
		if($kit instanceof IKit){
			if($this->hasKit()){
				$this->clearEntity();
			}
			$this->kit = $kit;
			$this->kit->giveTo($this->holderEntity);
			return;
		}

		if(is_string($kit)){
			$theKit = $this->kitsManager->getKit($kit);
			if($theKit !== null){
				if($this->hasKit()){
					$this->clearEntity();
				}
				$this->kit = $theKit;
				$this->kit->giveTo($this->holderEntity);
			}
		}
	}

	/**
	 * @return bool
	 *
	 * Determines if the kit holder has a kit.
	 */
	public function hasKit() : bool{
		return $this->kit !== null;
	}
}