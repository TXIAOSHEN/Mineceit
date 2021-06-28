<?php

declare(strict_types=1);

namespace mineceit\guild;

use mineceit\MineceitCore;
use mineceit\player\MineceitPlayer;
use pocketmine\utils\TextFormat;

class Guild{
	/* @var string */
	private $name; // cannot be space

	/* @var string */
	private $leader;

	/* @var string[]|array */
	private $officer;

	/* @var string[]|array */
	private $member;

	/* @var string */
	private $profileImage;

	/* @var string */
	private $guildTag;

	/* @var int */
	private $maxMember;

	/* @var Agent[]|array */
	private $tankAgent;

	/* @var Agent[]|array */
	private $damageAgent;

	/* @var Agent[]|array */
	private $supportAgent;

	/* @var int */
	private $resource;

	/* @var int */
	private $exp;

	/* @var string[]|array */
	private $joinRequest;

	/* @var string[]|array */
	private $banList;

	/* @var string[]|array */
	private $lastTimeLogin;

	/* @var string[]|array */
	private $perk;

	// Todo fixed construct if save object work fine

	public function __construct(string $name, string $leader, string $profileImage, string $guildTag){
		$this->name = $name;
		$this->leader = $leader;
		$this->officer = [];
		$this->member = [$this->leader];
		$this->profileImage = $profileImage;
		$this->guildTag = $guildTag;
		$this->maxMember = 10;
		$this->tankAgent = [];
		$this->tankAgent[] = new Agent('Golem');
		$this->tankAgent[] = new Agent('Guardian');
		$this->damageAgent = [];
		$this->damageAgent[] = new Agent('Swordman');
		$this->damageAgent[] = new Agent('Sniper');
		$this->supportAgent = [];
		$this->supportAgent[] = new Agent('Healer');
		$this->resource = 0;
		$this->exp = 0;
		$this->joinRequest = [];
		$this->banList = [];
		$this->lastTimeLogin = [];
		$this->lastTimeLogin[$leader] = (new \DateTime('NOW'))->format('d-m-Y');
		$this->perk = [];
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getGuildTag() : string{
		return $this->guildTag;
	}

	/**
	 * @return string
	 */
	public function getLeader() : string{
		return $this->leader;
	}

	/**
	 * @return int
	 */
	public function getMaxMember() : int{
		return $this->maxMember;
	}

	/**
	 * @param bool $flag
	 *
	 * @return int
	 */
	public function getResource(bool $flag = false){
		return ($flag) ? TextFormat::LIGHT_PURPLE . (string) $this->resource . TextFormat::RESET : $this->resource;
	}

	/**
	 * @param bool $flag
	 *
	 * @return int
	 */
	public function getExp(bool $flag = false){
		return ($flag) ? TextFormat::GREEN . (string) $this->exp . TextFormat::RESET : $this->exp;
	}

	/**
	 * @param int $amount
	 */
	public function addExp(int $amount) : void{
		$this->exp = $this->exp + $amount;
	}

	/**
	 * @param string $perkName
	 */
	public function addPerk(string $perkName) : void{
		if(!isset($this->perk[$perkName]))
			$this->perk[$perkName] = true;
	}

	/**
	 * @param string $perkName
	 */
	public function removePerk(string $perkName) : void{
		if(isset($this->perk[$perkName]))
			unset($this->perk[$perkName]);
	}

	/**
	 * @param string $perkName
	 *
	 * @return bool
	 */
	public function isPerk(string $perkName) : bool{
		if(isset($this->perk[$perkName])) return true;
		return false;
	}

	/**
	 * @param bool $int
	 *
	 * @return array|int|string[]
	 */
	public function getMember(bool $int = false){
		return ($int) ? count($this->member) : $this->member;
	}

	/**
	 * @param bool $int
	 *
	 * @return array|int|string[]
	 */
	public function getJoinRequest(bool $int = false){
		return ($int) ? count($this->joinRequest) : $this->joinRequest;
	}

	/**
	 * @param bool $int
	 *
	 * @return array|int|string[]
	 */
	public function getBanList(bool $int = false){
		return ($int) ? count($this->banList) : $this->banList;
	}

	/**
	 * @param int  $n
	 * @param bool $flag
	 *
	 * @return array|int|Agent[]
	 */
	public function getAgent(int $n, bool $flag = false){
		$result = [];

		switch($n){
			case 0:
				$result = ($flag) ? count($this->tankAgent) : $this->tankAgent;
				break;
			case 1:
				$result = ($flag) ? count($this->damageAgent) : $this->damageAgent;
				break;
			case 2:
				$result = ($flag) ? count($this->supportAgent) : $this->supportAgent;
				break;
		}
		return $result;
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return bool
	 */
	public function isInGuild(MineceitPlayer $player) : bool{
		return in_array($player->getName(), $this->member);
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return bool
	 */
	public function isInJoinRequest(MineceitPlayer $player) : bool{
		return in_array($player->getName(), $this->joinRequest);
	}

	/**
	 * @param MineceitPlayer|string $player |$task
	 */
	public function addJoinRequest(MineceitPlayer $player) : void{
		if(!in_array($player->getName(), $this->joinRequest) && !in_array($player->getName(), $this->banList)){
			$player->setGuild($this->name);
			$player->setGuildRegion(MineceitCore::getRegion());
			$this->joinRequest[] = $player->getName();
			// TODO MSG SUCCESS SENDING REQUEST
		}else{
			// TODO SENDING CANNOT JOIN REQUEST DUE TO BAN OR ALREADY JOIN
		}
	}

	/**
	 * @param string $player
	 */
	public function removeJoinRequest(string $player) : void{
		$key = array_search($player, $this->joinRequest);
		if($key !== false) unset($this->joinRequest[$key]);
	}

	/**
	 * @param string $player
	 */
	public function addMember(string $player) : void{
		$key = array_search($player, $this->joinRequest);
		if($key !== false) unset($this->joinRequest[$key]);
		if(in_array($player, $this->member) === false){
			$this->member[] = $player;
			$this->lastLogin[$player] = (new \DateTime('NOW'))->format('d-m-Y');
		}
	}

	/**
	 * @param string $player
	 */
	public function changeToOfficer(string $player) : void{
		$key = array_search($player, $this->officer);
		if($key === false){
			$this->officer[] = $player;
		}
	}

	/**
	 * @param string $player
	 */
	public function ban(string $player) : void{
		$this->kick($player);
		$key = array_search($player, $this->banList);
		if($key === false){
			$this->banList[] = $player;
		}
	}

	/**
	 * @param string $player
	 */
	public function kick(string $player) : void{
		$this->changeToMember($player);
		$key = array_search($player, $this->member);
		if($key !== false)
			unset($this->member[$key]);
		if(isset($this->lastTimeLogin[$player]))
			unset($this->lastTimeLogin[$player]);
	}

	/**
	 * @param string $player
	 */
	public function changeToMember(string $player) : void{
		$key = array_search($player, $this->officer);
		if($key !== false){
			unset($this->officer[$key]);
		}
	}

	/**
	 * @param string $player
	 */
	public function unBan(string $player) : void{
		$key = array_search($player, $this->banList);
		if($key !== false){
			unset($this->banList[$key]);
		}
	}

	/**
	 * @param MineceitPlayer $player
	 */
	public function dailyReward(MineceitPlayer $player) : void{
		$name = $player->getName();
		if(isset($this->lastTimeLogin[$name])){
			$dateNow = (new \DateTime('NOW'))->format('d-m-Y');
			if($this->lastTimeLogin[$name] !== $dateNow){
				// guild exp needed, coin, shard, exp, resource
				$dailyRewardPool = [[25000, 550, 275, 275, 550],
					[22500, 500, 250, 250, 500],
					[20000, 450, 225, 225, 450],
					[17500, 400, 200, 200, 400],
					[15000, 350, 175, 175, 350],
					[12500, 300, 150, 150, 300],
					[10000, 250, 125, 125, 250],
					[7500, 200, 100, 100, 200],
					[5000, 150, 75, 75, 150],
					[2500, 100, 50, 50, 100],
					[-1, 50, 25, 25, 50]];
				foreach($dailyRewardPool as $reward){
					if($this->exp >= $reward[0]){
						$player->getStatsInfo()->addCoins($reward[1]);
						$player->getStatsInfo()->addShards($reward[2]);
						$player->getStatsInfo()->addExp($reward[3]);
						$this->addResource($reward[4]);
						break;
					}
				}
				$this->lastLogin[$name] = (new \DateTime('NOW'))->format('d-m-Y');
			}else{
				// TODO MSG NEED TO WAIT FOR 24 HOUR
			}
		}
	}

	/**
	 * @param int $amount
	 */
	public function addResource(int $amount) : void{
		$this->resource = $this->resource + $amount;
	}
}
