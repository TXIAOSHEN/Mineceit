<?php

declare(strict_types=1);

namespace mineceit;

use mineceit\discord\DiscordUtil;
use mineceit\game\leaderboard\Leaderboards;
use mineceit\player\MineceitPlayer;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MineceitTask extends Task{

	/* @var Server */
	private $server;

	/* @var MineceitCore */
	private $core;

	/** @var int */
	private $currentTick;

	/** @var int */
	private $countdown;

	/** @var int */
	private $leaderboardUpdateTicks;
	/** @var int */
	private $leaderboardReloadTicks;

	/** @var Leaderboards */
	private $leaderboards;

	public function __construct(MineceitCore $core){
		$this->core = $core;
		$this->server = $core->getServer();
		$this->currentTick = 0;
		$this->countdown = 10;
		$this->leaderboards = MineceitCore::getLeaderboards();
		$this->leaderboardUpdateTicks = MineceitUtil::secondsToTicks(2);
		$this->leaderboardReloadTicks = MineceitUtil::minutesToTicks(5);
	}

	/**
	 * Actions to execute when run
	 *
	 * @param int $currentTick
	 *
	 * @return void
	 */
	public function onRun(int $currentTick){
		$sec = MineceitUtil::secondsToTicks(1);
		$restarthours = MineceitUtil::hoursToTicks(4);

		$this->updateLeaderboards();
		$this->updatePlayers();

		MineceitCore::getEventManager()->update();
		MineceitCore::getDuelHandler()->update();
		MineceitCore::getBotHandler()->update();
		MineceitCore::getReplayManager()->update();
		MineceitCore::getPartyManager()->getEventManager()->update();

		if($this->currentTick % $sec === 0){
			$this->updateAuctionHouse();
			MineceitCore::getDeleteBlocksHandler()->update();
		}

		if(($this->currentTick !== 0) && ($this->currentTick % $restarthours === 0)){
			$this->core->getScheduler()->scheduleRepeatingTask(new ClosureTask(
				function(int $currentTick2) : void{
					if($this->countdown > 0) MineceitUtil::broadcastMessage('Server restart in ' . TextFormat::LIGHT_PURPLE . $this->countdown);
					$this->countdown--;
					if($this->countdown === -1){
						$playerHandler = MineceitCore::getPlayerHandler();
						$this->server->setConfigBool("white-list", true);
						foreach($this->server->getOnlinePlayers() as $player){
							if($player instanceof MineceitPlayer) $playerHandler->savePlayerData($player);
							$player->transfer("zeqa.net", 19132);
						}
					}elseif($this->countdown === -90){
						$this->server->shutdown();
					}
				}
			), 20);
		}

		$this->currentTick++;
	}

	/**
	 * Updates the leaderbaord.
	 */
	private function updateLeaderboards() : void{
		if($this->currentTick % $this->leaderboardReloadTicks === 0
			|| $this->currentTick === 0){
			$this->leaderboards->reloadEloLeaderboards();
			$this->leaderboards->reloadStatsLeaderboards();
			$this->broadcastMessage();
			$this->sendOnlinePlayers();
		}

		if($this->currentTick % $this->leaderboardUpdateTicks === 0){
			$this->leaderboards->updateHolograms();
		}
	}

	/**
	 * Boardcast message.
	 */
	private function broadcastMessage() : void{
		$rnd = rand(0, 3);
		if($rnd === 0){
			MineceitUtil::broadcastMessage('Join our discord at' . TextFormat::LIGHT_PURPLE . ' discord.gg/zeqa');
		}elseif($rnd === 1){
			MineceitUtil::broadcastMessage('Donate for a rank at' . TextFormat::LIGHT_PURPLE . ' store.zeqa.net');
		}elseif($rnd === 2){
			MineceitUtil::broadcastMessage('Vote for ' . TextFormat::DARK_GREEN . 'Voter' . TextFormat::RESET . ' rank at' . TextFormat::GREEN . ' gg.gg/zeqavote');
		}else{
			MineceitUtil::broadcastMessage('This server is hosted by ' . TextFormat::LIGHT_PURPLE . 'Apex Hosting');
		}
	}

	/**
	 * Send online-players to discord.
	 */
	private function sendOnlinePlayers() : void{
		$title = DiscordUtil::boldText("Online-Players");
		$players = $this->server->getOnlinePlayers();
		$count = count($players);
		if($count !== 0){
			$playerNames = [];
			foreach($players as $player){
				if($player instanceof MineceitPlayer){
					$playerNames[] = $player->getName();
				}
			}
			$list = implode(", ", $playerNames);
			$description = "  " . DiscordUtil::boldText("Players:") . " {$count}\n " . DiscordUtil::boldText("Lists:") . " {$list}";
			DiscordUtil::sendOnlinePlayers($title, $description, DiscordUtil::BLUE);
		}
	}

	/**
	 *
	 * Updates the players in the server.
	 */
	private function updatePlayers() : void{
		$players = $this->server->getOnlinePlayers();
		foreach($players as $player){
			if($player instanceof MineceitPlayer){
				if($this->currentTick % 20 === 0){
					$player->update();
				}
				$player->updateCps();
				$player->updateNameTag();
			}
		}
	}

	/**
	 * Updates the auction house item
	 */
	private function updateAuctionHouse() : void{
		$auctionHouse = MineceitCore::getAuctionHouse();
		$auctionHouse->updateAuctionHouse();
	}
}
