<?php

declare(strict_types=1);

namespace mineceit\player\info\device;

use mineceit\MineceitCore;

class DeviceInfo{

	/** @var string[]|array */
	private $deviceModels;


	public function __construct(MineceitCore $core){
		$this->deviceModels = [];
		$file = $core->getResourcesFolder() . "device/device_models.json";
		if(file_exists($file)){
			$contents = file_get_contents($file);
			$this->deviceModels = json_decode($contents, true);
		}
	}

	/**
	 * @param string $model
	 *
	 * @return string|null
	 *
	 * Device Model.
	 */
	public function getDeviceFromModel(string $model) : ?string{

		if(isset($this->deviceModels[$model])){
			return $this->deviceModels[$model];
		}
		return null;
	}
}
