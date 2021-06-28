<?php

declare(strict_types=1);

namespace mineceit\player\info\device;


/**
 * Interface DeviceIds
 * @package mineceit\player\info\device
 *
 * Implements the constants for the device ids.
 */
interface DeviceIds{
	// The raw device Ids.
	public const UNKNOWN = -1;
	public const ANDROID = 1;
	public const IOS = 2;
	public const OSX = 3;
	public const FIREOS = 4;
	public const VRGEAR = 5;
	public const VRHOLOLENS = 6;
	public const WINDOWS_10 = 7;
	public const WINDOWS_32 = 8;
	public const DEDICATED = 9;
	public const TVOS = 10;
	public const PS4 = 11;
	public const SWITCH = 12;
	public const XBOX = 13;
	public const LINUX = 20; // For linux people.

	// The keyboard input ids.
	public const KEYBOARD = 1;
	public const TOUCH = 2;
	public const CONTROLLER = 3;
	public const MOTION_CONTROLLER = 4;

	// The device OS Values.
	public const DEVICE_OS_VALUES = [
		self::UNKNOWN => 'Unknown',
		self::ANDROID => 'Android',
		self::IOS => 'iOS',
		self::OSX => 'OSX',
		self::FIREOS => 'FireOS',
		self::VRGEAR => 'VRGear',
		self::VRHOLOLENS => 'VRHololens',
		self::WINDOWS_10 => 'Win10',
		self::WINDOWS_32 => 'Win32',
		self::DEDICATED => 'Dedicated',
		self::TVOS => 'TVOS',
		self::PS4 => 'PS4',
		self::SWITCH => 'Nintendo Switch',
		self::XBOX => 'Xbox',
		self::LINUX => 'Linux'
	];

	// Lists all of the non pe devices.
	public const NON_PE_DEVICES = [
		self::PS4 => true,
		self::WINDOWS_10 => true,
		self::XBOX => true,
		self::LINUX => true
	];

	// The input values.
	public const INPUT_VALUES = [
		self::UNKNOWN => 'Unknown',
		self::KEYBOARD => 'Keyboard',
		self::TOUCH => 'Touch',
		self::CONTROLLER => 'Controller',
		self::MOTION_CONTROLLER => 'Motion-Controller'
	];

	/** @var array -> Gets the ui values. */
	public const UI_PROFILE_VALUES = [
		self::UNKNOWN => 'Unknown',
		'Classic UI',
		'Pocket UI'
	];
}
