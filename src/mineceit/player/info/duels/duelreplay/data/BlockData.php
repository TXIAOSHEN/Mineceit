<?php

declare(strict_types=1);

namespace mineceit\player\info\duels\duelreplay\data;

use pocketmine\block\Block;
use pocketmine\math\Vector3;

class BlockData{

	/* @var BlockData|null
	 * The previous block before this block
	 * was ever pressed.
	 */
	private $prevBlockData;
	/* @var BlockData|null
	 * The next block after this block.
	 */
	private $nextBlockData;

	/* @ar int
	 * The id of the block.
	 */
	private $id;

	/* @var int
	 * The metadata of the block.
	 */
	private $meta;

	/* @var Vector3
	 * The position of the block.
	 */
	private $position;

	/**
	 * The tick that this block was placed.
	 */
	private $tickUpdated;

	/**
	 * BlockData constructor.
	 *
	 * @param Block $block
	 * @param int   $tickUpdated
	 */
	public function __construct(Block $block, int $tickUpdated){
		if($block instanceof Block){
			$this->position = $block->asVector3();
			$this->meta = $block->getDamage();
			$this->id = $block->getId();
		}else{
			$this->id = is_int($block) ? (int) $block : 0;
			$this->meta = 0;
			$this->position = new Vector3(0.0, 0.0, 0.0);
		}

		$this->tickUpdated = $tickUpdated;
		$this->prevBlockData = null;
		$this->nextBlockData = null;
	}

	/**
	 * @return BlockData|null
	 * Returns the previous block data at the same position.
	 */
	public function getPrevBlockData() : ?BlockData{
		return $this->prevBlockData;
	}

	public function setPrevBlockData(BlockData $blockData) : void{
		$this->prevBlockData = $blockData;
	}

	/**
	 * @return BlockData|null
	 * Returns the next block data at the same position.
	 */
	public function getNextBlockData() : ?BlockData{
		return $this->nextBlockData;
	}

	public function setNextBlockData(BlockData $blockData) : void{
		$this->nextBlockData = $blockData;
	}

	/**
	 * @return int
	 * The current tick updated.
	 */
	public function getTickUpdated() : int{
		return $this->tickUpdated;
	}

	/**
	 * @return Block
	 */
	public function getBlock() : Block{
		return Block::get($this->id, $this->meta);
	}

	/**
	 * @return Vector3
	 */
	public function getPosition() : Vector3{
		return $this->position;
	}

	/**
	 * Sets the position of the block data.
	 *
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 */
	public function setPosition(float $x, float $y, float $z){
		$this->position = new Vector3($x, $y, $z);
	}

	/**
	 * @return string
	 */
	public function getPositionAsString() : string{
		$x = (int) $this->position->x;
		$y = (int) $this->position->y;
		$z = (int) $this->position->z;
		return "{$x}:{$y}:{$z}";
	}
}
