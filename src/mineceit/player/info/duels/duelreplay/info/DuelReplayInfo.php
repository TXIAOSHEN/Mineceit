<?php

declare(strict_types=1);

namespace mineceit\player\info\duels\duelreplay\info;

use mineceit\kits\DefaultKit;
use mineceit\player\info\duels\duelreplay\data\PlayerReplayData;
use mineceit\player\info\duels\duelreplay\data\WorldReplayData;

class DuelReplayInfo{
	/* @var PlayerReplayData */
	private $playerAData;

	/* @var PlayerReplayData */
	private $playerBData;

	/* @var WorldReplayData */
	private $worldData;

	/* @var int */
	private $endTick;

	/* @var DefaultKit $kit
	 * Kit used during the duel.
	 */
	private $kit;

	/**
	 * DuelReplay constructor.
	 *
	 * @param int              $endTick -> The ending tick;
	 * @param PlayerReplayData $p1Data ;
	 * @param PlayerReplayData $p2Data ;
	 * @param WorldReplayData  $worldData ;
	 * @param DefaultKit       $kit ;
	 */
	public function __construct(int $endTick, PlayerReplayData $p1Data, PlayerReplayData $p2Data, WorldReplayData $worldData, DefaultKit $kit){
		$this->endTick = $endTick;
		$this->playerAData = $p1Data;
		$this->playerBData = $p2Data;
		$this->worldData = $worldData;
		$this->kit = $kit;
	}

	/**
	 * @return PlayerReplayData
	 */
	public function getPlayerAData() : PlayerReplayData{
		return $this->playerAData;
	}

	/**
	 * @return PlayerReplayData
	 */
	public function getPlayerBData() : PlayerReplayData{
		return $this->playerBData;
	}

	/**
	 * @return WorldReplayData
	 */
	public function getWorldData() : WorldReplayData{
		return $this->worldData;
	}

	/**
	 * @return int
	 */
	public function getEndTick() : int{
		return $this->endTick;
	}

	/**
	 * @return DefaultKit
	 */
	public function getKit() : DefaultKit{
		return $this->kit;
	}
}
