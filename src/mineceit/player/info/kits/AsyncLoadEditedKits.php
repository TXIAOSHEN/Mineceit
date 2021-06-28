<?php

declare(strict_types=1);

namespace mineceit\player\info\kits;


use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncLoadEditedKits extends AsyncTask{

	/** @var string */
	private $playerName;
	/** @var string */
	private $kitsDirectory;

	public function __construct(string $name){
		$this->playerName = $name;
		$this->kitsDirectory = (MineceitCore::getDataFolderPath() . "kits/{$name}/");
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public function onRun(){
		if(!is_dir($this->kitsDirectory)){
			mkdir($this->kitsDirectory);
			$this->setResult(["kits" => []]);
			return;
		}

		$kitsData = [];
		$files = scandir($this->kitsDirectory);
		foreach($files as $kitFile){
			if(strpos($kitFile, ".json") === false){
				continue;
			}
			$kitName = str_replace(".json", "", $kitFile);
			$dataPath = $this->kitsDirectory . $kitFile;
			$this->decodeFile($dataPath, $kitName, $kitsData);
		}
		$this->setResult(["kits" => $kitsData]);
	}

	/**
	 * @param string $dataPath
	 * @param string $kitName
	 * @param array  $data
	 *
	 * Decodes the file.
	 */
	private function decodeFile(string $dataPath, string $kitName, array &$data) : void{
		if(!file_exists($dataPath)){
			return;
		}
		$data[$kitName] = json_decode(
			file_get_contents($dataPath), true);
	}

	public function onCompletion(Server $server){
		$core = $server->getPluginManager()->getPlugin(MineceitCore::getInstance()->getName());
		$result = $this->getResult();

		if($core instanceof MineceitCore && $core->isEnabled()
			&& $result !== null){
			$player = $server->getPlayer($this->playerName);
			if($player instanceof MineceitPlayer && $player->isOnline()){
				$kitsHolder = $player->getKitHolder();
				assert($kitsHolder instanceof PlayerKitHolder);
				$kitsHolder->onLoadFinished($result["kits"]);
			}
		}
	}
}