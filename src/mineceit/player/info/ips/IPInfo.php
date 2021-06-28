<?php

declare(strict_types=1);

namespace mineceit\player\info\ips;


class IPInfo{
	const TWELVE_HR_COUNTRIES = [
		"United States" => true,
		"United Kingdom" => true,
		"Philippines" => true,
		"Canada" => true,
		"Australia" => true,
		"New Zealand" => true,
		"India" => true,
		"Egypt" => true,
		"Saudi Arabia" => true,
		"Columbia" => true,
		"Pakistan" => true,
		"Malaysia" => true
	];

	/** @var string */
	private $timeZone;
	/** @var bool */
	private $is24HrTime;
	/** @var string */
	private $country;
	/** @var string */
	private $ip;

	public function __construct(string $ip, string $timeZone, string $country, ?bool $is24HrTime = null){
		$this->country = $country;
		$this->ip = $ip;
		$this->timeZone = $timeZone;
		$this->is24HrTime = $is24HrTime ?? isset(self::TWELVE_HR_COUNTRIES[$country]);
	}

	/**
	 * @param string $ip
	 * @param array  $data
	 *
	 * @return IPInfo|null
	 *
	 * Loads the info from an ip & a set of data.
	 */
	public static function loadInfo(string $ip, array $data) : ?IPInfo{
		if(isset($data['tz'], $data['24-hr'], $data['country'])){
			return new IPInfo($ip,
				$data['tz'], $data['24-hr'], $data['country']);
		}
		return null;
	}

	public function getIP() : string{
		return $this->ip;
	}

	public function getCountry() : string{
		return $this->country;
	}

	public function is24HRTime() : bool{
		return $this->is24HrTime;
	}

	public function getTimeZone() : string{
		return $this->timeZone;
	}

	/**
	 * @return array
	 *
	 * Exports the ip info to an array.
	 */
	public function export() : array{
		return [
			'tz' => $this->timeZone,
			'24-hr' => $this->is24HrTime,
			'country' => $this->country
		];
	}
}