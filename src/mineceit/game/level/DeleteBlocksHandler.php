<?php

declare(strict_types=1);

namespace mineceit\game\level;

use mineceit\MineceitCore;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Server;

class DeleteBlocksHandler{
	/* @var Server */
	private $server;

	/** int[]|array */
	private $buildBlocks;

	public function __construct(MineceitCore $core){
		$this->server = $core->getServer();
		$this->buildBlocks = [];
	}

	/**
	 * @param Block $block
	 * @param bool  $break
	 */
	public function setBlockBuild(Block $block, bool $break = false) : void{
		$pos = $block->x . ':' . $block->y . ':' . $block->z . ':' . $block->getLevel()->getName();
		if($break && isset($this->buildBlocks[$pos])){
			unset($this->buildBlocks[$pos]);
		}else{
			$this->buildBlocks[$pos] = 10;
		}
	}

	/**
	 * Clears the blocks within a level.
	 */
	public function update() : void{

		foreach($this->buildBlocks as $pos => $sec){
			if($sec <= 0){
				$block = explode(':', $pos);
				$x = $block[0];
				$y = $block[1];
				$z = $block[2];
				$level = $this->server->getLevelByName($block[3]);
				if($level !== null) $level->setBlock(new Vector3($x, $y, $z), Block::get(Block::AIR));
				unset($this->buildBlocks[$pos]);
			}else{
				$this->buildBlocks[$pos]--;
			}
		}
	}
}
