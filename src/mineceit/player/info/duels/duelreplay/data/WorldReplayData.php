<?php

declare(strict_types=1);

namespace mineceit\player\info\duels\duelreplay\data;

use mineceit\arenas\DuelArena;
use pocketmine\block\Block;

class WorldReplayData extends ReplayData{
	/*
	 * @var array
	 * The temp array for the block data.
	 */
	private $tempBlockData;
	/* @var array
	 * For determining when a block was updated -> eg. break, place, etc.
	 */
	private $blockTimes;

	/* @var DuelArena
	 * For determining the arena.
	 */
	private $arena;

	/* @var bool
	 * Determines if the duel was ranked.
	 */
	private $ranked;

	public function __construct(DuelArena $arena, bool $ranked){
		$this->arena = $arena;
		$this->blockTimes = [];
		$this->tempBlockData = [];
		$this->ranked = $ranked;
	}

	/**
	 * @param int       $tick
	 * @param Block|int $block
	 * @param bool      $break
	 */
	public function setBlockAt(int $tick, Block $block, bool $break = false) : void{

		if($break){
			$newBlockData = new BlockData(Block::get(Block::AIR), $tick);
			$newBlockData->setPosition((int) $block->x, (int) $block->y, (int) $block->z);
		}else{
			$newBlockData = new BlockData($block, $tick);
		}

		$strPos = $newBlockData->getPositionAsString();
		if(isset($this->tempBlockData[$strPos])){
			$prevBlockData = $this->tempBlockData[$strPos];
		}else{
			$prevBlockData = new BlockData(Block::get(Block::AIR), 0);
			$prevBlockData->setPosition((int) $block->x, (int) $block->y, (int) $block->z);
		}
		$prevBlockData->setNextBlockData($newBlockData);
		$newBlockData->setPrevBlockData($prevBlockData);
		$this->blockTimes[$prevBlockData->getTickUpdated()][$strPos] = $prevBlockData;
		$this->tempBlockData[$strPos] = $newBlockData;
		$this->blockTimes[$tick][$strPos] = $newBlockData;
	}

	/**
	 * @param int  $tick
	 * @param bool $approximate - Gets the blocks that are less than this tick, marking this true is more expensive.
	 *
	 * Gets the blocks at a particular tick.
	 *
	 * @return array
	 */
	public function getBlocksAt(int $tick, bool $approximate = false) : array{
		if(!$approximate){
			if(isset($this->blockTimes[$tick]))
				return $this->blockTimes[$tick];
			return [];
		}

		$outputBlocks = [];
		foreach($this->tempBlockData as $tempBlockPos => $blockData){
			if($blockData instanceof BlockData){
				$position = $blockData->getPosition();

				$currentBlockData = $blockData;
				while(
					$currentBlockData !== null
					&& $currentBlockData->getTickUpdated() > 0
					&& $currentBlockData->getTickUpdated() > $tick
				){
					$currentBlockData = $currentBlockData->getPrevBlockData();
				}

				if($currentBlockData === null){
					$inputBlockData = new BlockData(Block::get(Block::AIR), $tick);
					$inputBlockData->setPosition($position->x, $position->y, $position->z);
					$outputBlocks[$tempBlockPos] = $inputBlockData;
					continue;
				}
				$outputBlocks[$tempBlockPos] = $currentBlockData;
			}
		}
		return $outputBlocks;
	}

	/**
	 * @return DuelArena
	 */
	public function getArena() : DuelArena{
		return $this->arena;
	}

	/**
	 * @return bool
	 */
	public function isRanked() : bool{
		return $this->ranked;
	}
}
