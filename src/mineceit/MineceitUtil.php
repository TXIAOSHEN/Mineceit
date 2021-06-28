<?php

declare(strict_types=1);

namespace mineceit;

use mineceit\data\mysql\MysqlRow;
use mineceit\data\mysql\MysqlStream;
use mineceit\game\entities\replay\ReplayHuman;
use mineceit\game\entities\replay\ReplayItemEntity;
use mineceit\game\level\AsyncDeleteLevel;
use mineceit\game\level\MineceitChunkLoader;
use mineceit\player\language\Language;
use mineceit\player\language\translate\TranslateUtil;
use mineceit\player\MineceitPlayer;
use pocketmine\block\Redstone;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\lang\TranslationContainer;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\ScriptCustomEventPacket;
use pocketmine\permission\PermissionManager;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Binary;
use pocketmine\utils\TextFormat;

class MineceitUtil{

	// TIME FUNCTIONS
	const ONE_MONTH_SECONDS = 2628000;
	const ONE_WEEK_SECONDS = 604800;

	const MINECEIT_SCRIPT_PKT_NAME = "mineceit:packet";

	const CLASSIC_DUEL_GEN = "duel_classic";
	const CLASSIC_COMBO_GEN = "combo_classic";
	const CLASSIC_MLG_GEN = "mlg_classic";
	const CLASSIC_SUMO_GEN = "sumo_classic";
	const CLASSIC_CLUTCH_GEN = "clutch_classic";

	const RIVER_DUEL_GEN = "duel_river";
	const BURNT_DUEL_GEN = "duel_burnt";

	public const NETHERITE_HELMET = 748;
	public const NETHERITE_CHESTPLATE = 749;
	public const NETHERITE_LEGGINGS = 750;
	public const NETHERITE_BOOTS = 751;

	public const
		HELMET = [
		Item::LEATHER_HELMET,
		Item::CHAIN_HELMET,
		Item::IRON_HELMET,
		Item::GOLD_HELMET,
		Item::DIAMOND_HELMET,
		self::NETHERITE_HELMET,
	],
		CHESTPLATE = [
		Item::LEATHER_CHESTPLATE,
		Item::CHAIN_CHESTPLATE,
		Item::IRON_CHESTPLATE,
		Item::GOLD_CHESTPLATE,
		Item::DIAMOND_CHESTPLATE,
		self::NETHERITE_CHESTPLATE,
		Item::ELYTRA,
	],
		LEGGINGS = [
		Item::LEATHER_LEGGINGS,
		Item::CHAIN_LEGGINGS,
		Item::IRON_LEGGINGS,
		Item::GOLD_LEGGINGS,
		Item::DIAMOND_LEGGINGS,
		self::NETHERITE_LEGGINGS,
	],
		BOOTS = [
		Item::LEATHER_BOOTS,
		Item::CHAIN_BOOTS,
		Item::IRON_BOOTS,
		Item::GOLD_BOOTS,
		Item::DIAMOND_BOOTS,
		self::NETHERITE_BOOTS,
	];

	/**
	 * @param int $secs
	 *
	 * @return int
	 */
	public static function secondsToTicks(int $secs) : int{
		return $secs * 20;
	}

	/**
	 * @param int $mins
	 *
	 * @return int
	 */
	public static function minutesToTicks(int $mins) : int{
		return $mins * 1200;
	}

	/**
	 * @param int $hours
	 *
	 * @return int
	 */
	public static function hoursToTicks(int $hours) : int{
		return $hours * 72000;
	}

	/**
	 * @param int $ticks
	 *
	 * @return int
	 */
	public static function ticksToMinutes(int $ticks) : int{
		return intval($ticks / 1200);
	}

	/**
	 * @param int $ticks
	 *
	 * @return int
	 */
	public static function ticksToSeconds(int $ticks) : int{
		return intval($ticks / 20);
	}

	/**
	 * @param int $ticks
	 *
	 * @return int
	 */
	public static function ticksToHours(int $ticks) : int{
		return intval($ticks / 72000);
	}

	// MESSAGE FUNCTIONS

	/**
	 * @param string $message
	 */
	public static function broadcastMessage(string $message) : void{
		$server = Server::getInstance();
		$server->broadcastMessage(self::getPrefix() . ' ' . TextFormat::RESET . $message);
	}

	public static function getPrefix() : string{
		$serverName = self::getServerName(true);
		return $serverName . TextFormat::RESET . TextFormat::DARK_GRAY . ' »' . TextFormat::RESET;
	}

	/**
	 * @param bool $bold
	 *
	 * @return string
	 */
	public static function getServerName(bool $bold = false) : string{
		return self::getThemeColor() . ($bold === true ? TextFormat::BOLD : '') . 'ZEQA';
	}

	/**
	 * @return string
	 */
	public static function getThemeColor() : string{
		return TextFormat::LIGHT_PURPLE;
	}


	/**
	 * @param array|string[] $excludedColors
	 *
	 * @return string
	 */
	public static function randomColor($excludedColors = []) : string{
		$array = [
			TextFormat::DARK_PURPLE => true,
			TextFormat::GOLD => true,
			TextFormat::RED => true,
			TextFormat::GREEN => true,
			TextFormat::LIGHT_PURPLE => true,
			TextFormat::AQUA => true,
			TextFormat::DARK_RED => true,
			TextFormat::DARK_AQUA => true,
			TextFormat::BLUE => true,
			TextFormat::GRAY => true,
			TextFormat::DARK_GREEN => true,
			TextFormat::BLACK => true,
			TextFormat::DARK_BLUE => true,
			TextFormat::DARK_GRAY => true,
			TextFormat::YELLOW => true,
			TextFormat::WHITE => true
		];

		$array2 = $array;

		foreach($excludedColors as $c){
			if(isset($array[$c]))
				unset($array[$c]);
		}

		if(count($array) === 0) $array = $array2;

		$size = count($array) - 1;

		$keys = array_keys($array);

		return (string) $keys[mt_rand(0, $size)];
	}

	// ITEM FUNCTIONS

	/**
	 * @param int                         $id
	 * @param int                         $meta
	 * @param int                         $count
	 * @param array|EnchantmentInstance[] $enchants
	 *
	 * @return Item
	 */
	public static function createItem(int $id, int $meta = 0, $count = 1, $enchants = []) : Item{

		$item = Item::get($id, $meta, $count);

		foreach($enchants as $e)
			$item->addEnchantment($e);

		return $item;
	}

	/**
	 * @param Item   $armor An Armor or an elytra
	 * @param Player $player
	 */
	public static function setArmorByType(Item $armor, Player $player) : void{
		$id = $armor->getId();
		$set = false;
		if(in_array($id, self::HELMET, true)){
			$copy = $player->getArmorInventory()->getHelmet();
			$set = $player->getArmorInventory()->setHelmet($armor);
		}elseif(in_array($id, self::CHESTPLATE, true)){
			$copy = $player->getArmorInventory()->getChestplate();
			$set = $player->getArmorInventory()->setChestplate($armor);
		}elseif(in_array($id, self::LEGGINGS, true)){
			$copy = $player->getArmorInventory()->getLeggings();
			$set = $player->getArmorInventory()->setLeggings($armor);
		}elseif(in_array($id, self::BOOTS, true)){
			$copy = $player->getArmorInventory()->getBoots();
			$set = $player->getArmorInventory()->setBoots($armor);
		}
		if($set){
			//if $set is defined, $copy is defined too
			$player->getInventory()->setItemInHand($copy);
		}
	}

	// POSITION FUNCTIONS

	/**
	 * @param Location|Position|Vector3 $pos
	 *
	 * @return array
	 */
	public static function posToArray($pos) : array{
		return [
			'x' => (int) $pos->x,
			'y' => (int) $pos->y,
			'z' => (int) $pos->z
		];
	}

	/**
	 * @param Vector3    $vec3
	 * @param Level|null $level
	 *
	 * @return Position
	 */
	public static function toPosition(Vector3 $vec3, Level $level = null) : Position{
		return new Position($vec3->x, $vec3->y, $vec3->z, $level);
	}

	// LEVEL FUNCTIONS

	/**
	 * @param MineceitCore $core
	 *
	 * @return array|string[]
	 */
	public static function getLevelsFromFolder(MineceitCore $core) : array{

		$dataFolder = $core->getDataFolder();

		$index = strpos($dataFolder, '\plugin_data');

		$substr = substr($dataFolder, 0, $index);

		$worlds = $substr . "/worlds";

		if(!is_dir($worlds))
			return [];

		$zip = new \ZipArchive;
		if($zip->open($core->getResourcesFolder() . 'worlds/Build.zip') === true){
			$zip->extractTo($worlds . "/Build/");
			$zip->close();
			unset($zip);
		}

		return scandir($worlds);
	}

	/**
	 * @param Level    $level
	 * @param int      $x
	 * @param int      $z
	 * @param callable $callable
	 */
	public static function onChunkGenerated(Level $level, int $x, int $z, callable $callable) : void{

		if($level->isChunkPopulated($x, $z)){
			($callable)();
			return;
		}
		$level->registerChunkLoader(new MineceitChunkLoader($level, $x, $z, $callable), $x, $z, true);
	}

	/**
	 * @param int    $worldId
	 * @param string $arena
	 * @param string $type
	 *
	 * @return bool
	 */
	public static function createLevel(int $worldId, string $arena, string $type) : bool{

		$server = Server::getInstance();

		$arenaPath = MineceitCore::getResourcesFolder() . "worlds/$arena.zip";
		if(!file_exists($arenaPath)){
			return false;
		}else{
			$newLevelPath = $server->getDataPath() . "/worlds/$type$worldId/";
			$zip = new \ZipArchive;
			if($zip->open($arenaPath) === true){
				mkdir($newLevelPath);
				$zip->extractTo($newLevelPath);
				$zip->close();
				unset($zip);

				$nbt = new BigEndianNBTStream();
				$leveldat = zlib_decode(file_get_contents($newLevelPath . 'level.dat'));
				$levelData = $nbt->read($leveldat);
				$levelData["Data"]->setTag(new StringTag("LevelName", "$type$worldId"));

				$buffer = $nbt->writeCompressed($levelData);
				file_put_contents($newLevelPath . 'level.dat', $buffer);

				$server->loadLevel("$type$worldId");
				$level = $server->getLevelByName("$type$worldId");
				$level->setTime(0);
				$level->stopTime();

				return true;
			}
		}

		return false;
	}

	/**
	 * @param string|Level $level
	 */
	public static function deleteLevel($level) : void{

		$server = Server::getInstance();

		if(is_string($level)){

			$path = $server->getDataPath() . "worlds/" . $level;

			$server->getAsyncPool()->submitTask(new AsyncDeleteLevel($path));
		}elseif($level instanceof Level){

			$server->unloadLevel($level);

			$path = Server::getInstance()->getDataPath() . 'worlds/' . $level->getFolderName();

			$server->getAsyncPool()->submitTask(new AsyncDeleteLevel($path));
		}
	}

	/**
	 * @param string|int $index - Int or string.
	 *
	 * @return int|string
	 *
	 * Converts the armor index based on its type.
	 */
	public static function convertArmorIndex($index){
		if(is_string($index)){
			switch(strtolower($index)){
				case "boots":
					return 3;
				case "leggings":
					return 2;
				case "chestplate":
				case "chest":
					return 1;
				case "helmet":
					return 0;
			}

			return 0;
		}

		switch($index % 4){
			case 0:
				return "helmet";
			case 1:
				return "chestplate";
			case 2:
				return "leggings";
			case 3:
				return "boots";
		}

		return 0;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 *
	 * Converts an item to an array.
	 */
	public static function itemToArr(Item $item) : array{
		$output = [
			"id" => $item->getId(),
			"meta" => $item->getDamage(),
			"count" => $item->getCount()
		];

		if($item->hasEnchantments()){
			$enchantments = $item->getEnchantments();
			$inputEnchantments = [];
			foreach($enchantments as $enchantment){
				$inputEnchantments[] = [
					"id" => $enchantment->getId(),
					"level" => $enchantment->getLevel()
				];
			}

			$output["enchants"] = $inputEnchantments;
		}

		if($item->hasCustomName()){
			$output["customName"] = $item->getCustomName();
		}

		return $output;
	}

	/**
	 * @param array $input
	 *
	 * @return Item|null
	 *
	 * Converts an array of data to an item.
	 */
	public static function arrToItem(array $input) : ?Item{
		if(!isset($input["id"], $input["meta"], $input["count"])){
			return null;
		}

		$item = Item::get($input["id"], $input["meta"], $input["count"]);
		if(isset($input["customName"])){
			$item->setCustomName($input["customName"]);
		}

		if(isset($input["enchants"])){
			$enchantments = $input["enchants"];
			foreach($enchantments as $enchantment){
				if(!isset($enchantment["id"], $enchantment["level"])){
					continue;
				}

				$item->addEnchantment(new EnchantmentInstance(
					Enchantment::getEnchantment($enchantment["id"]),
					$enchantment["level"]
				));
			}
		}

		return $item;
	}


	/**
	 * @param Level            $level
	 * @param Vector3          $source
	 * @param Item             $item
	 * @param Vector3|null     $motion
	 * @param null             $droppedTick
	 * @param ReplayHuman|null $pickup
	 * @param null             $pickupTime
	 *
	 * @return ReplayItemEntity|null
	 */
	public static function dropItem(Level $level, Vector3 $source, Item $item, Vector3 $motion = null, $droppedTick = null, ReplayHuman $pickup = null, $pickupTime = null) : ?ReplayItemEntity{

		$motion = $motion ?? new Vector3(lcg_value() * 0.2 - 0.1, 0.2, lcg_value() * 0.2 - 0.1);
		$itemTag = $item->nbtSerialize();
		$itemTag->setName("Item");

		if(!$item->isNull()){
			$nbt = Entity::createBaseNBT($source, $motion, lcg_value() * 360, 0);
			$nbt->setShort("Health", 5);
			$nbt->setShort("PickupDelay", 40);
			$nbt->setTag($itemTag);
			$itemEntity = Entity::createEntity("ReplayItem", $level, $nbt);
			if($itemEntity instanceof ReplayItemEntity){
				if($pickupTime !== null) $itemEntity->setPickupTick($pickupTime);
				if($pickup !== null) $itemEntity->setHumanPickup($pickup);
				if($droppedTick !== null) $itemEntity->setDroppedTick($droppedTick);
				$itemEntity->spawnToAll();
				return $itemEntity;
			}
		}
		return null;
	}


	/**
	 * @param string $name
	 * @param bool   $displayName
	 *
	 * @return Player|null
	 */
	public static function getPlayer(string $name, bool $displayName = false) : ?Player{

		$found = null;
		$name = strtolower($name);
		$delta = PHP_INT_MAX;
		$server = Server::getInstance();

		foreach($server->getOnlinePlayers() as $player){

			if(stripos($player->getDisplayName(), $name) === 0){
				$curDelta = strlen($player->getDisplayName()) - strlen($name);
				if($curDelta < $delta){
					$found = $player;
					$delta = $curDelta;
				}
				if($curDelta === 0){
					break;
				}
			}
		}

		return $found;
	}


	/**
	 *
	 * @param string $name
	 * @param bool   $displayName
	 *
	 * @return Player|null
	 */
	public static function getPlayerExact(string $name, bool $displayName = false) : ?Player{
		$name = strtolower($name);
		$server = Server::getInstance();
		foreach($server->getOnlinePlayers() as $player){
			$str = ($displayName ? strtolower($player->getDisplayName()) : $player->getLowerCaseName());
			if($str === $name){
				return $player;
			}
		}

		return null;
	}


	/**
	 * @param string $permissions
	 *
	 * Gets recipients based on the permissions. -> Taken from Server in BroadcastMessage function.
	 *
	 * @return CommandSender[]
	 */
	public static function getRecipients(string $permissions = Server::BROADCAST_CHANNEL_USERS) : array{

		/** @var CommandSender[] $recipients */
		$recipients = [];
		foreach(explode(";", $permissions) as $permission){
			foreach(PermissionManager::getInstance()->getPermissionSubscriptions($permission) as $permissible){
				if($permissible instanceof CommandSender && $permissible->hasPermission($permission)){
					$recipients[spl_object_hash($permissible)] = $permissible; // do not send messages directly, or some might be repeated
				}
			}
		}

		return $recipients;
	}


	/**
	 * @param CommandSender               $playerSent
	 * @param string                      $format
	 * @param string|TranslationContainer $message
	 * @param array|CommandSender[]|null
	 * @param int                         $indexMsg
	 *
	 * Broadcasts a message to each player. -> used for translating messages.
	 */
	public static function broadcastTranslatedMessage(CommandSender $playerSent, string $format, $message, array $recipients = null, int $indexMsg = 0) : void{

		/** @var CommandSender[] $recipients */
		$recipients = $recipients ?? Server::getInstance()->getOnlinePlayers();
		$lang = $playerSent instanceof MineceitPlayer ? $playerSent->getLanguageInfo()->getLanguage() : MineceitCore::getPlayerHandler()->getLanguage();

		foreach($recipients as $sender){

			if($sender instanceof MineceitPlayer && $sender
					->getSettingsInfo()->doesTranslateMessages() && !$sender->equalsPlayer($playerSent)){
				TranslateUtil::client5_translate($message, $sender, $lang, $format, $indexMsg);
			}else{

				if($message instanceof TranslationContainer){
					$text = $message->getText();
					$parameters = $message->getParameters();
					$parameters[$indexMsg] = $format . $parameters[$indexMsg];
					$message = new TranslationContainer($text, $parameters);
					$sender->sendMessage($message);
				}else{
					$sender->sendMessage($format . $message);
				}
			}
		}
	}


	/**
	 * @param MineceitPlayer $player
	 *
	 * @return string
	 *
	 * The join message.
	 */
	public static function getJoinMessage(MineceitPlayer $player) : string{
		if($player->isSilentStaffEnabled()) return '';
		$name = $player->getDisplayName();
		if($player->getDisguiseInfo()->isDisguised()){
			$player->getDisguiseInfo()->setDisguised(true, true);
			$name = $player->getDisguiseInfo()->getDisguiseData()->getDisplayName();
		}
		$prefix = TextFormat::DARK_GRAY . '[' . TextFormat::GREEN . '+' . TextFormat::DARK_GRAY . ']';
		return $prefix . TextFormat::GREEN . " {$name}";
	}

	/**
	 * @param MineceitPlayer $player
	 *
	 * @return string
	 *
	 * The join message.
	 */
	public static function getLeaveMessage(MineceitPlayer $player) : string{
		if($player->isSilentStaffEnabled()) return '';
		$name = $player->getDisplayName();
		$prefix = TextFormat::DARK_GRAY . '[' . TextFormat::RED . '-' . TextFormat::DARK_GRAY . ']';
		return $prefix . TextFormat::RED . " {$name}";
	}

	/**
	 * @param               $num
	 * @param Language|null $lang
	 *
	 * @return string
	 *
	 * Gets the ordinal for the number.
	 */
	public static function getOrdinalPostfix($num, Language $lang = null) : string{

		if(!is_numeric($num)){
			return "";
		}

		$num = strval($num);
		$length = strlen($num);
		if($length <= 0){
			return "";
		}

		$lang = $lang ?? MineceitCore::getPlayerHandler()->getLanguage();

		$lastNum = intval($num);
		if($lang->doesShortenOrdinals()){
			$lastNum = intval($num[$length - 1]);
		}

		return $lang->getOrdinalOf($lastNum);
	}


	/**
	 * @param string $string
	 *
	 * @return int
	 *
	 * Gets the word count of a string.
	 */
	public static function getWordCount(string $string) : int{

		if($string === ""){
			return 0;
		}

		$exploded = explode(" ", trim($string));
		$count = 0;
		foreach($exploded as $word){
			if($word !== ""){
				$count++;
			}
		}
		return $count;
	}

	/**
	 * @param $input
	 *
	 * @return string
	 *
	 * Gets the word count of a string.
	 */
	public static function center($input) : string{
		$clear = TextFormat::clean($input);
		$lines = explode("\n", $clear);
		$max = max(array_map("strlen", $lines));
		$lines = explode("\n", $input);
		foreach($lines as $key => $line){
			$lines[$key] = str_pad($line, $max + self::colorCount($line), " ", STR_PAD_BOTH);
		}

		return implode("\n", $lines);
	}

	public static function colorCount($input) : int{
		$colors = "abcdef0123456789klmnor";
		$count = 0;
		for($i = 0; $i < strlen($colors); $i++){
			$count += substr_count($input, "§" . $colors[$i]);
		}

		return $count;
	}

	/**
	 * @param MineceitPlayer|ReplayHuman  $player
	 * @param DataPacket                  $packet
	 * @param callable                    $function
	 * @param MineceitPlayer[]|array|null $viewers
	 *
	 * Broadcasts the swish sound.
	 */
	public static function broadcastDataPacket($player, DataPacket $packet, callable $function, array $viewers = null) : void{

		$position = $player->asVector3();
		$level = $player->getLevel();

		/** @var Player[] $chunkPlayers */
		$chunkPlayers = $viewers ?? $level->getViewersForPosition($position);

		foreach($chunkPlayers as $key => $player){
			if($player instanceof MineceitPlayer){
				if($player->isOnline() && $function($player)){
					$player->batchDataPacket($packet);
				}
			}
		}
	}


	/**
	 * @param string $messageLocal
	 *
	 * Broadcasts a notice to all of the staff members.
	 *
	 * TODO IMPLEMENT THIS WHEN ANOTHER REPORT OCCURS
	 */
	public static function broadcastNoticeToStaff(string $messageLocal) : void{

		$server = Server::getInstance();
		$players = $server->getOnlinePlayers();

		foreach($players as $player){
			if($player instanceof MineceitPlayer){
				if($player->isOnline() && $player->hasHelperPermissions()){
					$lang = $player->getLanguageInfo()->getLanguage();
					$player->sendPopup($lang->getMessage($messageLocal));
				}
			}
		}
	}


	/**
	 * @param MineceitPlayer $player
	 *
	 * Sends the arrow ding sound to a specific player.
	 */
	public static function sendArrowDingSound(MineceitPlayer $player) : void{

		$packet = new LevelEventPacket();
		$packet->evid = LevelEventPacket::EVENT_SOUND_ORB;
		$packet->data = 1;
		$packet->position = $player->asVector3();

		$player->batchDataPacket($packet);
	}

	/**
	 * @param $player
	 * @param $viewers
	 */
	public static function spawnLightningBolt($player, $viewers) : void{
		$packet = new AddActorPacket();
		$packet->type = "minecraft:lightning_bolt";
		$packet->entityRuntimeId = Entity::$entityCount++;
		$packet->metadata = [];
		$packet->position = new Vector3($player->getX(), $player->getY(), $player->getZ());
		$packet->yaw = $player->getYaw();
		$packet->pitch = $player->getPitch();

		$player->getServer()->broadcastPacket($viewers, $packet);
	}

	/**
	 * @param $player
	 * @param $viewers
	 */
	public static function sprayBlood($player, $viewers) : void{
		$block = new Redstone();
		$packet = new LevelEventPacket;
		$packet->evid = LevelEventPacket::EVENT_PARTICLE_DESTROY;
		$packet->position = new Vector3($player->getX(), $player->getY(), $player->getZ());
		$packet->data = $block->getRuntimeId();

		$player->getServer()->broadcastPacket($viewers, $packet);
	}

	/**
	 * @param int|float $pos
	 * @param int|float $max
	 * @param int|float $min
	 *
	 * @return bool
	 *
	 * Determines whether the player is within a set of bounds.
	 */
	public static function isWithinBounds($pos, $max, $min) : bool{
		return $pos <= $max && $pos >= $min;
	}

	/**
	 * @param string $uuid
	 *
	 * @return Player|null
	 *
	 * Gets the player based on their uuid.
	 */
	public static function getPlayerFromUUID(string $uuid) : ?Player{

		$server = Server::getInstance();
		$players = $server->getOnlinePlayers();
		foreach($players as $player){
			$pUUID = $player->getUniqueId()->toString();
			if($pUUID === $uuid){
				return $player;
			}
		}

		return null;
	}

	/**
	 * @param array $data
	 *
	 * @return ScriptCustomEventPacket
	 *
	 * Constructs a custom packet.
	 */
	public static function constructCustomPkt(array $data) : ScriptCustomEventPacket{

		$pk = new ScriptCustomEventPacket();
		$pk->eventName = self::MINECEIT_SCRIPT_PKT_NAME;
		$buffer = "";
		foreach($data as $key => $value){
			if(is_string($value)){
				$buffer .= Binary::writeShort(strlen($value)) . $value;
			}elseif(is_bool($value)){
				$buffer .= ($value ? "\x01" : "\x00");
			}
		}
		$pk->eventData = $buffer;

		return $pk;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $update
	 *
	 * @return MysqlStream
	 *
	 * Gets the default mysql stream that will be sent when saving/loading data.
	 */
	public static function getMysqlStream(MineceitPlayer $player, bool $update = false) : MysqlStream{

		$mysqlStream = new MysqlStream();
		$lang = !$update ? Language::ENGLISH_US : $player->getLanguageInfo()
			->getLanguage()->getLocale();

		$playerSettings = new MysqlRow("PlayerSettings");
		$playerSettings->put("username", $player->getName());
		$playerSettings->put("language", $lang);

		// Generates the MYSQL Rows.
		$playerStats = $player->getStatsInfo()->generateMYSQLRow($update);
		$playerElo = $player->getEloInfo()->generateMYSQLRow($update);
		// Generates player settings.
		$playerSettings = $player->getSettingsInfo()->generateMYSQLRow($update);
		$player->getDisguiseInfo()->applyToMYSQLRow($playerSettings, $update);

		$vote = new MysqlRow("VoteData");
		$vote->put("username", $player->getName());

		$donate = new MysqlRow("DonateData");
		$donate->put("username", $player->getName());

		$ranks = new MysqlRow("PlayerRanks");
		$ranks->put("username", $player->getName());


		$statement = "username = '{$player->getName()}'";

		if($update){

			# tag sort
			$tags = [];
			$validtags = $player->getValidTags();
			foreach($validtags as $tag){
				if($tag === 'None'){
					if(($key = array_search('None', $validtags)) !== false)
						unset($validtags[$key]);
				}else{
					$tags[] = TextFormat::clean($tag);
				}
			}
			array_multisort($tags, $validtags);
			array_unshift($validtags, 'None');

			# cape sort
			$capes = [];
			$validcapes = $player->getValidCapes();
			foreach($validcapes as $cape){
				if($cape === 'None'){
					if(($key = array_search('None', $validcapes)) !== false)
						unset($validcapes[$key]);
				}else{
					$capes[] = TextFormat::clean($cape);
				}
			}
			array_multisort($capes, $validcapes);
			array_unshift($validcapes, 'None');

			# stuff sort
			$stuffs = [];
			$validstuffs = $player->getValidStuffs();
			foreach($validstuffs as $stuff){
				if($stuff === 'None'){
					if(($key = array_search('None', $validstuffs)) !== false)
						unset($validstuffs[$key]);
				}else{
					$stuffs[] = TextFormat::clean($stuff);
				}
			}
			array_multisort($stuffs, $validstuffs);
			array_unshift($validstuffs, 'None');

			$bpclaimed = $player->getBpClaimed();

			$playerSettings->put("muted", $player->isMuted());
			$playerSettings->put("coinnoti", true);
			$playerSettings->put("expnoti", true);
			$playerSettings->put("silentstaff", $player->isSilentStaffEnabled());
			$playerSettings->put("tag", $player->getTag());
			$playerSettings->put("cape", $player->getCape());
			$playerSettings->put("stuff", $player->getStuff());
			$playerSettings->put("validtags", implode(',', $validtags));
			$playerSettings->put("validcapes", implode(',', $validcapes));
			$playerSettings->put("validstuffs", implode(',', $validstuffs));
			$playerSettings->put("bpclaimed", implode(',', $bpclaimed));
			$playerSettings->put("isbuybp", $player->isBuyBattlePass());
			// TODO:
			$playerSettings->put("potcolor", $player->getPotColor());
			$playerSettings->put("guild", $player->getGuildRegion() . ',' . $player->getGuild());

			$playerRanks = $player->getRanks(true);

			$length = count($playerRanks);

			$count = 0;

			while($count < $length){
				$index = $count + 1;
				$key = "rank{$index}";
				$value = $playerRanks[$count];
				$ranks->put($key, $value);
				$count++;
			}

			$ranks->put("lasttimehosted", $player->getLastTimeHosted());

			$mysqlStream->updateRow($playerSettings, [$statement]);
			$mysqlStream->updateRow($playerStats, [$statement]);
			$mysqlStream->updateRow($ranks, [$statement]);
			$mysqlStream->updateRow($playerElo, [$statement]);
		}else{
			$mysqlStream->insertRow($playerSettings, [$statement]);
			$mysqlStream->insertRow($playerStats, [$statement]);
			$mysqlStream->insertRow($vote, [$statement]);
			$mysqlStream->insertRow($donate, [$statement]);
			// $mysqlStream->insertRow($permissions, [$statement]);
			$mysqlStream->insertRow($ranks, [$statement]);
			$mysqlStream->insertRow($playerElo, [$statement]);
		}

		return $mysqlStream;
	}

	/**
	 * @param MineceitPlayer $player
	 * @param bool           $donate
	 *
	 * @return MysqlStream
	 *
	 * Gets the default mysql stream that will be sent when saving/loading data.
	 */
	public static function getMysqlDonateVoteStream(MineceitPlayer $player, bool $donate = true) : MysqlStream{

		$mysqlStream = new MysqlStream();

		if($donate){
			$donate = new MysqlRow("DonateData");
			$donate->put("username", $player->getName());

			$statement = "username = '{$player->getName()}'";

			$donate->put("time", time());

			$mysqlStream->updateRow($donate, [$statement]);
		}else{
			$vote = new MysqlRow("VoteData");
			$vote->put("username", $player->getName());

			$statement = "username = '{$player->getName()}'";

			$vote->put("time", time());

			$mysqlStream->updateRow($vote, [$statement]);
		}

		return $mysqlStream;
	}

	/**
	 * @param string $dataName
	 * @param array  $loadedData
	 * @param        $data - The stat to load.
	 *
	 * Loads the stat.
	 */
	public static function loadData(string $dataName, array &$loadedData, &$data) : void{
		if(isset($loadedData[$dataName])){
			$data = $loadedData[$dataName];
		}
	}
}
