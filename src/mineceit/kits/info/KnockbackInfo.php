<?php

declare(strict_types=1);

namespace mineceit\kits\info;


class KnockbackInfo{
	/** @var float */
	private $horizontalKB;
	/** @var float */
	private $verticalKB;
	/** @var int */
	private $speed;

	public function __construct(float $horizontalKB = 0.4, float $verticalKB = 0.4, int $speed = 10){
		$this->horizontalKB = $horizontalKB;
		$this->verticalKB = $verticalKB;
		$this->speed = $speed;
	}

	/**
	 * @param $data
	 *
	 * @return KnockbackInfo
	 *
	 * Decodes the data into a knockback info structure.
	 */
	public static function decode($data) : KnockbackInfo{
		if(is_array($data)
			&& isset($data['xkb'], $data['ykb'], $data['speed'])){
			return new KnockbackInfo(
				$data['xkb'], $data['ykb'], $data['speed']);
		}
		return new KnockbackInfo();
	}

	public function getHorizontalKb() : float{
		return $this->horizontalKB;
	}

	public function setHorizontalKb(float $kb) : void{
		$this->horizontalKB = $kb;
	}

	public function getVerticalKb() : float{
		return $this->verticalKB;
	}

	public function setVerticalKb(float $kb) : void{
		$this->verticalKB = $kb;
	}

	/**
	 * @return int
	 *
	 * Gets the attack delay.
	 */
	public function getSpeed() : int{
		return $this->speed;
	}

	/**
	 * @param int $speed
	 *
	 * Sets the attack delay of the kit.
	 */
	public function setSpeed(int $speed) : void{
		$this->speed = $speed;
	}

	/**
	 * @return array
	 *
	 * Exports the knockback info to an array.
	 */
	public function export() : array{
		return [
			'xkb' => $this->horizontalKB,
			'ykb' => $this->verticalKB,
			'speed' => $this->speed
		];
	}
}