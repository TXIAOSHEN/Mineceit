<?php

declare(strict_types=1);

namespace mineceit\misc;


use mineceit\MineceitCore;
use pocketmine\event\Listener;

/**
 * Class AbstractListener
 * @package mineceit\misc
 *
 * An abstract listener.
 */
abstract class AbstractListener implements Listener{

	/**
	 * AbstractListener constructor.
	 *
	 * @param MineceitCore $core
	 *
	 * The constructor for a listener.
	 */
	public function __construct(MineceitCore $core){
		$core->getServer()->getPluginManager()->registerEvents($this, $core);
	}
}