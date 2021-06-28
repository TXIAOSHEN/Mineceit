<?php

declare(strict_types=1);

namespace mineceit\parties\events\types\match\data;

use mineceit\parties\MineceitParty;

class QueuedParty{

	/* @var string */
	private $queue;

	/* @var MineceitParty */
	private $party;

	/* @var int */
	private $size;

	public function __construct(MineceitParty $party, string $queue){
		$this->queue = $queue;
		$this->party = $party;
		$this->size = $party->getPlayers(true);
	}

	/**
	 * @return MineceitParty
	 */
	public function getParty() : MineceitParty{
		return $this->party;
	}

	/**
	 * @return string
	 */
	public function getQueue() : string{
		return $this->queue;
	}

	/**
	 * @return int
	 */
	public function getSize() : int{
		return $this->size;
	}
}
