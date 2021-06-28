<?php

declare(strict_types=1);

namespace mineceit\kits;

use mineceit\kits\info\KnockbackInfo;
use mineceit\kits\types\Boxing;
use mineceit\kits\types\Build;
use mineceit\kits\types\BuildUHC;
use mineceit\kits\types\Combo;
use mineceit\kits\types\Fist;
use mineceit\kits\types\Gapple;
use mineceit\kits\types\Knock;
use mineceit\kits\types\MLGRush;
use mineceit\kits\types\NoDebuff;
use mineceit\kits\types\OITC;
use mineceit\kits\types\Resistance;
use mineceit\kits\types\Soup;
use mineceit\kits\types\Sumo;
use mineceit\MineceitCore;
use pocketmine\utils\Config;

class KitsManager{
	public const GAPPLE = 'gapple';
	public const SUMO = 'sumo';
	public const FIST = 'fist';
	public const NODEBUFF = 'nodebuff';
	public const COMBO = 'combo';
	public const BUILDUHC = 'builduhc';
	public const RESISTANCE = 'resistance';
	public const BUILD = 'build';
	public const OITC = 'oitc';
	public const KNOCK = 'knock';
	public const SOUP = 'soup';
	public const MLGRUSH = 'mlgrush';
	public const BOXING = 'boxing';

	/* @var Config */
	private $config;

	/* @var DefaultKit[]|array */
	private $kits = [];

	public function __construct(){
		$this->initConfig();
	}

	/**
	 * Initializes the config file.
	 */
	private function initConfig() : void{
		$this->config = new Config(MineceitCore::getDataFolderPath() . 'kit-knockback.yml', Config::YAML, []);

		if(!is_dir($initialPath = MineceitCore::getDataFolderPath() . "kits/")){
			mkdir($initialPath);
		}

		$this->kits = [
			self::COMBO => new Combo(),
			self::GAPPLE => new Gapple(),
			self::FIST => new Fist(),
			self::NODEBUFF => new NoDebuff(),
			self::SOUP => new Soup(),
			self::SUMO => new Sumo(),
			self::BUILDUHC => new BuildUHC(),
			self::RESISTANCE => new Resistance(),
			self::BUILD => new Build(),
			self::OITC => new OITC(),
			self::KNOCK => new Knock(),
			self::BOXING => new Boxing(),
			self::MLGRUSH => new MLGRush()
		];

		foreach($this->kits as $key => $kit){
			assert($kit instanceof DefaultKit);
			if(!$this->config->exists($key)){
				$this->config->set($key, $kit->export());
				$this->config->save();
			}else{
				$kitData = $this->config->get($key);
				$knockbackInfo = KnockbackInfo::decode($kitData);
				$kit->setKnockbackInfo($knockbackInfo);
				$this->kits[$key] = $kit;
			}
		}
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function isKit(string $name) : bool{
		$kit = $this->getKit($name);
		return $kit !== null;
	}

	/**
	 * @param string $name
	 *
	 * @return DefaultKit|null
	 */
	public function getKit(string $name) : ?DefaultKit{

		if(isset($this->kits[$name])){
			return $this->kits[$name];
		}else{
			foreach($this->kits as $kit){
				if($name === $kit->getName()){
					return $kit;
				}
			}
		}

		return null;
	}

	/**
	 * @param bool $asString
	 *
	 * @return array|string[]|DefaultKit[]
	 */
	public function getKits(bool $asString = false) : array{
		$result = [];
		foreach($this->kits as $kit){
			$name = $kit->getName();
			if($asString === true)
				$result[] = $name;
			else $result[] = $kit;
		}
		return $result;
	}


	/**
	 * @return array|string[]
	 */
	public function getKitsLocal() : array{
		return array_keys($this->kits);
	}
}
