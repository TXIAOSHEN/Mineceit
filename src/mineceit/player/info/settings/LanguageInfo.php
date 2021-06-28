<?php

declare(strict_types=1);

namespace mineceit\player\info\settings;


use mineceit\MineceitCore;
use mineceit\player\language\Language;
use pocketmine\Player;

class LanguageInfo{

	/** @var string */
	private $languageLocale;

	/** @var Player */
	private $player;

	/** @var Language|null */
	private $language;

	public function __construct(Player $player){
		$this->languageLocale = Language::ENGLISH_US;
		$this->player = $player;
		$this->language = MineceitCore::getPlayerHandler()
			->getLanguage($this->languageLocale);
	}

	public function getPlayer() : ?Player{
		return $this->player;
	}

	/**
	 * @param bool $fromLocale - Get the language from their localized locale or from their language setting.
	 *
	 * @return Language|null
	 *
	 * Gets the language of the player.
	 */
	public function getLanguage(bool $fromLocale = false) : ?Language{
		if($this->language === null){
			return $this->language = MineceitCore::getPlayerHandler()
				->getLanguage($this->languageLocale);
		}
		return !$fromLocale ? $this->language :
			MineceitCore::getPlayerHandler()->getLanguage($this->player->getLocale());
	}

	public function setLanguage(string $language){
		if($language !== $this->languageLocale){
			$this->language = MineceitCore::getPlayerHandler()
				->getLanguage($language);
		}
		$this->languageLocale = $language;
	}

	/**
	 * @return bool
	 *
	 * Gets the different locale.
	 */
	public function hasDifferentLocale() : bool{
		return $this->languageLocale !== $this->player->getLocale();
	}
}