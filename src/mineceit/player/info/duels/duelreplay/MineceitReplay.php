<?php

declare(strict_types=1);

namespace mineceit\player\info\duels\duelreplay;

use mineceit\game\entities\replay\IReplayEntity;
use mineceit\game\entities\replay\ReplayArrow;
use mineceit\game\entities\replay\ReplayHuman;
use mineceit\game\entities\replay\ReplayItemEntity;
use mineceit\MineceitCore;
use mineceit\MineceitUtil;
use mineceit\player\info\duels\duelreplay\data\BlockData;
use mineceit\player\info\duels\duelreplay\data\PlayerReplayData;
use mineceit\player\info\duels\duelreplay\data\WorldReplayData;
use mineceit\player\info\duels\duelreplay\info\DuelReplayInfo;
use mineceit\player\language\Language;
use mineceit\player\MineceitPlayer;
use mineceit\scoreboard\Scoreboard;
use mineceit\utils\Math;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MineceitReplay{
	/* @var string
	 * The world name
	 */
	private $worldId;

	/* @var WorldReplayData
	 * The data of the world during the duel.
	 */
	private $worldData;

	/* @var ReplayHuman
	 * The human that represents PlayerA.
	 */
	private $humanA;

	/* @var ReplayHuman
	 * The human that represents PlayerB.
	 */
	private $humanB;

	/* @var PlayerReplayData
	 * The data of playerA during the duel.
	 */
	private $playerAData;

	/* @var PlayerReplayData
	 * The data of playerB during the duel.
	 */
	private $playerBData;

	/* @var int
	 * The final tick of the duel.
	 */
	private $endTick;

	/* @var int
	 * The current tick of the replay.
	 */
	private $currentTick;

	/* @var bool
	 * Determines whether the replay is paused or not.
	 */
	private $paused;

	/* @var MineceitPlayer
	 * The player viewing the replay.
	 */
	private $spectator;

	/* @var Position */
	private $centerPosition;

	/* @var Level */
	private $level;

	/* @var int
	 * Tracks the prev integer tick of the replay itself so that
	 * if the time scale is really low then it shouldn't spam
	 * the previous tasks.
	 */
	private $prevReplayIntegerTick;
	/* @var float
	 * Tracks the current tick of the replay itself.
	 */
	private $currentReplayTick;

	/* @var float
	 * Tracks the time scale of the replay.
	 */
	private $replayTimeScale;

	/* @var bool
	 * Determines when the replay starts counting.
	 */
	private $startReplayCount;

	/* @var AbstractKit */
	private $duelKit;

	/* @var float
	 * The replay seconds for rewind and forward.
	 */
	private $replaySecs;


	public function __construct(int $worldId, MineceitPlayer $spectator, DuelReplayInfo $info){
		$this->paused = false;
		$this->spectator = $spectator;
		$this->currentTick = 0;
		$this->prevReplayIntegerTick = 0;
		$this->currentReplayTick = 0.0;
		$this->replayTimeScale = MineceitCore::REPLAY_TIME_SCALE_DEFAULT;
		$this->worldId = $worldId;
		$server = Server::getInstance();
		$this->level = $server->getLevelByName("replay$worldId");
		$this->playerBData = $info->getPlayerBData();
		$this->playerAData = $info->getPlayerAData();
		$this->endTick = $info->getEndTick();
		$this->worldData = $info->getWorldData();
		$this->centerPosition = null;
		$this->startReplayCount = false;
		$this->duelKit = $info->getKit();
		$this->replaySecs = 5;
	}

	public function update() : void{

		$this->currentTick++;

		if($this->startReplayCount && !$this->paused && $this->currentReplayTick <= $this->endTick){
			$this->currentReplayTick += $this->replayTimeScale;
		}

		$replayTickAsInteger = $this->getReplayTickAsInteger();
		if(!$this->spectator->isOnline()){
			$this->endReplay();
			return;
		}

		if($this->currentTick === 5){
			$this->teleportViewer();
		}elseif($this->currentTick === 20){
			$this->loadEntities();
			$this->startReplayCount = true;
		}

		$this->updateTimeScale();
		// Updates the spectator.
		$this->updateSpectator($this->currentTick % 20 === 0);

		if($this->startReplayCount && $this->currentReplayTick <= $this->endTick){
			if(!$this->paused){
				$this->updateHuman($this->humanA, $this->playerAData);
				$this->updateHuman($this->humanB, $this->playerBData);
				$this->updateWorld(false);
			}
		}
		$this->prevReplayIntegerTick = $this->getReplayTickAsInteger();
	}

	/**
	 * Gets the current tick of the replay as an integer.
	 * @return int
	 */
	private function getReplayTickAsInteger() : int{ return intval($this->currentReplayTick); }

	/**
	 * @param bool $resetPlayer
	 *
	 * Ends the replay.
	 */
	public function endReplay(bool $resetPlayer = true) : void{
		if($resetPlayer && $this->spectator !== null && $this->spectator->isOnline()){
			$this->spectator->reset(true, true);
			MineceitCore::getItemHandler()->spawnHubItems($this->spectator);
			$msg = $this->spectator->getLanguageInfo()->getLanguage()->generalMessage(Language::IN_HUB);
			$this->spectator->sendMessage(MineceitUtil::getPrefix() . TextFormat::RESET . ' ' . $msg);
			$this->spectator->getScoreboardInfo()->setScoreboard(
				Scoreboard::SCOREBOARD_SPAWN);
		}

		if($this->humanA !== null){
			$this->humanA->close();
		}

		if($this->humanB !== null){
			$this->humanB->close();
		}

		$this->prevReplayIntegerTick = $this->getReplayTickAsInteger();
		MineceitUtil::deleteLevel($this->level);
		MineceitCore::getReplayManager()->deleteReplay($this->worldId);
	}

	/**
	 * Teleports the viewer to the spectator map.
	 */
	private function teleportViewer() : void{

		$arena = $this->worldData->getArena();

		$this->spectator->getExtensions()->setFakeSpectator();

		MineceitCore::getItemHandler()->spawnReplayItems($this->spectator);

		$sSb = $this->spectator->getScoreboardInfo();
		if($sSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
			$sSb->setScoreboard(Scoreboard::SCOREBOARD_REPLAY);
		}

		$spawnPos = $arena->getP1SpawnPos();
		$p1x = $spawnPos->getX();
		$y = $spawnPos->getY();
		$p1z = $spawnPos->getZ();

		$spawnPos = $arena->getP2SpawnPos();
		$p2x = $spawnPos->getX();
		$p2z = $spawnPos->getZ();

		$this->centerPosition = new Position(intval((($p2x + $p1x) / 2)), $y, intval((($p2z + $p1z) / 2)), $this->level);

		$center = $this->centerPosition;

		$centerX = intval($this->centerPosition->x);
		$centerZ = intval($this->centerPosition->z);

		MineceitUtil::onChunkGenerated($this->level, $centerX >> 4, $centerZ >> 4, function() use ($center){
			$this->spectator->teleport($center);
		});

		if($this->spectator->getLevel()->getName() !== $this->worldId)
			$this->spectator->teleport($center);
	}

	private function loadEntities() : void{

		$humanANBT = ReplayHuman::getHumanNBT($this->playerAData);
		$humanBNBT = ReplayHuman::getHumanNBT($this->playerBData);

		$humanAStartPosition = $this->playerAData->getStartPosition();
		$humanBStartPosition = $this->playerBData->getStartPosition();

		if(!$this->level->isInLoadedTerrain($humanAStartPosition))
			$this->level->loadChunk($humanAStartPosition->x >> 4, $humanAStartPosition->z >> 4);

		if(!$this->level->isInLoadedTerrain($humanBStartPosition))
			$this->level->loadChunk($humanBStartPosition->x >> 4, $humanBStartPosition->z >> 4);

		$this->humanA = Entity::createEntity("ReplayHuman", $this->level, $humanANBT);
		$this->humanA->spawnToAll();
		$humanASkin = $this->playerAData->getSkin();

		if($humanASkin instanceof Skin)
			$this->humanA->setSkin($humanASkin);

		$this->humanB = Entity::createEntity("ReplayHuman", $this->level, $humanBNBT);
		$this->humanB->spawnToAll();
		$humanBSkin = $this->playerBData->getSkin();

		if($humanBSkin instanceof Skin)
			$this->humanB->setSkin($humanBSkin);

		$this->initInventories();
	}

	private function initInventories() : void{
		$this->initHumanInventory($this->humanA, $this->playerAData);
		$this->initHumanInventory($this->humanB, $this->playerBData);
	}

	private function initHumanInventory(ReplayHuman &$human, PlayerReplayData &$replayData){
		$inv = $human->getInventory();
		$startInv = $replayData->getStartInventory();
		$armorInv = $human->getArmorInventory();
		$startArmorInv = $replayData->getArmorInventory();

		$count = 0;
		$len = count($startInv);
		while($count < $len){
			$i = $startInv[$count];
			$inv->setItem($count, $i);
			$count++;
		}

		$count = 0;
		$len = count($startArmorInv);
		while($count < $len){
			$i = $startArmorInv[$count];
			$armorInv->setItem($count, $i);
			$count++;
		}
		$armorInv->sendContents($human->getViewers());
		$inv->sendContents($human->getViewers());
	}

	private function updateTimeScale(){
		$entities = $this->level->getEntities();
		foreach($entities as $entity){
			if($entity instanceof IReplayEntity){
				$entity->setTimeScale($this->replayTimeScale);
			}
		}
	}

	private function updateSpectator(bool $updateScoreboard) : void{
		// TODO: Keyframe implementation goes here.

		if($updateScoreboard){
			$color = MineceitUtil::getThemeColor();
			$durationBottom = $color . ' ' . $this->getDuration() . TextFormat::WHITE . ' | ' . $color . $this->getMaxDuration() . ' ';
			$this->spectator->getScoreboardInfo()->updateLineOfScoreboard(2, $durationBottom);
		}
	}

	/**
	 * @return string
	 */
	public function getDuration() : string{
		$durationSeconds = intval($this->currentReplayTick / 20) - 5;
		if($durationSeconds < 0) $durationSeconds = 0;

		$seconds = $durationSeconds % 60;
		$minutes = intval($durationSeconds / 60);

		$color = MineceitUtil::getThemeColor();

		$result = $color . '%min%:%sec%';

		$secStr = "$seconds";
		$minStr = "$minutes";

		if($seconds < 10)
			$secStr = '0' . $seconds;

		if($minutes < 10)
			$minStr = '0' . $minutes;

		return str_replace('%min%', $minStr, str_replace('%sec%', $secStr, $result));
	}

	/**
	 * @return string
	 */
	public function getMaxDuration() : string{

		$durationSeconds = intval($this->endTick / 20) - 5;

		if($durationSeconds < 0) $durationSeconds = 0;

		$seconds = $durationSeconds % 60;
		$minutes = intval($durationSeconds / 60);

		$color = MineceitUtil::getThemeColor();

		$result = $color . '%min%:%sec%';

		$secStr = "$seconds";
		$minStr = "$minutes";

		if($seconds < 10)
			$secStr = '0' . $seconds;

		if($minutes < 10)
			$minStr = '0' . $minutes;

		return str_replace('%min%', $minStr, str_replace('%sec%', $secStr, $result));
	}

	/**
	 * @param ReplayHuman      $human
	 * @param PlayerReplayData $data
	 *
	 * Updates the human according to current replay time.
	 */
	private function updateHuman(ReplayHuman $human, PlayerReplayData $data) : void{
		if($human === null || $human->isClosed()){
			return;
		}

		$replayTickAsInteger = $this->getReplayTickAsInteger();
		$tickDifference = abs($replayTickAsInteger - $this->prevReplayIntegerTick);
		$attributes = $data->getAttributesAt($replayTickAsInteger);
		$deathTime = $data->getDeathTime();

		if($data->didDie()){
			if($this->currentReplayTick >= $deathTime || $this->currentReplayTick >= $this->endTick){
				$human->setInvisible(true);
				if($human->isOnFire()){
					$human->extinguish();
				}

				$inv = $human->getInventory();
				if($inv !== null){
					$inv->setItemInHand(Item::get(Item::AIR));
					$inv->sendHeldItem($human->getViewers());
				}
				$armorInv = $human->getArmorInventory();
				if($armorInv !== null){
					$armorInv->setHelmet(Item::get(Item::AIR));
					$armorInv->setChestplate(Item::get(Item::AIR));
					$armorInv->setLeggings(Item::get(Item::AIR));
					$armorInv->setBoots(Item::get(Item::AIR));
				}
			}else{
				if($human->isInvisible()){
					$human->setInvisible(false);
				}
			}
		}

		if($tickDifference > 0){
			if(isset($attributes['tp']))
				$human->teleport($attributes['tp']);

			if(isset($attributes['bow'])){
				$human->setReleaseBow($attributes['bow']);
			}

			if(isset($attributes['thrown'])){
				$value = $attributes['thrown'];
				$human->onClickAir($value, $human->getDirectionVector());
			}

			if(isset($attributes['sneak'])){
				$value = $attributes['sneak'];
				$human->setSneaking($value);
			}

			if(isset($attributes['drop'])){
				$drops = $attributes['drop'];
				$item = $drops['item'];
				$motion = $drops['motion'];
				$pickup = isset($drops['pickup']) ? $drops['pickup'] : null;
				// TODO: Check replay tick parameter of drop item
				MineceitUtil::dropItem($this->level, $human->add(0, 1.3, 0), $item, $motion, $this->currentReplayTick, $human, $pickup);
			}

			if(isset($attributes['nameTag'])){
				$tag = $attributes['nameTag'];
				$human->setNameTag($tag);
			}

			if(isset($attributes['item'])){
				$inv = $human->getInventory();
				$item = $attributes['item'];
				$inv->setItemInHand($item);
				$inv->sendHeldItem($human->getViewers());
			}

			if(isset($attributes['animation'])){
				$animation = $attributes['animation'];
				switch($animation){
					case AnimatePacket::ACTION_SWING_ARM:
						$human->broadcastEntityEvent(ActorEventPacket::ARM_SWING);
						break;
				}
			}

			if(isset($attributes['damaged'])){
				$human->broadcastEntityEvent(ActorEventPacket::HURT_ANIMATION);
				$this->level->broadcastLevelSoundEvent($human->asVector3(), LevelSoundEventPacket::SOUND_HURT);
			}

			if(isset($attributes['armor'])){
				$armor = $attributes['armor'];
				$armorInv = $human->getArmorInventory();
				if(isset($armor['helmet']))
					$armorInv->setHelmet($armor['helmet']);
				if(isset($armor['chest']))
					$armorInv->setChestplate($armor['chest']);
				if(isset($armor['pants']))
					$armorInv->setLeggings($armor['pants']);
				if(isset($armor['boots']))
					$armorInv->setBoots($armor['boots']);
			}

			if(isset($attributes['fishing'])){
				$fishing = $attributes['fishing'];
				ReplayHuman::useRod($human, $fishing);
			}

			if(isset($attributes['consumed'])){
				$drank = $attributes['consumed'];
				$sound = $drank ? LevelSoundEventPacket::SOUND_DRINK : LevelSoundEventPacket::SOUND_EAT;
				$this->level->broadcastLevelSoundEvent($human->asVector3(), $sound);
			}
		}

		if(isset($attributes['rotation'])){
			$rotation = $attributes['rotation'];
			if($this->replayTimeScale >= 1.0){
				$human->setRotation($rotation['yaw'], $rotation['pitch']);
			}else{
				$humanLocation = $human->getLocation();
				$yawDifference = $rotation['yaw'] - $humanLocation->yaw;
				$pitchDifference = $rotation['pitch'] - $humanLocation->pitch;
				$newYaw = $humanLocation->yaw + $yawDifference * $this->replayTimeScale;
				$newPitch = $humanLocation->pitch + $pitchDifference * $this->replayTimeScale;
				$human->setRotation($newYaw, $newPitch);
			}
		}

		if(isset($attributes['fire'])){
			$fire = $attributes['fire'];
			if(is_int($fire)){
				// $fire doesn't matter since we are setting it on fire every tick it should be on fire.
				$human->setOnFire($fire);
			}elseif(is_bool($fire)){
				$human->extinguish();
			}
		}else{
			$fire = $data->getLastAttributeUpdate($replayTickAsInteger, 'fire');
			if($fire !== null){
				if(is_int($fire))
					$human->setOnFire($fire);
				elseif(is_bool($fire))
					$human->extinguish();
			}else{
				if($human->isOnFire()) $human->extinguish();
			}
		}

		if(isset($attributes['effects'])){
			$effects = $attributes['effects'];
			$keys = array_keys($effects);
			foreach($keys as $key){
				/* @var EffectInstance $effect */
				$effect = $effects[$key];
				$human->addEffect($effect);
			}
		}

		if(isset($attributes['position'])){
			$position = $attributes['position'];
			if($this->replayTimeScale > 1.0){
				$currentTargetPosition = $human->getTargetPosition();
				$alphaLerpedTarget = 1.0 / $this->replayTimeScale;
				$position = Math::lerp($currentTargetPosition, $position, $alphaLerpedTarget);
			}
			$human->setTargetPosition($position);
		}

		if(isset($attributes['motion'])){
			$motion = $attributes['motion'];
			$human->setTargetMotion($motion);
		}
	}

	/**
	 *
	 * @param bool $updatePause
	 * @param bool $approximateBlockUpdates
	 */
	public function updateWorld(bool $updatePause = true, bool $approximateBlockUpdates = false) : void{

		$replayTickAsInteger = $this->getReplayTickAsInteger();
		$oldTick = $replayTickAsInteger - (int) $this->replayTimeScale;

		while($oldTick <= $replayTickAsInteger){
			/* @var BlockData[] $blocks */
			$blocks = $this->worldData->getBlocksAt($oldTick, $approximateBlockUpdates);

			foreach($blocks as $key => $block){
				$position = $block->getPosition();
				$currentBlock = $this->level->getBlock($position);
				$bl = $block->getBlock();

				if($currentBlock->getId() !== $bl->getId() || $currentBlock->getDamage() !== $bl->getDamage()){
					$this->level->setBlock($position, $bl);
				}
			}

			$oldTick++;
		}


		$entities = $this->level->getEntities();
		foreach($entities as $e){
			if($e instanceof IReplayEntity){
				if($updatePause){
					if($e instanceof ReplayArrow
						&& $e->isOnGround()){
						$e->close();
						continue;
					}

					if($e instanceof ReplayItemEntity
						&& $e->shouldDespawn($this->currentReplayTick)){
						$e->close();
						continue;
					}
				}else{
					if($e instanceof ReplayItemEntity){
						$e->updatePickup($this->currentReplayTick);
					}
				}
				$e->setPaused($updatePause);
			}
		}
	}

	/**
	 * @return MineceitPlayer
	 *
	 * The spectator.
	 */
	public function getSpectator() : MineceitPlayer{
		return $this->spectator;
	}

	/**
	 * @return float
	 *
	 * The replay seconds.
	 */
	public function getReplaySecs() : float{
		return $this->replaySecs;
	}

	/**
	 * @param float|int $secs
	 * Sets the replay seconds.
	 */
	public function setReplaySecs($secs) : void{
		$this->replaySecs = $secs;
	}

	/**
	 *
	 * Rewinds the time to a specific point.
	 */
	public function rewind() : void{
		$newTicks = -($this->replaySecs * 20) + $this->currentReplayTick;
		$this->setTicks($newTicks);
	}

	/**
	 * @param int|float $ticks
	 * Sets the ticks of the replay.
	 */
	private function setTicks($ticks) : void{
		if($ticks <= 0){
			$ticks = 0;
		}elseif($ticks >= $this->endTick){
			$ticks = $this->endTick;
		}

		$this->currentReplayTick = $ticks;
		$prevReplayFloatTick = $this->currentReplayTick - $this->replayTimeScale;
		if($prevReplayFloatTick <= 0.0){
			$prevReplayFloatTick = 0.0;
		}
		$this->prevReplayIntegerTick = intval($prevReplayFloatTick);

		if($this->humanA !== null && $this->humanB !== null){
			if($this->spectator !== null && $this->spectator->isOnline()){
				$this->updateSpectator(true);
			}

			$this->updateHumanAfterTickChange($this->humanA, $this->playerAData);
			$this->updateHumanAfterTickChange($this->humanB, $this->playerBData);
			$this->updateWorld($this->paused, true);
		}

		// Updates before and after just so there isn't any discrepancy.
		$this->prevReplayIntegerTick = $this->getReplayTickAsInteger();
	}

	/**
	 * Updates the human after a significant difference in tick change (Ex: After a rewind or a fast-forward).
	 *
	 * @param ReplayHuman      $human
	 * @param PlayerReplayData $replayPlayerData
	 */
	private function updateHumanAfterTickChange(ReplayHuman $human, PlayerReplayData &$replayPlayerData) : void{

		if($human === null || $human->isClosed()){
			return;
		}

		$human->setSneaking(false);
		$replayTickAsInteger = $this->getReplayTickAsInteger();
		$attributes = $replayPlayerData->getAttributesAt($replayTickAsInteger);
		$deathTime = $replayPlayerData->getDeathTime();

		if($replayPlayerData->didDie()){
			// Not needed to be converted as an integer since its only a greater than check.
			if($this->currentReplayTick >= $deathTime || $this->currentReplayTick >= $this->endTick){
				$human->setInvisible(true);
			}else{
				if($human->isInvisible()){
					$human->setInvisible(false);
				}
			}
		}

		if(isset($attributes['tp']))
			$human->teleport($attributes['tp']);

		if($this->currentReplayTick <= 0.0){
			$human->resetPosition();
		}else if(isset($attributes['position'])){
			$position = $attributes['position'];
			$human->teleport($position);
			$human->setTargetPosition($position);
		}

		if(isset($attributes['motion'])){
			$motion = $attributes['motion'];
			$human->setTargetMotion($motion);
		}

		if(isset($attributes['rotation'])){
			$rotation = $attributes['rotation'];
			$human->setRotation($rotation['yaw'], $rotation['pitch']);
		}else{
			$lastRotation = $replayPlayerData->getLastAttributeUpdate($replayTickAsInteger, 'rotation');
			if($lastRotation !== null){
				$human->setRotation($lastRotation['yaw'], $lastRotation['pitch']);
			}
		}

		$inv = $human->getInventory();

		if(isset($attributes['item'])){
			$item = $attributes['item'];
			$inv->setItemInHand($item);
			$inv->sendHeldItem($human->getViewers());
		}else{
			$lastItem = $replayPlayerData->getLastAttributeUpdate($replayTickAsInteger, 'item');
			if($lastItem !== null){
				$inv->setItemInHand($lastItem);
				$inv->sendHeldItem($human->getViewers());
			}
		}

		if(isset($attributes['fishing'])){
			$fishing = $attributes['fishing'];
			ReplayHuman::useRod($human, $fishing);
		}else{
			$fishing = $replayPlayerData->getLastAttributeUpdate($replayTickAsInteger, 'fishing');
			if($fishing !== null){
				ReplayHuman::useRod($human, $fishing);
			}
		}

		if(isset($attributes['nameTag'])){
			$tag = $attributes['nameTag'];
			$human->setNameTag($tag);
		}else{
			$lastNameTag = $replayPlayerData->getLastAttributeUpdate($replayTickAsInteger, 'nameTag');
			if($lastNameTag !== null)
				$human->setNameTag($lastNameTag);
		}

		if(isset($attributes['fire'])){
			$fire = $attributes['fire'];
			if(is_int($fire))
				$human->setOnFire($fire);
			elseif(is_bool($fire))
				$human->extinguish();
		}else{
			$fire = $replayPlayerData->getLastAttributeUpdate($replayTickAsInteger, 'fire');
			if($fire !== null){
				if(is_int($fire))
					$human->setOnFire($fire);
				elseif(is_bool($fire))
					$human->extinguish();
			}else{
				if($human->isOnFire()) $human->extinguish();
			}
		}

		$armorInv = $human->getArmorInventory();

		if(isset($attributes['armor'])){
			$armor = $attributes['armor'];
			if(isset($armor['helmet']))
				$armorInv->setHelmet($armor['helmet']);
			if(isset($armor['chest']))
				$armorInv->setChestplate($armor['chest']);
			if(isset($armor['pants']))
				$armorInv->setLeggings($armor['pants']);
			if(isset($armor['boots']))
				$armorInv->setBoots($armor['boots']);
		}else{
			$armor = $replayPlayerData->getLastAttributeUpdate($replayTickAsInteger, 'armor');
			if($armor !== null){
				if(isset($armor['helmet']))
					$armorInv->setHelmet($armor['helmet']);
				if(isset($armor['chest']))
					$armorInv->setChestplate($armor['chest']);
				if(isset($armor['pants']))
					$armorInv->setLeggings($armor['pants']);
				if(isset($armor['boots']))
					$armorInv->setBoots($armor['boots']);
			}
		}

		if(isset($attributes['effects'])){
			$effects = $attributes['effects'];
			$keys = array_keys($effects);
			foreach($keys as $key){
				/* @var EffectInstance $effect */
				$effect = $effects[$key];
				$human->addEffect($effect);
			}
		}else{
			$effects = $replayPlayerData->getLastAttributeUpdate($replayTickAsInteger, 'effects');
			if($effects !== null){
				$keys = array_keys($effects);
				foreach($keys as $key){
					/* @var EffectInstance $effect */
					$effect = $effects[$key];
					$human->addEffect($effect);
				}
			}
		}

		if(isset($attributes['sneak'])){
			$sneak = $attributes['sneak'];
			$human->setSneaking($sneak);
		}else{
			$sneak = $replayPlayerData->getLastAttributeUpdate($replayTickAsInteger, 'sneak');
			if($sneak !== null)
				$human->setSneaking($sneak);
		}
	}

	/**
	 *
	 * Fast forwards the time to a specific point.
	 */
	public function fastForward() : void{
		$newTicks = ($this->replaySecs * 20) + $this->currentReplayTick;
		$this->setTicks($newTicks);
	}

	/**
	 * @return string
	 */
	public function getQueue() : string{
		return $this->duelKit->getName();
	}

	/**
	 * @return bool
	 */
	public function isRanked() : bool{
		return $this->worldData->isRanked();
	}

	/**
	 * @var float|int $timeScale
	 * Sets the time scale of the replay.
	 */
	public function setTimeScale($timeScale) : void{
		$this->replayTimeScale = $timeScale;
	}

	/**
	 * Gets the time scale of the replay.
	 */
	public function getTimeScale() : float{ return $this->replayTimeScale; }

	/**
	 * @return bool
	 */
	public function isPaused() : bool{
		return $this->paused;
	}

	/**
	 * @param bool $paused
	 *
	 * Sets the rewind to pause.
	 */
	public function setPaused(bool $paused) : void{

		$itemHandler = MineceitCore::getItemHandler();

		if($this->paused){
			$itemHandler->givePauseItem($this->spectator);
			$this->spectator->getScoreboardInfo()->removePausedFromScoreboard();
		}else{
			$itemHandler->givePlayItem($this->spectator);
			$this->spectator->getScoreboardInfo()->addPausedToScoreboard();
		}

		$this->updateWorld($paused);
		$this->paused = $paused;
	}

	/**
	 * @param int|float $seconds
	 *
	 * Sets the replay time to a specific point based in seconds.
	 */
	private function setSeconds($seconds) : void{
		$this->setTicks($seconds * 20);
	}
}
