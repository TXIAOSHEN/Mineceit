<?php

declare(strict_types=1);

namespace mineceit\player\ban;

use mineceit\data\ban\AsyncLoadBanData;
use mineceit\data\ban\AsyncSaveBanData;
use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;

class BanHandler{
	/* @var Server */
	private $server;

	/** @var array */
	private $banList;

	public function __construct(MineceitCore $core){
		$this->server = $core->getServer();
		$this->banList = [];
		$task = new AsyncLoadBanData();
		$this->server->getAsyncPool()->submitTask($task);
	}

	/**
	 *
	 * Sets the banlist.
	 *
	 * @param array $banlist
	 */
	public function setBanList(array $banlist) : void{
		$this->banList = $banlist;
	}

	/**
	 *
	 * Adds the banlist.
	 *
	 * @param array $banlist
	 */
	public function addBanList(array $banlist) : void{
		foreach($this->banList as $key => $ban){
			if($ban[1] === $banlist[1]){
				$this->removeBanList($banlist[1]);
				break;
			}
		}
		$task = new AsyncSaveBanData($banlist);
		$this->server->getAsyncPool()->submitTask($task);
		$this->banList[] = $banlist;
	}

	/**
	 *
	 * Remove player from the banlist.
	 *
	 * @param string $player
	 */
	public function removeBanList(string $player) : void{
		foreach($this->banList as $key => $ban){
			if($ban[1] === $player){
				$task = new AsyncSaveBanData($this->banList[$key], false);
				$this->server->getAsyncPool()->submitTask($task);
				unset($this->banList[$key]);
				break;
			}
		}
	}

	/**
	 *
	 * Is player in the banlist.
	 *
	 * @param MineceitPlayer $player
	 *
	 * @return array
	 */
	public function isInBanList(MineceitPlayer $player) : array{
		foreach($this->banList as $key => $ban){
			if($ban[1] === strtolower($player->getName())){
				return $ban;
			}
		}
		return [];
	}
}
