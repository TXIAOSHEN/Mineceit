<?php

declare(strict_types=1);

namespace mineceit\player\info\ips\tasks;

use mineceit\MineceitCore;
use mineceit\player\info\ips\IPInfo;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncCollectInfo extends AsyncTask{

	/** @var string */
	private $name;

	/** @var string */
	private $ip;

	public function __construct(string $name, string $ip){
		$this->name = $name;
		$this->ip = $ip;
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public function onRun(){

		// IP Locate = https://iplocate.io/api/lookup/{ip}"
		// Free Geo IP = https://freegeoip.app/json/{ip}

		$context = stream_context_create([
			"ssl" => [
				"verify_peer" => false,
				"verify_peer_name" => false
			]
		]);

		$ip = $this->ip;

		try{
			$contents = file_get_contents("https://iplocate.io/api/lookup/{$ip}", false, $context);
		}catch(\Exception $e){
			try{
				$contents = file_get_contents("https://freegeoip.app/json{$ip}", false, $context);
			}catch(\Exception $e){
				return;
			}
		}

		if(!isset($contents)){
			return;
		}

		$result = json_decode($contents, true);
		if(isset($result['country_name'])){
			$result['country'] = $result['country_name'];
		}
		if(isset($result["time_zone"], $result['country'])){
			$country = (string) $result['country'];
			$this->setResult([
				"country" => $country,
				"timezone" => $result["time_zone"]
			]);
		}
	}

	public function onCompletion(Server $server){
		$core = $server->getPluginManager()->getPlugin('Mineceit');
		$result = $this->getResult();
		if($core instanceof MineceitCore && $core->isEnabled()){
			$playerManager = MineceitCore::getPlayerHandler();
			$ipManager = $playerManager->getIPManager();
			if($result !== null){
				$timeZone = (string) $result['timezone'];
				$country = (string) $result['country'];
				$ipInfo = new IPInfo($this->ip, $timeZone, $country);
				$ipManager->addInfo($ipInfo);
			}
		}
	}
}
