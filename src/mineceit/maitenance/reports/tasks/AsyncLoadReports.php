<?php

declare(strict_types=1);

namespace mineceit\maitenance\reports\tasks;

use mineceit\MineceitCore;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncLoadReports extends AsyncTask{

	/** @var string */
	private $file;

	public function __construct(string $file){
		$this->file = $file;
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public function onRun(){

		$file = fopen($this->file, 'r');

		$result = [];

		while(($data = fgetcsv($file)) !== false){
			$result[] = $data;
		}

		fclose($file);

		$this->setResult($result);
	}

	public function onCompletion(Server $server){
		$core = $server->getPluginManager()->getPlugin('Mineceit');

		$result = $this->getResult();

		if($core instanceof MineceitCore && $core->isEnabled()){

			$reportManager = MineceitCore::getReportManager();

			$reportManager->loadReports($result);
		}
	}
}
