<?php

declare(strict_types=1);

namespace mineceit\player\info\ips;

use mineceit\MineceitCore;
use mineceit\player\info\ips\tasks\AsyncCheckIP;
use mineceit\player\info\ips\tasks\AsyncCollectInfo;
use mineceit\player\info\ips\tasks\AsyncLoadIPs;
use mineceit\player\info\ips\tasks\AsyncSaveIPs;
use mineceit\player\MineceitPlayer;
use pocketmine\Server;

class IPManager{

	/** @var array */
	private $safeIps;

	/** @var MineceitCore */
	private $core;

	/** @var Server */
	private $server;

	/** @var string */
	private $ipsDir;
	/** @var string */
	private $aliasDir;

	/** @var string */
	private $safeIPsFile;
	/** @var string */
	private $tzFile;
	/** @var string */
	private $aliasFile;

	/** @var array|IPInfo[] */
	private $ipsInfo;
	/** @var array */
	private $aliasLocIps;

	public function __construct(MineceitCore $core){
		$this->safeIps = [];
		$this->server = $core->getServer();
		$this->core = $core;
		$this->ipsInfo = [];
		$this->aliasLocIps = [];

		$this->ipsDir = $core->getDataFolder() . 'ips/';
		$this->aliasDir = $core->getDataFolder() . 'aliases/';

		$this->initFile();
	}

	private function initFile() : void{

		if(!is_dir($this->ipsDir)){
			mkdir($this->ipsDir);
		}

		$this->safeIPsFile = $this->ipsDir . 'safe-ips.txt';
		$this->tzFile = $this->ipsDir . 'Timezones.csv';
		$this->aliasFile = $this->aliasDir . 'ip-aliases.yml';
		$task = new AsyncLoadIPs($this->safeIPsFile, $this->aliasFile, $this->tzFile);
		$this->server->getAsyncPool()->submitTask($task);
	}

	/**
	 * @param $safeIps
	 * @param $aliasLocIps
	 * @param $tzIps
	 *
	 * Loads the ips.
	 */
	public function loadIps($safeIps, $aliasLocIps, $tzIps) : void{
		$this->safeIps = (array) $safeIps;
		$this->aliasLocIps = (array) $aliasLocIps;
		$this->ipsInfo = (array) $tzIps;
	}

	/**
	 * Saves ips to the files.
	 */
	public function save() : void{

		$exportedIpsInfo = [];
		$this->saveIPInfo($exportedIpsInfo);

		$task = new AsyncSaveIPs($this->safeIPsFile,
			$this->safeIps,
			$this->tzFile,
			$exportedIpsInfo,
			$this->aliasFile,
			$this->aliasLocIps);

		$this->server->getAsyncPool()->submitTask($task);
	}

	/**
	 * @param array $arr
	 *
	 * Saves the ip info to an array which is used for export.
	 */
	private function saveIPInfo(array &$arr) : void{
		foreach($this->ipsInfo as $ipInfo){
			$arr[$ipInfo->getIP()] = $ipInfo->export();
		}
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return void
	 *
	 * Checks the player's ip whether it is safe or not.
	 */
	public function checkIPSafe(MineceitPlayer $player) : void{

		$ip = $player->getAddress();
		$name = $player->getName();

		$searched = array_flip($this->safeIps);

		if(!isset($searched[$ip])){
			MineceitCore::getPlayerHandler()->updateAliases($player);
			return;
		}

		$task = new AsyncCheckIP($name, $ip);
		$this->server->getAsyncPool()->submitTask($task);
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $saveIP
	 *
	 * @return array
	 *
	 * Gets the information of the player.
	 */
	public function collectInfo(MineceitPlayer $player, bool $saveIP = true) : array{

		$ip = $player->getAddress();
		$name = $player->getName();

		$searched = array_flip($this->safeIps);
		// Saves the ip to the safe ips.
		if(!isset($searched[$ip]) && $saveIP){
			$this->safeIps[] = $ip;
		}

		// Saves them to the aliases.
		$locIP = $this->getHostAddressOf($ip);
		$data = [];
		if(isset($this->aliasLocIps[$locIP])){
			$data = (array) $this->aliasLocIps[$locIP];
		}

		$searched = array_flip($data);
		if(!isset($searched[$name])){
			$data[] = $name;
		}

		$this->aliasLocIps[$locIP] = $data;

		if(!isset($this->ipsInfo[$ip])){
			$task = new AsyncCollectInfo($name, $ip);
			$this->server->getAsyncPool()->submitTask($task);
		}

		return ['loc-ip' => $locIP, 'alias-ips' => $this->aliasLocIps];
	}

	/**
	 * @param string $ip
	 *
	 * @return string
	 *
	 * Generates the host address.
	 */
	private function getHostAddressOf(string $ip) : string{
		$exploded = array_chunk(explode(".", $ip), 3);
		return implode(".", $exploded[0]);
	}

	/**
	 * @param string $ip
	 *
	 * @return IPInfo|null
	 *
	 * Gets the info of the given ip.
	 */
	public function getInfo(string $ip) : ?IPInfo{
		if(isset($this->ipsInfo[$ip])){
			return $this->ipsInfo[$ip];
		}
		return null;
	}

	/**
	 * @param IPInfo $info
	 *
	 * Sets the ip as collected.
	 */
	public function addInfo(IPInfo $info){
		$this->ipsInfo[$info->getIP()] = $info;
	}
}
