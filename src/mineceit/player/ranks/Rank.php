<?php

declare(strict_types=1);

namespace mineceit\player\ranks;

class Rank{

	public const PERMISSION_OWNER = "owner";
	public const PERMISSION_ADMIN = "admin";
	public const PERMISSION_MOD = "mod";
	public const PERMISSION_HELPER = "helper";
	public const PERMISSION_BUILDER = "builder";
	public const PERMISSION_CONTENT_CREATOR = "content-creator";
	public const PERMISSION_VIP = "vip";
	public const PERMISSION_VIPPL = "vip+";
	public const PERMISSION_NONE = "none";

	public const PERMISSION_INDEXES = [
		self::PERMISSION_OWNER,
		self::PERMISSION_ADMIN,
		self::PERMISSION_MOD,
		self::PERMISSION_HELPER,
		self::PERMISSION_BUILDER,
		self::PERMISSION_CONTENT_CREATOR,
		self::PERMISSION_VIPPL,
		self::PERMISSION_VIP,
		self::PERMISSION_NONE
	];

	/* @var string */
	private $name;

	/* @var string */
	private $formattedName;

	/* @var string */
	private $localName;

	/* @var string */
	private $color;

	/** @var string */
	private $permission;

	public function __construct(string $localName, string $name, string $formattedName, string $permission = self::PERMISSION_NONE){
		$this->localName = $localName;
		$this->name = $name;
		$this->formattedName = $formattedName;
		$this->permission = $permission;
		$this->color = str_replace($this->name, '', $this->formattedName);
		if($this->name === 'DonatorPlus') $this->color = "§e";
		elseif($this->name === 'Player') $this->color = '';
	}

	/**
	 * @param string $localName
	 * @param array  $array
	 *
	 * @return Rank|null
	 */
	public static function parseRank(string $localName, array $array) : ?Rank{

		$result = null;

		if(isset($array['name'], $array['format'])){

			$name = (string) $array['name'];
			$format = (string) $array['format'];

			$permission = Rank::PERMISSION_NONE;
			if(isset($array['permission'])){
				$permission = $array['permission'];
			}

			$result = new Rank($localName, $name, $format, $permission);
		}
		return $result;
	}

	/**
	 * @return string
	 */
	public function getFormat() : string{
		return $this->formattedName;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getLocalName() : string{
		return $this->localName;
	}

	/**
	 * @return string
	 */
	public function getColor() : string{
		return $this->color;
	}

	/**
	 * @return array
	 */
	public function encode() : array{
		return [
			'name' => $this->name,
			'format' => $this->formattedName,
			'permission' => $this->permission
		];
	}

	/**
	 * @return string
	 */
	public function getPermission() : string{
		return $this->permission;
	}
}
