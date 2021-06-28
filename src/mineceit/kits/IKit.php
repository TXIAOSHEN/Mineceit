<?php

declare(strict_types=1);

namespace mineceit\kits;

use mineceit\game\behavior\kits\IKitHolderEntity;
use mineceit\kits\info\KnockbackInfo;
use mineceit\kits\info\MiscKitInfo;

interface IKit{

	/**
	 * @param IKitHolderEntity $entity
	 *
	 * @return bool
	 *
	 * Gives the kit to the holder entity.
	 */
	public function giveTo(IKitHolderEntity $entity) : bool;

	/**
	 * @return KnockbackInfo
	 *
	 * Gets the knockback information of the kit.
	 */
	public function getKnockbackInfo() : KnockbackInfo;

	/**
	 * @return MiscKitInfo
	 *
	 * Gets the misc kit information.
	 */
	public function getMiscKitInfo() : MiscKitInfo;

	/**
	 * @return string
	 *
	 * Gets the name of the kit.
	 */
	public function getName() : string;

	/**
	 * @return string
	 *
	 * Gets the localized name of the kit.
	 */
	public function getLocalizedName() : string;

	/**
	 * @return array
	 *
	 * Exports the kit to an array.
	 */
	public function export() : array;


	/**
	 * @param $kit
	 *
	 * @return bool
	 *
	 * Determines if one kit is equivalent to another.
	 */
	public function equals($kit) : bool;
}