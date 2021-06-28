<?php

declare(strict_types=1);

namespace mineceit;

use mineceit\arenas\ArenaHandler;
use mineceit\auction\AuctionHouse;
use mineceit\commands\arenas\CreateArena;
use mineceit\commands\arenas\DeleteArena;
use mineceit\commands\arenas\DuelArena;
use mineceit\commands\arenas\EventArena;
use mineceit\commands\arenas\SetArenaSpawn;
use mineceit\commands\bans\MineceitBanCommand;
use mineceit\commands\bans\MineceitBanListCommand;
use mineceit\commands\bans\MineceitKickCommand;
use mineceit\commands\bans\MineceitPardonCommand;
use mineceit\commands\bans\MineceitResetBans;
use mineceit\commands\basic\AnnounceCommand;
use mineceit\commands\basic\AuctionCommand;
use mineceit\commands\basic\BattlepassCommand;
use mineceit\commands\basic\CosmeticCommand;
use mineceit\commands\basic\DisguiseCommand;
use mineceit\commands\basic\FlyCommand;
use mineceit\commands\basic\FreezeCommand;
use mineceit\commands\basic\GamemodeCommand;
use mineceit\commands\basic\GuildCommand;
use mineceit\commands\basic\HealCommand;
use mineceit\commands\basic\HostCommand;
use mineceit\commands\basic\HubCommand;
use mineceit\commands\basic\MuteCommand;
use mineceit\commands\basic\PlayerInfoCommand;
use mineceit\commands\basic\SetLeaderboardHologram;
use mineceit\commands\basic\SettingsCommand;
use mineceit\commands\basic\ShopCommand;
use mineceit\commands\basic\StatsCommand;
use mineceit\commands\basic\ZoffroCommand;
use mineceit\commands\duels\DuelCommand;
use mineceit\commands\duels\SpecCommand;
use mineceit\commands\kits\ListKits;
use mineceit\commands\other\MineceitGarbageCollectorCommand;
use mineceit\commands\other\MineceitRestartCommand;
use mineceit\commands\other\MineceitStatusCommand;
use mineceit\commands\other\MineceitTeleportCommand;
use mineceit\commands\other\MineceitTellCommand;
use mineceit\commands\ranks\CreateRank;
use mineceit\commands\ranks\DeleteRank;
use mineceit\commands\ranks\ListRanks;
use mineceit\commands\ranks\SetRanks;
use mineceit\data\mysql\AsyncCreateDatabase;
use mineceit\discord\DiscordUtil;
use mineceit\duels\BotHandler;
use mineceit\duels\DuelHandler;
use mineceit\events\EventManager;
use mineceit\game\enchantments\KnockbackEnchantment;
use mineceit\game\entities\bots\BotPotion;
use mineceit\game\entities\bots\ClutchBot;
use mineceit\game\entities\bots\EasyBot;
use mineceit\game\entities\bots\HackerBot;
use mineceit\game\entities\bots\HardBot;
use mineceit\game\entities\bots\MediumBot;
use mineceit\game\entities\EnderPearl;
use mineceit\game\entities\FishingHook;
use mineceit\game\entities\MineceitItemEntity;
use mineceit\game\entities\replay\ReplayArrow;
use mineceit\game\entities\replay\ReplayHuman;
use mineceit\game\entities\replay\ReplayItemEntity;
use mineceit\game\entities\replay\ReplayPearl;
use mineceit\game\entities\replay\ReplayPotion;
use mineceit\game\entities\SplashPotion;
use mineceit\game\items\ItemHandler;
use mineceit\game\leaderboard\Leaderboards;
use mineceit\game\level\DeleteBlocksHandler;
use mineceit\guild\GuildManager;
use mineceit\kits\KitsManager;
use mineceit\maitenance\reports\ReportManager;
use mineceit\parties\PartyManager;
use mineceit\player\ban\BanHandler;
use mineceit\player\cosmetic\CosmeticHandler;
use mineceit\player\info\duels\duelreplay\ReplayManager;
use mineceit\player\PlayerHandler;
use mineceit\player\ranks\RankHandler;
use pocketmine\command\Command;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class MineceitCore extends PluginBase{

	// TODO POSSIBLY ADD RESOURCE PACKS SO THAT THEY SHOW UP IN FORMS
	// TODO ADD CUSTOM DEATH MESSAGES.
	// TODO FIX CONSISTENCY AUTOCLICK DETECTOR -> LATER
	// TODO ADD CHECK IF PLAYER SWINGS THEIR ARMS WHEN HITTING THE OTHER
	// TODO FIX DUELS NOT ENDING WHEN PLAYER DIES
	// TODO ADD CUSTOM KIT SETUP
	// TODO FIX PERMISSIONS FOR PLAYERS -> BASED OFF RANKS NOT PLAYERS -> TESTED
	// TODO COME UP WITH FEATURES TO INCENTIVIZE PEOPLE TO BUY VIP & VIP+
	// TODO PARTICLES -> Figure out how
	// TODO FIGURE OUT HOW TERRAIN GENERATION WORKS

	/**
	 * @var bool
	 * Determines whether mysql is enabled.
	 */
	public const MYSQL_ENABLED = true;
	/**
	 * @var bool
	 * Determines whether parties are enabled
	 */
	public const PARTIES_ENABLED = true;
	/**
	 * @var bool
	 * Determines whether replays are enabled.
	 */
	public const REPLAY_ENABLED = true;
	/**
	 * @var float
	 * Determines the default replay time scale.
	 */
	public const REPLAY_TIME_SCALE_DEFAULT = 1.0;
	/**
	 * @var bool
	 * Determines whether discord logs is enabled.
	 */
	public const DISCORD_ENABLED = true;
	/** @var KitsManager */
	private static $kits;
	/** @var PlayerHandler */
	private static $playerHandler;
	/** @var BanHandler */
	private static $banHandler;
	/** @var RankHandler */
	private static $rankHandler;
	/** @var ItemHandler */
	private static $itemHandler;
	/** @var CosmeticHandler */
	private static $cosmeticHandler;
	/** @var DeleteBlocksHandler */
	private static $blocksHandler;
	/** @var ArenaHandler */
	private static $arenas;
	/** @var DuelHandler */
	private static $duelHandler;
	/** @var BotHandler */
	private static $botHandler;
	/** @var MineceitCore */
	private static $instance;
	/** @var Leaderboards */
	private static $leaderboard;
	/** @var PartyManager */
	private static $partyManager;
	/** @var ReplayManager */
	private static $replayManager;
	/** @var ReportManager */
	private static $reportManager;
	/** @var EventManager */
	private static $eventManager;
	/** @var AuctionHouse */
	private static $auctionHouse;
	/** @var GuildManager */
	private static $guildManager;
	/** @var string */
	private static $dataFolder;
	/** @var string */
	private static $resourcesFolder;

	/**
	 * @return EventManager
	 */
	public static function getEventManager() : EventManager{
		return self::$eventManager;
	}

	/**
	 * @return ReportManager
	 */
	public static function getReportManager() : ReportManager{
		return self::$reportManager;
	}

	/**
	 * @return PartyManager
	 */
	public static function getPartyManager() : PartyManager{
		return self::$partyManager;
	}

	/**
	 * @return Leaderboards
	 */
	public static function getLeaderboards() : Leaderboards{
		return self::$leaderboard;
	}

	/**
	 * @return MineceitCore
	 */
	public static function getInstance() : MineceitCore{
		return self::$instance;
	}

	/**
	 * @return RankHandler
	 */
	public static function getRankHandler() : RankHandler{
		return self::$rankHandler;
	}

	/**
	 * @return PlayerHandler
	 */
	public static function getPlayerHandler() : PlayerHandler{
		return self::$playerHandler;
	}

	/**
	 * @return BanHandler
	 */
	public static function getBanHandler() : BanHandler{
		return self::$banHandler;
	}

	/**
	 * @return AuctionHouse
	 */
	public static function getAuctionHouse() : AuctionHouse{
		return self::$auctionHouse;
	}

	/**
	 * @return GuildManager
	 */
	public static function getGuildManager() : GuildManager{
		return self::$guildManager;
	}

	/**
	 * @return KitsManager
	 */
	public static function getKits() : KitsManager{
		return self::$kits;
	}

	/**
	 * @return CosmeticHandler
	 */
	public static function getCosmeticHandler() : CosmeticHandler{
		return self::$cosmeticHandler;
	}

	/**
	 * @return ArenaHandler
	 */
	public static function getArenas() : ArenaHandler{
		return self::$arenas;
	}

	/**
	 * @return ItemHandler
	 */
	public static function getItemHandler() : ItemHandler{
		return self::$itemHandler;
	}

	/**
	 * @return DeleteBlocksHandler
	 */
	public static function getDeleteBlocksHandler() : DeleteBlocksHandler{
		return self::$blocksHandler;
	}

	/**
	 * @return DuelHandler
	 */
	public static function getDuelHandler() : DuelHandler{
		return self::$duelHandler;
	}

	/**
	 * @return BotHandler
	 */
	public static function getBotHandler() : BotHandler{
		return self::$botHandler;
	}

	/**
	 * @return ReplayManager
	 */
	public static function getReplayManager() : ReplayManager{
		return self::$replayManager;
	}

	/**
	 * @return string
	 *
	 * Gets the resources folder.
	 */
	public static function getResourcesFolder() : string{
		return self::$resourcesFolder;
	}

	/**
	 * @return string
	 *
	 * Gets the data folder.
	 */
	public static function getDataFolderPath() : string{
		return self::$dataFolder;
	}

	/**
	 * @return array
	 *
	 * Gets the mysql data.
	 */
	public static function getMysqlData() : array{

		$config = new Config(self::$dataFolder . "mineceit.yml", Config::YAML);

		if($config->exists("mysql")){
			return (array) $config->get("mysql");
		}

		return ["username" => "", "database" => "", "password" => "", "ip" => "", "port" => 3306];
	}

	/**
	 * @return array
	 *
	 * Gets the discord webhooks.
	 */
	public static function getDiscordWebhooks() : array{

		$config = new Config(self::$dataFolder . "mineceit.yml", Config::YAML);

		if($config->exists("webhooks")){
			return (array) $config->get("webhooks");
		}

		return ["ban" => "", "logs" => "", "players" => "", "reports" => "", "status" => "", "smth" => ""];
	}

	/**
	 * When the plugins.
	 */
	public function onEnable(){

		self::$instance = $this;

		self::$dataFolder = $this->getDataFolder();
		self::$resourcesFolder = $this->getFile() . 'resources/';

		$file = fopen(self::$dataFolder . 'reports.csv', 'wb');
		fclose($file);

		if(!file_exists(self::$dataFolder . '/donator.yml')){
			$file = fopen(self::$dataFolder . '/donator.yml', 'wb');
			fclose($file);
		}

		$this->loadLevels();

		$this->saveResource("mineceit.yml");

		$this->getServer()->setConfigString("enable-query", "off");

		$this->initEnchantments();
		$this->registerEntities();
		$this->registerCommands();

		self::$rankHandler = new RankHandler($this);
		self::$playerHandler = new PlayerHandler($this);
		self::$banHandler = new BanHandler($this);
		self::$itemHandler = new ItemHandler();
		self::$kits = new KitsManager();

		self::$cosmeticHandler = new CosmeticHandler($this);
		self::$blocksHandler = new DeleteBlocksHandler($this);
		self::$arenas = new ArenaHandler($this);
		self::$duelHandler = new DuelHandler($this);
		self::$botHandler = new BotHandler($this);
		self::$replayManager = new ReplayManager($this);
		self::$partyManager = new PartyManager($this);
		self::$reportManager = new ReportManager($this);
		self::$eventManager = new EventManager($this);
		self::$auctionHouse = new AuctionHouse();
		self::$guildManager = new GuildManager();

		self::$leaderboard = new Leaderboards($this);

		if(self::MYSQL_ENABLED){
			$kitslocal = [];
			$kits = self::$kits->getKits();
			foreach($kits as $kit){
				if($kit->getMiscKitInfo()->isDuelsEnabled())
					$kitslocal[] = $kit->getLocalizedName();
			}
			$task = new AsyncCreateDatabase($kitslocal);
			$this->getServer()->getAsyncPool()->submitTask($task);
		}

		$title = DiscordUtil::boldText(MineceitCore::getRegion() . " - Status");
		$data = self::getServerType();
		$message = "{$data} is now " . DiscordUtil::boldText("ONLINE");
		DiscordUtil::sendStatusUpdate($title, $message, DiscordUtil::GREEN);

		$this->getServer()->getPluginManager()->registerEvents(new MineceitListener($this), $this);
		$this->getScheduler()->scheduleRepeatingTask(new MineceitTask($this), 1);

		$this->getLogger()->info("\n\n              ---" . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'Zeqa' . TextFormat::WHITE . ' Network' . "---\n");

		$this->getServer()->getNetwork()->setName(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'Zeqa' . TextFormat::WHITE . ' Network');
	}

	/**
	 * Loads the levels.
	 */
	private function loadLevels() : void{

		$worlds = MineceitUtil::getLevelsFromFolder($this);

		$size = count($worlds);

		$server = $this->getServer();

		if($size > 0){
			foreach($worlds as $world){
				$world = strval($world);
				if(strpos($world, 'duel') !== false || strpos($world, 'replay') !== false || strpos($world, 'party') !== false || strpos($world, 'bot') !== false){
					MineceitUtil::deleteLevel($world);
				}elseif(!$server->isLevelLoaded($world) && (strpos($world, '.') === false)){
					$server->loadLevel($world);
					$server->getLevelByName($world)->setTime(0);
					if($world === 'OITC') $server->getLevelByName($world)->setTime(18000);
					$server->getLevelByName($world)->stopTime();
				}
			}
		}
	}

	/**
	 * Registers the overriden enchantments.
	 */
	private function initEnchantments() : void{
		Enchantment::registerEnchantment(
			new KnockbackEnchantment(
				Enchantment::KNOCKBACK, "%enchantment.knockback",
				Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD,
				Enchantment::SLOT_NONE, 2));
	}

	/**
	 * Registers the entities.
	 */
	private function registerEntities() : void{

		Entity::registerEntity(EnderPearl::class, true, ["EnderPearl"]);
		Entity::registerEntity(SplashPotion::class, false, ['ThrownPotion', 'minecraft:potion', 'thrownpotion']);
		Entity::registerEntity(BotPotion::class, false, ['BotPotion']);
		Entity::registerEntity(FishingHook::class, false, ["FishingHook", "minecraft:fishing_hook"]);
		Entity::registerEntity(MineceitItemEntity::class, false, ['Item', 'minecraft:item']);

		Entity::registerEntity(ReplayItemEntity::class, false, ["ReplayItem"]);
		Entity::registerEntity(ReplayArrow::class, false, ["ReplayArrow"]);
		Entity::registerEntity(ReplayPearl::class, false, ["ReplayPearl"]);
		Entity::registerEntity(ReplayHuman::class, true);
		Entity::registerEntity(ReplayPotion::class, true, ["ReplayPotion"]);

		Entity::registerEntity(ClutchBot::class, true, ['ClutchBot']);
		Entity::registerEntity(EasyBot::class, true, ['EasyBot']);
		Entity::registerEntity(MediumBot::class, true, ['MediumBot']);
		Entity::registerEntity(HardBot::class, true, ['HardBot']);
		Entity::registerEntity(HackerBot::class, true, ['HackerBot']);
	}

	/**
	 * Registers the commands.
	 */
	private function registerCommands() : void{
		$this->unregisterCommand('about');
		$this->unregisterCommand('checkperm');
		$this->unregisterCommand('gamerule');
		$this->unregisterCommand('checkperm');
		$this->unregisterCommand('mixer');
		$this->unregisterCommand('version');
		$this->unregisterCommand('suicide');
		$this->unregisterCommand('gamemode');
		$this->unregisterCommand('ban');
		$this->unregisterCommand('ban-ip');
		$this->unregisterCommand('banlist');
		$this->unregisterCommand('kick');
		$this->unregisterCommand('pardon');
		$this->unregisterCommand('pardon-ip');
		$this->unregisterCommand('tp');
		$this->unregisterCommand('tell');
		$this->unregisterCommand('me');
		$this->unregisterCommand('gc');
		$this->unregisterCommand('status');

		$this->registerCommand(new AnnounceCommand());
		$this->registerCommand(new AuctionCommand());
		$this->registerCommand(new BattlepassCommand());
		$this->registerCommand(new ListKits());
		$this->registerCommand(new CreateArena());
		$this->registerCommand(new CreateRank());
		$this->registerCommand(new CosmeticCommand());
		$this->registerCommand(new DisguiseCommand());
		$this->registerCommand(new GamemodeCommand());
		$this->registerCommand(new GuildCommand());
		$this->registerCommand(new FreezeCommand());
		//$this->registerCommand(new FollowCommand());
		$this->registerCommand(new FlyCommand());
		$this->registerCommand(new DuelCommand());
		$this->registerCommand(new HostCommand());
		$this->registerCommand(new HubCommand());
		$this->registerCommand(new HealCommand());
		$this->registerCommand(new DeleteArena());
		$this->registerCommand(new DeleteRank());
		$this->registerCommand(new ListRanks());
		$this->registerCommand(new StatsCommand());
		$this->registerCommand(new SettingsCommand());
		$this->registerCommand(new ShopCommand());
		$this->registerCommand(new SpecCommand());
		$this->registerCommand(new SetRanks());
		$this->registerCommand(new SetLeaderboardHologram());
		$this->registerCommand(new PlayerInfoCommand());
		$this->registerCommand(new ZoffroCommand());
		$this->registerCommand(new MineceitBanCommand("ban"));
		$this->registerCommand(new MineceitBanListCommand('banlist'));
		$this->registerCommand(new MineceitKickCommand('kick'));
		$this->registerCommand(new MineceitPardonCommand('pardon'));
		$this->registerCommand(new MineceitTeleportCommand('tp'));
		$this->registerCommand(new MuteCommand(true));
		$this->registerCommand(new MuteCommand(false));
		$this->registerCommand(new MineceitTellCommand("tell"));
		$this->registerCommand(new MineceitGarbageCollectorCommand("gc"));
		$this->registerCommand(new MineceitStatusCommand("status"));
		$this->registerCommand(new MineceitRestartCommand());
		$this->registerCommand(new MineceitResetBans());
		$this->registerCommand(new DuelArena());
		$this->registerCommand(new EventArena());
		$this->registerCommand(new SetArenaSpawn());
	}

	/**
	 * @param string $commandName
	 *
	 * Unregisters a command.
	 */
	private function unregisterCommand(string $commandName) : void{

		$commandMap = $this->getServer()->getCommandMap();
		$cmd = $commandMap->getCommand($commandName);
		if($cmd !== null){
			$commandMap->unregister($cmd);
		}
	}

	/**
	 * @param Command $command
	 *
	 * Registers a command.
	 */
	private function registerCommand(Command $command) : void{

		$this->getServer()->getCommandMap()->register($command->getName(), $command);
	}

	/**
	 * @return string
	 *
	 * Gets the region name.
	 */
	public static function getRegion() : string{

		$config = new Config(self::$dataFolder . "mineceit.yml", Config::YAML);

		$result = "Zeqa";

		if($config->exists("region")){
			$result = (string) $config->get("region");
		}

		return $result === "" ? "Zeqa" : $result;
	}

	/**
	 * @return string
	 *
	 * Gets the server type.
	 */
	public static function getServerType() : string{

		$config = new Config(self::$dataFolder . "mineceit.yml", Config::YAML);

		$result = "Zeqa";

		if($config->exists("server-type")){
			$result = (string) $config->get("server-type");
		}

		return $result === "" ? "Zeqa" : $result;
	}

	public function onDisable(){
		$title = DiscordUtil::boldText(MineceitCore::getRegion() . " - Status");
		$data = self::getServerType();
		$message = "{$data} is now " . DiscordUtil::boldText("OFFLINE");
		DiscordUtil::sendStatusUpdate($title, $message, DiscordUtil::RED);
	}
}
