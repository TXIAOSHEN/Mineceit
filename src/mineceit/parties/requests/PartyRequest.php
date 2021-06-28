<?php

declare(strict_types=1);

namespace mineceit\parties\requests;

use mineceit\parties\MineceitParty;
use mineceit\player\MineceitPlayer;

class PartyRequest{

	/* @var MineceitPlayer */
	private $from;

	/* @var MineceitPlayer */
	private $to;

	/* @var MineceitParty */
	private $party;

	/* @var string */
	private $fromName;

	/* @var string */
	private $toName;

	/* @var string */
	private $texture;

	/* @var string */
	private $toDisplayName;

	/* @var string */
	private $fromDisplayName;


	public function __construct(MineceitPlayer $from, MineceitPlayer $to, MineceitParty $party){
		$this->from = $from;
		$this->to = $to;
		$this->party = $party;
		$this->toName = $to->getName();
		$this->fromName = $from->getName();
		$this->toDisplayName = $to->getDisplayName();
		$this->fromDisplayName = $from->getDisplayName();
		$this->texture = '';
	}

	/**
	 * @return string
	 */
	public function getTexture() : string{
		return $this->texture;
	}

	/**
	 * @return MineceitPlayer
	 */
	public function getFrom() : MineceitPlayer{
		return $this->from;
	}

	/**
	 * @return MineceitPlayer
	 */
	public function getTo() : MineceitPlayer{
		return $this->to;
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
	public function getFromName() : string{
		return $this->fromName;
	}

	/**
	 * @return string
	 */
	public function getToName() : string{
		return $this->toName;
	}


	/**
	 * @return string
	 */
	public function getFromDisplayName() : string{
		return $this->fromDisplayName;
	}

	/**
	 * @return string
	 */
	public function getToDisplayName() : string{
		return $this->toDisplayName;
	}
}
