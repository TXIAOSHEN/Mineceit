<?php

declare(strict_types=1);

namespace mineceit\player\info\kits;


use mineceit\game\behavior\kits\IKitHolderEntity;
use mineceit\game\behavior\kits\KitHolder;
use mineceit\kits\DefaultKit;
use mineceit\kits\edited\EditedKit;
use mineceit\kits\IKit;
use mineceit\MineceitCore;
use pocketmine\item\Item;
use pocketmine\Server;

class PlayerKitHolder extends KitHolder{

	/** @var array|EditedKit[] */
	private $editedKits;
	/** @var Server */
	private $server;
	/** @var string */
	private $entityName;

	/** @var bool - Called when the edited kits were changed. */
	private $editedKitsChanged = false;
	/** @var DefaultKit|null - The current kit that the player is editing. */
	private $currentEditingKit = null;

	public function __construct(IKitHolderEntity $entity){
		parent::__construct($entity);

		$this->server = Server::getInstance();
		$this->entityName = $entity->getKitHolderEntity()->getName();
		$this->editedKits = [];
	}

	/**
	 * @param string $editingKit
	 *
	 * @return bool
	 *
	 *  Sets the kit that the player is editing.
	 */
	public function setEditingKit(string $editingKit) : bool{
		if($this->isEditingKit()){
			return false;
		}

		$kit = $this->kitsManager->getKit($editingKit);
		if($kit === null){
			return false;
		}
		$this->currentEditingKit = $kit;
		$this->clearEntity();
		$this->getParentEntity()->setImmobile(true);
		$this->currentEditingKit->giveTo($this->holderEntity);
		return true;
	}

	/**
	 * @return bool
	 *
	 * Determines if the player is editing a kit.
	 */
	public function isEditingKit() : bool{
		return $this->currentEditingKit !== null;
	}

	/**
	 * @param $cancelled - Sets the finished editing kit as cancelled.
	 * Sets the player as finished editing the kit.
	 */
	public function setFinishedEditingKit(bool $cancelled) : void{
		if(!$this->isEditingKit()){
			return;
		}
		if($cancelled){
			$this->clearEntity();
			$this->getParentEntity()->setImmobile(false);
			$this->currentEditingKit = null;
			return;
		}
		$items = $this->getParentEntity()->getInventory()->getContents();
		$this->addEditedKit($this->currentEditingKit, $items);
		$this->clearEntity();
		$this->getParentEntity()->setImmobile(false);
		$this->currentEditingKit = null;
	}

	/**
	 * @param DefaultKit   $kit
	 * @param array|Item[] $items
	 *
	 * Adds an edited kit to the player kit holder.
	 */
	private function addEditedKit(DefaultKit $kit, array $items) : void{
		if(isset($this->editedKits[$name = $kit->getName()])){
			$this->editedKits[$name]->setItems($items);
			return;
		}
		$editedKit = new EditedKit($kit, $items);
		$this->editedKits[$name] = $editedKit;
	}

	/**
	 * @param IKit|string $kit
	 *
	 * Sets the kit of the player based on the input, which is
	 * either a kit or a string.
	 */
	public function setKit($kit) : void{
		if(is_string($kit)
			&& isset($this->editedKits[$kit])){
			parent::setKit($this->editedKits[$kit]);
			return;
		}

		if($kit instanceof DefaultKit
			&& isset($this->editedKits[$kit->getName()])){
			parent::setKit($this->editedKits[$kit->getName()]);
			return;
		}
		parent::setKit($kit);
	}

	/**
	 * @param array $kitsData
	 *
	 * Called when the player kit has finished loading the kit data.
	 * Should not be used by anything except the Async load edited kits class.
	 */
	public function onLoadFinished(array $kitsData) : void{
		foreach($kitsData as $kitName => $data){
			$editedKit = EditedKit::load($kitName, $data);
			if($editedKit !== null && $editedKit->hasParentKit()){
				$this->editedKits[$editedKit->getName()] = $editedKit;
			}
		}
	}

	/**
	 * Loads the edited kits of the player kit holder.
	 */
	public function loadEditedKits() : void{
		$asyncTask = new AsyncLoadEditedKits($this->entityName);
		$this->server->getAsyncPool()->submitTask($asyncTask);
	}

	/**
	 * Saves the edited kits of the player kit holder.
	 */
	public function saveEditedKits() : void{
		foreach($this->editedKits as $kitName => $editedKit){
			$this->saveKit($editedKit);
		}
	}

	/**
	 * @param EditedKit $kit
	 *
	 * Saves the edited kit to a file path.
	 */
	public function saveKit(EditedKit $kit) : void{
		$initialPath = MineceitCore::getDataFolderPath() . "kits/";
		if(!is_dir($initialPath .= ($this->entityName . "/"))){
			mkdir($initialPath);
		}
		$initialPath .= $kit->getName() . ".json";
		if(!file_exists($initialPath)){
			$file = fopen($initialPath, "w");
			fclose($file);
		}
		file_put_contents($initialPath, json_encode($kit->export()));
	}
}