<?php

declare(strict_types=1);

namespace mineceit\guild;

use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;

class GuildManager{
	/* @var Guild[]|array */
	private $guilds;


	public function __construct(){
		$this->guilds = [];
		$this->loadGuildData();
	}

	/**
	 * Load the current Guild Data
	 */
	public function loadGuildData() : void{
		$dataPath = MineceitCore::getDataFolderPath() . 'guild/userGuild/';
		if(!is_dir(MineceitCore::getDataFolderPath() . 'guild/')){
			mkdir(MineceitCore::getDataFolderPath() . 'guild/');
			mkdir(MineceitCore::getDataFolderPath() . 'guild/userGuild/');
		}

		$files = scandir($dataPath);
		foreach($files as $file){
			$ext = pathinfo($dataPath . $file, PATHINFO_EXTENSION);
			if($ext != 'bin' || !file_exists($dataPath . $file)) continue;
			$objData = file_get_contents($dataPath . $file);
			$obj = unserialize($objData);
			$this->guilds[$obj->getName()] = $obj;
		}
	}

	/**
	 * Save the current Guild Data
	 */
	public function saveGuildData() : void{
		$dataPath = MineceitCore::getDataFolderPath() . 'guild/userGuild/';
		if(!is_dir($dataPath))
			mkdir($dataPath);
		$guilds = $this->guilds;
		foreach($guilds as $guild){
			file_put_contents($dataPath . strtolower($guild->getName()) . '.bin', serialize($guild));
		}
	}

	/**
	 * add Guild Data
	 *
	 * @param Guild $guild
	 */
	public function addGuild(Guild $guild) : void{
		if(!isset($this->guilds[$guild->getName()])){
			$this->guilds[$guild->getName()] = $guild;
		}
	}

	/**
	 * add Guild Data
	 *
	 * @param Guild $guild
	 */
	public function removeGuild(Guild $guild) : void{
		if(isset($this->guilds[$guild->getName()])){
			$members = $guild->getMember();
			foreach($members as $member){
				$guild->kick($member);
			}
			$dataPath = MineceitCore::getDataFolderPath() . 'guild/userGuild/' . strtolower($guild->getName()) . '.bin';
			if(file_exists($dataPath)){
				unlink($dataPath);
			}
			unset($this->guilds[$guild->getName()]);
		}
	}

	/**
	 * @param bool $int
	 *
	 * @return array|int|Guild[]
	 */
	public function getGuilds(bool $int = false){
		return ($int) ? count($this->guilds) : $this->guilds;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return null|Guild[]
	 */
	public function getGuildfromPlayer(MineceitPlayer $player){
		if(isset($this->guilds[$player->getGuild()])){
			return $this->guilds[$player->getGuild()];
		}
		return null;
	}
}
