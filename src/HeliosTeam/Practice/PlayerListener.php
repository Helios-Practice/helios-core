<?php

declare(strict_types=1);

namespace HeliosTeam\Practice;

use HeliosTeam\Practice\Entity\SplashPotion;
use HeliosTeam\Practice\Utils\Device;
use Libs\Webhooks\Embed;
use Libs\Webhooks\Message;
use Libs\Webhooks\Webhook;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;
use pocketmine\level\Location;
use pocketmine\utils\TextFormat;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\utils\Config;

class PLayerListener implements Listener {

    private $plugin;
    public $setspawns;
    public $duelStickPlayers;
    public $clicks;
    public $freeze = array();

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event): void {

        if(!is_dir($this->getPlugin()->getDataFolder() . "ipdb/")){
            @mkdir($this->getPlugin()->getDataFolder() . "ipdb/", 0777, true);
        }
        if(!is_dir($this->getPlugin()->getDataFolder() . "playerdata/")){
            @mkdir($this->getPlugin()->getDataFolder() . "playerdata/", 0777, true);
        }

        $player = $event->getPlayer();
        $name = $event->getPlayer()->getDisplayName();

        $player->setFood(20);
        $player->setXpProgress(0);
        $player->setHealth(20);
        $player->setXpLevel(0);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->removeAllEffects();

        $event->setJoinMessage("§8[§2+§8] §a" . $player->getName());

        $ip = $event->getPlayer()->getAddress();
        $file0 = new Config($this->getPlugin()->getDataFolder() . "ipdb/" . $ip . ".txt", CONFIG::ENUM);
        $file0->set($name);
        $file0->save();

        $cid = $event->getPlayer()->getClientId();
        $xuid = $event->getPlayer()->getXuid();

        $ip = $player->getPlayer()->getAddress();
        $file = new Config($this->getPlugin()->getDataFolder() . "ipdb/" . $ip . ".txt");
        $names = $file->getAll(true);
        $aliases = implode(', ', $names);

        $arr = [$name, $ip, $cid, $xuid];
        $arr[] = $aliases;

        $file1 = new Config($this->getPlugin()->getDataFolder() . "playerdata/" . $name . ".yml", CONFIG::YAML);
        $file1->set("Player Data", $arr);
        $file1->save();

        $name = $event->getPlayer()->getName();
        $this->getPlugin()->getServer()->dispatchCommand($player, "hub");
        $player->sendMessage("§aWelcome to Helios, " . $player->getName() . "!");
        $this->getPlugin()->sendMenu($event->getPlayer());
        $config = $this->getPlugin()->getConfig();
        $players = $this->getPlugin()->getPlayers()->getAll();
        $playersInfo = $this->getPlugin()->getPlayersInfo()->getAll();
        $activeduels = $this->getPlugin()->getActiveDuels()->getAll();
        foreach($this->getPlugin()->getKits()->getAll(true) as $kit) {

            if(!isset($players[$name][$kit])) $this->getPlugin()->getPlayers()->setNested("$name.$kit", (int)$this->getPlugin()->getConfig()->get("default-elo"));
            if(!isset($playersInfo[$name][$kit]["wins"])) $this->getPlugin()->getPlayersInfo()->setNested("$name.$kit.wins", 0);
            if(!isset($playersInfo[$name][$kit]["loses"])) $this->getPlugin()->getPlayersInfo()->setNested("$name.$kit.loses", 0);
        }

        if(!isset($playersInfo[$name]["ranked-played"])) $this->getPlugin()->getPlayersInfo()->setNested("$name.ranked-played", 0);

        $this->getPlugin()->getPlayers()->save();
        $this->getPlugin()->getPlayersInfo()->save();
        $this->getPlugin()->pureChat->setPrefix($this->getPlugin()->getRankPrefix($name), $event->getPlayer());
        $specitem = Item::get(268, 0, 1);
        $specitem->setCustomName("§r§l§aSpectate");

        $eventitem = Item::get(450, 0, 1);
        $eventitem->setCustomName("§r§l§aJoin Sumo Event");
        $player->getInventory()->setItem(5, $eventitem);

        $player->getInventory()->setItem(2, $specitem);
        $this->getPlugin()->getScoreboardUtil()->setLobbyScoreboard($player);
        $player->sendTitle("§aHelios", "§2Welcome!", 10, 30, 10);
        $player->getLevel()->setTime(7000);
        $player->getLevel()->stopTime();
        $eventitem = Item::get(450, 0, 1);
        $eventitem->setCustomName("§r§l§aJoin Sumo Event");
        $player->getInventory()->setItem(5, $eventitem);
        foreach ($this->getPlugin()->getServer()->getLevels() as $level) {
            $this->getPlugin()->getServer()->loadLevel($level->getName());
            $level->setTime(7000);
            $level->stopTime();
        }
    }

    public function onArenaSetting(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $level = $player->getLevel();
        $block = $event->getBlock();
        if(isset($this->setspawns[$name])){

            $arena = $this->setspawns[$name][0];
            $spawns = $this->getPlugin()->getArenasConfig()->getNested("$arena.spawns");

            $spawns[] = [$block->getX(), $block->getFloorY() + 1, $block->getZ()];
            $this->getPlugin()->getArenasConfig()->setNested("$arena.spawns", $spawns);
            if($this->setspawns[$name][1] === 2) $player->sendMessage($this->getPlugin()->getPrefix() . TextFormat::YELLOW . "First spawn has been set. Now set the second spawn!");
            $this->setspawns[$name][1]--;
            if($this->setspawns[$name][1] <= 0){
                $player->teleport($this->getPlugin()->getServer()->getDefaultLevel()->getSpawnLocation());
                unset($this->setspawns[$name]);
                $this->getPlugin()->getArenasConfig()->save();
                $this->getPlugin()->addArena($arena, $this->getPlugin()->getArenasConfig()->getNested("$arena.world"), $this->getPlugin()->getArenasConfig()->getNested("$arena.spawns"), $this->getPlugin()->getArenasConfig()->getNested("$arena.kit"), $this->getPlugin()->getArenasConfig()->getNested("$arena.type"));
                $player->sendMessage($this->getPlugin()->getPrefix() . TextFormat::YELLOW . "Arena " . TextFormat::RED . $arena . TextFormat::YELLOW . " have been set!");
            }
        }
    }

    public function onDeathMessage(PlayerDeathEvent $event) {
        $event->setDeathMessage(null);
    }

    public function onRespawn(PlayerRespawnEvent $event) {
        $player = $event->getPlayer();
        $this->getPlugin()->getServer()->dispatchCommand($player, "hub");
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($player instanceof Player && $item->getCustomName() === "§r§l§cReturn to lobby") {
            $this->getPlugin()->getServer()->dispatchCommand($player, "hub");
        }
    }

    public function onSumoEventInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($player instanceof Player && $item->getCustomName() === "§r§l§aJoin Sumo Event") {
            $this->getPlugin()->getServer()->dispatchCommand($player, "sumo join");
        }
    }

    public function onInteract2(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($player instanceof Player && $item->getCustomName() === "§r§l§cLeave staff mode") {
            $this->getPlugin()->getServer()->dispatchCommand($player, "vanish");
            $this->getPlugin()->sendMenu($player);
            $player->setAllowFlight(false);
            $player->setFlying(false);
            $player->setGamemode(2);
        }
    }

    public function onInteract5(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($player instanceof Player && $item->getCustomName() === "§r§l§aReport Manager") {
            $this->getPlugin()->getServer()->dispatchCommand($player, "helios list");
        }
    }

    public function soup(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        switch ($item->getId()) {
            case Item::MUSHROOM_STEW:
                $item->setCount($item->getCount() - 1);
                $player->setHealth($player->getHealth() + 8);
        }
    }

    public function onInteract3(PlayerInteractEvent $event): void {
        $action = $event->getAction();
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        $queueItem = explode(":", $this->getPlugin()->getConfig()->get("queue-item"));
        $unrankedItem = explode(":", $this->getPlugin()->getConfig()->get("unranked-item"));
        $ffaItem = explode(":", $this->getPlugin()->getConfig()->get("ffa-item"));
        $cosmeticsItem = explode(":", $this->getPlugin()->getConfig()->get("cosmetics-item"));
        $statslbItem = explode(":", $this->getPlugin()->getConfig()->get("statslb-item"));
        $modsItem = explode(":", $this->getPlugin()->getConfig()->get("mods-item"));
        $specitem = Item::get(268, 0, 1);
        $specitem->setCustomName("§r§l§aSpectate");

        if($player->getLevel() !== $this->getPlugin()->getServer()->getDefaultLevel()) {
            return;
        }

        if ($action == PlayerInteractEvent::RIGHT_CLICK_AIR) {
            switch($item->getid()) {
                case (int)$queueItem[1]:
                    $this->getPlugin()->getServer()->getCommandMap()->dispatch($player, "practice quit");
                    $this->getPlugin()->RemoveQueueItem($player);
                    break;
                case (int)$unrankedItem[1]:
                    $this->getPlugin()->getUI()->kitJoinUI($player, "unranked");
                    $this->getPlugin()->getScoreboardUtil()->setLobbyScoreboard($player);
                    break;
                case (int)$ffaItem[1]:
                    $this->getPlugin()->getUI()->ffaForm($player);
                    $this->getPlugin()->getScoreboardUtil()->setLobbyScoreboard($player);
                    break;
                case (int)$cosmeticsItem[1]:
                    $player->sendMessage("§cCosmetics coming soon!");
                    break;
                case (int)$statslbItem[1]:
                    $player->sendMessage("§cDuels stats coming soon!");
                    break;
                case (int)$modsItem[1]:
                    $player->sendMessage("§cMod menu coming soon!");
                    break;
            }
        }
    }

    public function onSpecInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $action = $event->getAction();
        if ($action == PlayerInteractEvent::RIGHT_CLICK_AIR) {
            if ($item->getCustomName() === "§r§l§aSpectate") {
                $this->getPlugin()->getUI()->spectateForm($player);
                $this->getPlugin()->getScoreboardUtil()->setLobbyScoreboard($player);
            }
        }
    }

    public function onEventInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $action = $event->getAction();
        if ($action == PlayerInteractEvent::RIGHT_CLICK_AIR) {
            if ($item->getCustomName() === "§r§l§aJoin Sumo Event") {
                $this->getPlugin()->getServer()->dispatchCommand($player, "sumo join");
                $this->getPlugin()->getScoreboardUtil()->setEventScoreboard($player);
            }
        }
    }

    public function onMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        $arena = $this->getPlugin()->getArenaByPlayer($player);
        if($arena and !$arena->isIdle() and $arena->getCountdown() > 0) {
            if($player->isSneaking()) $event->setCancelled();
            $player->setImmobile(true);
            //$event->setTo(Location::fromObject($event->getFrom()->setComponents($event->getFrom()->x, $event->getTo()->y, $event->getFrom()->z), $event->getFrom()->level, $event->getTo()->yaw, $event->getTo()->pitch));
        }
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $name = $event->getPlayer()->getName();
        $player = $event->getPlayer();
        $arena = $this->getPlugin()->getArenaByPlayer($player);
        $event->setQuitMessage("§8[§4-§8] §c" . $player->getName());
                $this->getPlugin()->getActiveFFA()->remove("$name");
                $this->getPlugin()->getActiveFFA()->save();
        if($arena) {
            $arena->quit($player);
        }
    }

    public function onRespawn2(PlayerRespawnEvent $event): void {
        $this->getPlugin()->sendMenu($event->getPlayer());
        $eventitem = Item::get(450, 0, 1);
        $eventitem->setCustomName("§r§l§aJoin Sumo Event");
        $event->getPlayer()->getInventory()->setItem(5, $eventitem);
        $player = $event->getPlayer();
        $name = $event->getPlayer()->getName();
            $this->getPlugin()->getActiveFFA()->remove("$name");
            $this->getPlugin()->getActiveFFA()->save();
    }

    public function onDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $arena = $this->getPlugin()->getArenaByPlayer($player);
        if($arena and $arena->isRunning()){
            $arena->quit($player, true);
            $event->setDrops([]);
        }

        /*$cause = $player->getLastDamageCause();
        $player->setXpLevel(0);
        $event->setXpDropAmount(0);
        if ($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            $hp = $killer->getHealth();
            if ($killer instanceof Player) {
                $killer->setHealth(20);
                $kname = $killer->getName();
                if ($farena = $this->getPlugin()->getActiveFFA()->getNested("$kname.none") === "abc") {
                    $arenaxname = $this->getPlugin()->getActiveFFA()->getNested("$kname.arena");
                    if ($arenaxname === "NoDebuff") {
                        foreach ($killer->getInventory()->getContents() as $item) {
                            if($item->getId() === 438 and $item->getDamage() === 22) {
                                $event->setDeathMessage("§7" . $player->getName() . " §7was killed by " . $killer->getName() . "§7.");
                            }
                        }
                    } else {
                        $event->setDeathMessage("§7" . $player->getName() . " §7was killed by " . $killer->getName() . "§7.");
                    }
                    $this->getPlugin()->addFFAKit($killer, $this->getPlugin()->getFFAArenasConfig()->getNested("$arenaxname.kit"));
                } else {
                    $event->setDeathMessage("§7" . $player->getName() . " §7was killed by " . $killer->getName() . "§7.");
                }
            }
        }
        $light = new AddActorPacket(); // ty optical :D
        $light->type = "minecraft:lightning_bolt";
        $light->entityRuntimeId = Entity::$entityCount++;
        $light->metadata = [];
        $light->motion = null;
        $light->yaw = $player->getYaw();
        $light->pitch = $player->getPitch();

        $light->position = new Position($player->getX(), $player->getY(), $player->getZ());
        $sound = new PlaySoundPacket();
        $sound->soundName = "ambient.weather.thunder";
        $sound->x = $player->getX();
        $sound->y = $player->getY();
        $sound->z = $player->getZ();
        $sound->volume = 1;
        $sound->pitch = 1;
        $this->plugin->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $light);
        $this->plugin->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $sound);
        $player->setCurrentTotalXp(0);
        $event->setDrops([]);*/
    }

    public function removeDeathScreen(EntityDamageEvent $event) {
        $victim = $event->getEntity();
        if ($victim->getLevel()->getFolderName() === "Sumo-Event") return;
        if ($event->getFinalDamage() >= $victim->getHealth()) {
            $event->setCancelled();
            if ($victim instanceof Player) {
                if ($event instanceof EntityDamageByEntityEvent) {
                    $attacker = $event->getDamager();
                    if ($attacker instanceof Player) {
                        $messages = ["quickied", "mopped", "blown to smithereens", "clowned", "handed an L", "crapped on", "necked", "rejected", "destroyed", "killed", "w-tapped", "comboed", "annihilated", "clipped", "railed", "strafed on", "wrecked", "eternitied", "surpassed", "wexied"];
                        $this->getPlugin()->getServer()->dispatchCommand($attacker, "rekit");
                        $this->getPlugin()->getServer()->broadcastMessage("§7" . $victim->getName() . " §7was " . $messages[array_rand($messages)] . " by " . $attacker->getName() . "§7.");
                    }

                    $light = new AddActorPacket();
                    $light->type = "minecraft:lightning_bolt";
                    $light->entityRuntimeId = Entity::$entityCount++;
                    $light->metadata = [];
                    $light->motion = null;
                    $light->yaw = $victim->getYaw();
                    $light->pitch = $victim->getPitch();
                    $light->position = new Position($victim->getX(), $victim->getY(), $victim->getZ());
                    $sound = new PlaySoundPacket();
                    $sound->soundName = "ambient.weather.thunder";
                    $sound->x = $victim->getX();
                    $sound->y = $victim->getY();
                    $sound->z = $victim->getZ();
                    $sound->volume = 1;
                    $sound->pitch = 1;
                    $this->plugin->getServer()->broadcastPacket($victim->getLevel()->getPlayers(), $light);
                    $this->plugin->getServer()->broadcastPacket($victim->getLevel()->getPlayers(), $sound);

                    $victim->setCurrentTotalXp(0);
                    $victim->setXpProgress(0);
                    $victim->setXpLevel(0);
                    $this->prepareLobby($victim);
                    $this->getPlugin()->sendMenu($victim);
                    $eventitem = Item::get(450, 0, 1);
                    $eventitem->setCustomName("§r§l§aJoin Sumo Event");
                    $victim->getInventory()->setItem(5, $eventitem);
                }
            }
        }
    }

    public function prepareLobby(Player $player) {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->setXpLevel(0);
        $player->setXpProgress(0);
        $player->setHealth(20);
        $lobby = $this->getPlugin()->getServer()->getLevelByName("Lobby");
        $pos = new Position(-1228.5, 21.04, 1692.62, $lobby);
        $player->teleport($pos);
        $this->getPlugin()->sendMenu($player);
        $player->setGamemode(2);
        $this->getPlugin()->getScoreboardUtil()->setLobbyScoreboard($player);
        $eventitem = Item::get(450, 0, 1);
        $eventitem->setCustomName("§r§l§aJoin Sumo Event");
        $player->getInventory()->setItem(5, $eventitem);
    }

    public function removeVoid(PlayerMoveEvent $event) {
        $victim = $event->getPlayer();
        if (intval($victim->y) <= 1) {
            $light = new AddActorPacket();
            $light->type = "minecraft:lightning_bolt";
            $light->entityRuntimeId = Entity::$entityCount++;
            $light->metadata = [];
            $light->motion = null;
            $light->yaw = $victim->getYaw();
            $light->pitch = $victim->getPitch();
            $light->position = new Position($victim->getX(), $victim->getY(), $victim->getZ());
            $sound = new PlaySoundPacket();
            $sound->soundName = "ambient.weather.thunder";
            $sound->x = $victim->getX();
            $sound->y = $victim->getY();
            $sound->z = $victim->getZ();
            $sound->volume = 1;
            $sound->pitch = 1;
            $this->plugin->getServer()->broadcastPacket($victim->getLevel()->getPlayers(), $light);
            $this->plugin->getServer()->broadcastPacket($victim->getLevel()->getPlayers(), $sound);

            $victim->setCurrentTotalXp(0);
            $victim->setXpProgress(0);
            $victim->setXpLevel(0);
            $this->prepareLobby($victim);
            $this->getPlugin()->sendMenu($victim);
            $eventitem = Item::get(450, 0, 1);
            $eventitem->setCustomName("§r§l§aJoin Sumo Event");
            $victim->getInventory()->setItem(5, $eventitem);
        }
    }

    public function onHit(EntityDamageByEntityEvent $event): void {
        $player = $event->getEntity();
        $damager = $event->getDamager();
        $arena = $this->getPlugin()->getArenaByPlayer($player);
        if(!($player instanceof Player) or !($damager instanceof Player)) {
            return;
        }

        if($arena and $arena->isStarting()) {
            $player->getInventory()->clearAll();
            $event->setCancelled();
        }

        /*if($arena and $arena->isRunning()){
            $arena->addHit($damager);
        }*/

    }

    public function onLevelChange(EntityLevelChangeEvent $event): void {
        $entity = $event->getEntity();
        if(!($entity instanceof Player)) {
            return;
        }
        $name = $event->getEntity()->getName();
        $arena = $this->getPlugin()->getArenaByPlayer($entity);
        if($arena and $arena->isRunning() and $event->getTarget() !== $arena->getWorld()){
            $arena->quit($entity);
        }
        if($entity->isAlive() and $event->getTarget() === $entity->getServer()->getDefaultLevel()){
            $this->getPlugin()->sendMenu($entity);
        }
    }

    /*public function onLevelChange2(EntityLevelChangeEvent $event) {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            if ($player->getLevel()->getFolderName() === "Lobby") {
                $this->getPlugin()->getScoreboardUtil()->setLobbyScoreboard($player);
            }
        }
    }*/

    public function onRespawn1(PlayerRespawnEvent $event) {
        $player = $event->getPlayer();
        $this->getPlugin()->getScoreboardUtil()->setLobbyScoreboard($player);
        $eventitem = Item::get(450, 0, 1);
        $eventitem->setCustomName("§r§l§aJoin Sumo Event");
        $player->getInventory()->setItem(5, $eventitem);
    }

    public function onPlace(BlockPlaceEvent $event): void {
        $name = $event->getPlayer()->getName();
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $arena = $this->getPlugin()->getArenaByPlayer($player);
        $ffaarena = $this->getPlugin()->getActiveFFA();
        if ($ffaarena->getNested("$name.none"))
            if ($arena and $arena->isRunning()) {
                $arena->addPlacedBlock($block->getX(), $block->getY(), $block->getZ());
            }
    }

    public function onBreak(BlockBreakEvent $event): void {
        $name = $event->getPlayer()->getName();
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $arena = $this->getPlugin()->getArenaByPlayer($player);
        $vector3 = new Vector3($block->getX(), $block->getY(), $block->getZ());
        if ($arena and $arena->isRunning()) {
            if (in_array($vector3, $arena->getPlacedBlocks())) {
                $arena->removePlacedBlock($block->getX(), $block->getY(), $block->getZ());
            } else {
                $event->setCancelled();
            }
        }
    }

    public function onBlockBurn(BlockBurnEvent $event): void {
        $block = $event->getCausingBlock();
        $level = $block->getLevel()->getFolderName();
        $arena = $this->getPlugin()->getArenaByName($level);

        if($arena and $arena->isRunning()) {
            $event->setCancelled();
            $arena->addPlacedBlock($block->getX(), $block->getY(), $block->getZ());
        }
    }

    public function onExhaust(PlayerExhaustEvent $event) {
        $event->setCancelled(true);
    }


    public function onVoid(EntityDamageEvent $event) {
        switch ($event->getCause()) {
            case EntityDamageEvent::CAUSE_FALL:
                $event->setCancelled(true);
        }
    }

    # Credit: JackMD

    public function getCPS(Player $player){
        if(!isset($this->clicks[$player->getLowerCaseName()])){
            return 0;
        }
        $time = $this->clicks[$player->getLowerCaseName()][0];
        $clicks = $this->clicks[$player->getLowerCaseName()][1];
        if($time !== time()){
            unset($this->clicks[$player->getLowerCaseName()]);
            return 0;
        }
        return $clicks;
    }

    public function addCPS(Player $player) {
        if(!isset($this->clicks[$player->getLowerCaseName()])){
            $this->clicks[$player->getLowerCaseName()] = [time(), 0];
        }
        $time = $this->clicks[$player->getLowerCaseName()][0];
        $clicks = $this->clicks[$player->getLowerCaseName()][1];
        if($time !== time()){
            $time = time();
            $clicks = 0;
        }
        $clicks++;
        $this->clicks[$player->getLowerCaseName()] = [$time, $clicks];
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event) {
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        if ($packet instanceof InventoryTransactionPacket) {
            $transactionType = $packet->transactionType;
            if ($transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
                $this->addCPS($player);
                $player->sendPopup("§7CPS: §l§a" . $this->getCPS($player));
                if ($this->getCPS($player) >= 24) {
                    foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $staff) {
                        if ($staff->hasPermission("anticheat.alerts")) {
                            $staff->sendPopup("§b" . $player->getName() . " §7[§bCPS: " . $this->getCPS($player) . "§7] [§b" . $player->getPing() . "ms§7]");
                        }
                    }
                }
            }
        }

        if ($packet instanceof LevelSoundEventPacket) {
            $sound = $packet->sound;
            if ($sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) {
                $this->addCPS($player);
                $player->sendPopup("§7CPS: §l§a" . $this->getCPS($player));
                if ($this->getCPS($player) >= 24) {
                    foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $staff) {
                        if ($staff->hasPermission("anticheat.alerts")) {
                            $staff->sendPopup("§b" . $player->getName() . " §7[§bCPS: " . $this->getCPS($player) . "§7] [§b" . $player->getPing() . "ms§7]");
                        }
                    }
                }
                if ($this->getCPS($player) >= 50 && $player->getPing() <= 350) {
                    $this->getPlugin()->getServer()->getIPBans()->addBan($player->getAddress(), "HELIOS CHEAT DETECTION", null, "CONSOLE");
                    $player->kick("§dYou have been permanently IP-banned from Helios Practice!\n§dBanned by: §bCONSOLE\n§dReason: §bHELIOS CHEAT DETECTION\n§dDiscord Server: §bhttps://bit.ly/heliospractice", false);
                    $this->getPlugin()->getServer()->broadcastMessage("§b" . $player->getName() . " §d has been permanently IP-banned from the server for cheating.");
                    $webHook = new Webhook("https://discordapp.com/api/webhooks/756917746209390737/QQL9T4jpNhuhdPT06zXLa_zmK0wbJ-Nil4OgsoAkH9gzgLkQcS4f5H5Ixp1WmdRj-mzV");
                    $msg = new Message();
                    $embed = new Embed();
                    $embed->setTitle("The ban hammer has spoken!");
                    $embed->setColor(0xFF0000);
                    $embed->setDescription("**Staff:** CONSOLE\n**IP-banned: **" . $player->getName() . "\n**Reason:** HELIOS CHEAT DETECTION\n**Expires:** never");
                    $embed->setFooter("Helios Practice");
                    $msg->addEmbed($embed);
                    $webHook->send($msg);
                }
            }
        }
    }

    public function freezeBlock(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($item->getCustomName() === "§r§l§aFreeze a player") {
            $this->getPlugin()->getUI()->freezeForm($player);
        }
    }

    public function teleportMenu(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($item->getCustomName() === "§r§l§aTeleport") {
            $this->getPlugin()->getUI()->teleportForm($player);
        }
    }

    public function adminMenu(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($item->getCustomName() === "§r§l§aAdmin Menu") {
            if ($player->isOp()) {
                $this->getPlugin()->getUI()->adminForm($player);
            } else {
                $player->sendMessage("§cYou're not an admin...");
            }
        }
    }

    public function staffMode(Player $player) {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->setHealth(20);
        $player->removeAllEffects();
        $this->getPlugin()->getServer()->dispatchCommand($player, "v");
    }

    public function onThrow(PlayerDropItemEvent $event) {
        $event->setCancelled(true);
    }

    public function onQuery(QueryRegenerateEvent $event): void {
        $event->setMaxPlayerCount($event->getPlayerCount() + 1);
        $event->setPlayerCount($event->getPlayerCount() + 0);
    }

    public function getPlugin(): Main {
        return $this->plugin;
    }

    public function onPot(ProjectileHitBlockEvent $event) {
        $player = $event->getEntity()->getOwningEntity();
        $pot = $event->getEntity();
        if ($player instanceof Player) {
            if ($pot instanceof \pocketmine\entity\projectile\SplashPotion) {
                if ($pot != null) {
                    switch (round($player->distance($pot), 0)) {
                        case 1:
                        case 0:
                            $player->setHealth($player->getHealth() + 8);
                            break;
                        case 2:
                            $player->setHealth($player->getHealth() + 7.5);
                            break;
                        case 3:
                            $player->setHealth($player->getHealth() + 6);
                            break;
                        default:
                            return;
                    }
                }
            }
        }
    }

    public function onPotHit(ProjectileHitEntityEvent $event) {
        $player = $event->getEntity()->getOwningEntity();
        $pot = $event->getEntity();
        if ($player instanceof Player) {
            if ($pot != null) {
                if ($pot instanceof \pocketmine\entity\projectile\SplashPotion) {
                    $player->setHealth($player->getHealth() + 8);
                }
            }
        }
    }

    public function onDamage(EntityDamageByEntityEvent $event) {
        if ($event->getEntity() instanceof Player && $event->getDamager() instanceof Player) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            $entitydistance = $entity->distance($damager);
            $distancerounded = round($entitydistance, 1);
            if ($distancerounded >= 5.5) {
                if ($damager instanceof Player && $entity instanceof Player) {
                    foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $staff) {
                        if ($staff->hasPermission("helios.staff")) {
                            $staff->sendPopup("§b" . $damager->getName() . " §7[§b" . $distancerounded . "§7] §7[§b" . $damager->getPing() . "ms§7]");
                        }
                    }
                }
            }
        }
    }

    public function onPlace2(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        if (in_array($player->getName(), $this->freeze)) {
            $event->setCancelled(true);
        }
    }

    public function onFreezeMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        if (in_array($player->getName(), $this->freeze)) {
            $player->setImmobile(true);
            $player->sendPopup("§cYou can't move! You are frozen.");
        }
    }

    public function freezeDamage(EntityDamageEvent $event) {
        if ($event instanceof EntityDamageByEntityEvent) {
            if ($event->getEntity() instanceof Player && $event->getDamager() instanceof Player) {
                $entity = $event->getEntity();
                $damager = $event->getDamager();
                if ($entity instanceof Player && $damager instanceof Player) {
                    if ((in_array($entity->getName(), $this->freeze)) && (!in_array($damager->getName(), $this->freeze))) {
                        $damager->sendMessage("§cYou cannot hit a player while they are frozen.");
                        $event->setCancelled(true);
                    }
                    if ((!in_array($entity->getName(), $this->freeze)) && (in_array($damager->getName(), $this->freeze))) {
                        $damager->sendMessage("§cYou cannot attack players while you are frozen");
                        $event->setCancelled(true);
                    }
                }
            }
        }
    }

    public function onDisconnectPacket(DataPacketSendEvent $event) {
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if ($packet instanceof DisconnectPacket and $packet->message === "Internal server error") {
            $packet->message = ("§dYou have encountered a bug.\n§dContact us on Discord: §bhttps://bit.ly/heliospractice");
            foreach ($this->plugin->getServer()->getOnlinePlayers() as $online) {
                if ($online->hasPermission("helios.high.staff")) {
                    $online->sendPopup("§c§lWarning: internal server error!");
                }
            }
        }
    }

    /*public function onLogin(DataPacketReceiveEvent $event) {
        $pk = $event->getPacket();
        if (!$pk instanceof LoginPacket) {
            return;
        }
        $player = $event->getPlayer();
        $currentProtocol = ProtocolInfo::CURRENT_PROTOCOL;
        if ($pk->protocol < $currentProtocol) {
            $player->kick(TextFormat::RED . 'Your protocol version needs to be same or above ' . $currentProtocol . ".", false);
        } elseif ($pk->protocol > $currentProtocol) {
            $pk->protocol = $currentProtocol;
            $this->getPlugin()->getServer()->getLogger()->alert($player->getName() . "'s protocol changed to " . $currentProtocol . ".");
        }
    }*/

    /*public static function createPotion($player){
        $motion=$player->getDirectionVector();
        $nbt=Entity::createBaseNBT($player->add(0, 0, 0), $motion);
        $entity=Entity::createEntity("SplashPotion1", $player->level, $nbt, $player);
        if($entity instanceof Projectile){
            $event=new ProjectileLaunchEvent($entity);
            $event->call();
            if($event->isCancelled() or $player->getGamemode()===3){
                $entity->kill();
            }else{
                $entity->spawnToAll();
                //self::playSound($player, 54, true);
                $itemInHand=$player->getInventory()->getItemInHand();
                if($itemInHand->getId()===Item::SPLASH_POTION){
                    $player->getInventory()->setItemInHand(Item::get(0));
                }
            }
        }
    }*/
}