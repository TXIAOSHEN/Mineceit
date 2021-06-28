<?php

declare(strict_types=1);

namespace mineceit\game\enchantments;

use mineceit\kits\info\KnockbackInfo;
use mineceit\player\MineceitPlayer;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\KnockbackEnchantment as PMKnockbackEnchantment;

class KnockbackEnchantment extends PMKnockbackEnchantment{

	public function onPostAttack(Entity $attacker, Entity $victim, int $enchantmentLevel) : void{
		if($attacker instanceof MineceitPlayer
			&& $victim instanceof MineceitPlayer){
			$attackerKit = $attacker->getKitHolder()->getKit();
			if($attackerKit->equals($victim->getKitHolder()->getKit())){
				$this->applyKnockback($attacker, $victim, $attackerKit->getKnockbackInfo());
				return;
			}
		}
		parent::onPostAttack($attacker, $victim, $enchantmentLevel);
	}

	/**
	 * @param Entity        $entity
	 * @param Entity        $damager
	 * @param KnockbackInfo $info
	 *
	 * A custom knockback function that overrides the default knockback.
	 */
	private function applyKnockback(Entity $entity, Entity $damager, KnockbackInfo $info) : void{
		$xzKb = $info->getHorizontalKb();
		$yKb = $info->getVerticalKb();
		$x = $entity->getX() - $damager->x;
		$z = $entity->getZ() - $damager->z;
		$f = sqrt($x * $x + $z * $z);
		if($f <= 0){
			return;
		}

		if(mt_rand() / mt_getrandmax() > $entity->getAttributeMap()
				->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()
		){
			$f = 1 / $f;
			$motion = clone $entity->getMotion();
			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $xzKb;
			$motion->y += $yKb;
			$motion->z += $z * $f * $xzKb;
			if($motion->y > $yKb){
				$motion->y = $yKb;
			}
			$entity->setMotion($motion);
		}
	}
}