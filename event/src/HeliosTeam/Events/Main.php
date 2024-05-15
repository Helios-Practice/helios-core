<?php

namespace HeliosTeam\Events;

use HeliosTeam\Tasks\EventTask;
use HeliosTeam\Tasks\StartTask;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {

    private $participants = [];
    private $fighting = [];
    private $sumoevent = false;
    private $started = false;
    private $roundinprogress = false;
    private $round = 0;

    const NO_EVENT = "§cThere is no event currently running.";
    const USAGE = "§aUsage: §7/sumo {create:start:round:join:leave:end}";

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("config.yml");
        $config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        foreach ($this->getServer()->getLevels() as $level) {
            $this->getServer()->loadLevel($level->getFolderName());
        }
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
        if (!isset($args[0])) {
            $sender->sendMessage(self::USAGE);
            return true;
        }
        switch (strtolower($args[0])) {
            case "create":
                if (!$sender->hasPermission("helios.event.host")) {
                    $sender->sendMessage("§cYou do not have permission to create sumo events.");
                    return true;
                }
                if ($this->sumoevent) {
                    $sender->sendMessage("§cAn event is already running!");
                    return true;
                }
                $this->sumoevent = true;
                $sender->sendMessage("§aYou have successfully started a sumo event!");
                $this->getServer()->broadcastMessage("§a" . $sender->getName() . " has started a sumo event! To join, run §l/sumo join§r§a.");
                break;
            case "start":
                if (!$sender->hasPermission("helios.event.host")) {
                    $sender->sendMessage("§cYou do not have permission to start sumo events.");
                    return true;
                }
                if ($this->started) {
                    $sender->sendMessage("§cThe sumo event has already started.");
                    return true;
                }
                if (!$this->sumoevent) {
                    $sender->sendMessage(self::NO_EVENT);
                }
                if (count($this->participants) <= 1 || count($this->getServer()->getOnlinePlayers()) <= 1) {
                    $sender->sendMessage("§cThere are not enough players to start the event.");
                    return true;
                }
                $count = count($this->participants);
                $remainder = $count % 2;
                if ($remainder != 0) {
                    $sender->sendMessage("§cThere must be an even amount of players to start the event.");
                    return true;
                }
                $this->started = true;
                $sender->sendMessage("§aThe sumo event has been started successfully.");
                $this->getScheduler()->scheduleRepeatingTask(new StartTask($this), 20);
                foreach ($this->participants as  $participant) {
                    $player = $this->getServer()->getPlayer($participant);
                }
                break;
            case "round":
                if(!$sender->hasPermission("helios.event.host")){
                    $sender->sendMessage(TF::RED . "You do not have permission to use this command!");
                    return true;
                }
                if(!$this->sumoevent){
                    $sender->sendMessage(self::NO_EVENT);
                    return true;
                }
                if(!$this->started){
                    $sender->sendMessage(TF::RED . "The sumo event has not been started.");
                    return true;
                }
                if(count($this->participants) > 1){
                    list($red, $blue) = array_chunk($this->participants, ceil(count($this->participants) / 2));
                } else {
                    $this->endSumoEvent();
                    return true;
                }
                if($this->roundinprogress){
                    $sender->sendMessage(TF::RED . "There is a round currently in progress.");
                    return true;
                }
                $this->roundinprogress = true;
                $player1 = $this->getServer()->getPlayer($red[array_rand($red)]);
                $player2 = $this->getServer()->getPlayer($blue[array_rand($blue)]);
                $this->round++;
                $p1 = $player1->getName(); $p2 = $player2->getName();
                $this->fighting[] = $p1;
                $this->fighting[] = $p2;
                $player1->setImmobile(true); $player2->setImmobile(true);
                $rn = $this->round;
                $world = $this->getConfig()->get("world");
                $worldd = $this->getServer()->getLevelByName($world);
                $player1->teleport($worldd->getSafeSpawn());
                $player2->teleport($worldd->getSafeSpawn());

                $pos = $world = $this->getConfig()->get("pos");
                $pos2 = $world = $this->getConfig()->get("pos2");

                $player1->teleport(new Position($pos[0], $pos[1],$pos[2],$worldd));
                $player2->teleport(new Position($pos2[0],$pos2[1],$pos2[2],$worldd));
                $player1->sendMessage("§aOpponent: §l" . $p2);
                $player2->sendMessage("§aOpponent: §l" . $p1);
                foreach ($this->getServer()->getOnlinePlayers() as $eventparticipant) {
                    if ($eventparticipant->getLevel()->getFolderName() === "Sumo-Event") {
                        $eventparticipant->sendPopup("§a" . $p1 . " vs. " . $p2);
                    }
                }
                $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                    function (int $currentTick) use ($player1, $player2): void {
                        if(!in_array($player1->getName(),$this->fighting)){
                            $this->endRound($player1, $player2);
                            $player2->setImmobile(false);
                            $this->removeFighting($player2->getName());
                            return;
                        }
                        if(!in_array($player2->getName(), $this->fighting)) {
                            $this->endRound($player2, $player1);
                            $player1->setImmobile(false);
                            $this->removeFighting($player1->getName());
                            return;
                        }
                        foreach([$player1, $player2] as $players) {
                            $players->setImmobile(false);
                            $players->sendTitle("§l§aFight!", "", 5, 15, 5);
                            $this->sumoEffect($players);
                        }
                    }
                ), 100);
                break;
            case "spectate":
                if(!$this->sumoevent) {
                    $sender->sendMessage(self::NO_EVENT);
                    return true;
                }
                if(!$this->started){
                    $sender->sendMessage(TF::RED . "The sumo event has not been started.");
                    return true;
                }
                $world = $this->getConfig()->get("world");
                if ($sender instanceof Player) {
                    if($sender->getLevel()->getName() === $world){
                        $sender->sendMessage(TF::RED . "You are already in the sumo event world.");
                        return true;
                    }
                }
                $sumomap = $this->getServer()->getLevelByName("Sumo-Event");
                $pos = new Position(9984.55, 93, 10003.49, $sumomap);
                $sender->teleport($pos);
                break;
            case "join":
                if (!$this->sumoevent) {
                    $sender->sendMessage(self::NO_EVENT);
                    return true;
                }
                if ($this->started) {
                    $sender->sendMessage("§cThe event has already started.");
                    return true;
                }
                if (!in_array($sender->getName(), $this->participants)){
                    $this->participants[] = $sender->getName();
                    $sender->sendMessage("§aYou have joined the sumo event!");
                    if ($sender instanceof Player) {
                        $sender->removeAllEffects();
                        $sender->getInventory()->clearAll();
                        $sender->getArmorInventory()->clearAll();
                        $sender->setHealth(20);
                    }
                    $this->getServer()->broadcastMessage("§a" . $sender->getName() . " has joined the sumo event. §7[" . count($this->participants) . "]");
                    $sumomap = $this->getServer()->getLevelByName("Sumo-Event");
                    $pos = new Position(9984.55, 93, 10003.49, $sumomap);
                    $sender->teleport($pos);
                } else {
                    $sender->sendMessage("§cYou are already in the event.");
                }
                break;
            case "leave":
                if (!$this->sumoevent){
                    $sender->sendMessage(TF::RED . self::NO_EVENT);
                    return true;
                }
                if (in_array($sender->getName(), $this->participants)) {
                    if (!$this->started) {
                        $this->removePlayer($sender->getName());
                        $sender->sendMessage("§cYou left the sumo event.");
                        if ($sender instanceof Player) {
                            $sender->teleport($this->getServer()->getDefaultLevel()->getSpawnLocation());
                            $this->getServer()->dispatchCommand($sender, "hub");
                        }
                    } else {
                        $sender->sendMessage("§cThe event has already started.");
                    }
                } else {
                    $sender->sendMessage("§cYou are not in a sumo event.");
                }
                break;
            case "end":
                if (!$sender->hasPermission("helios.high.staff")){
                    $sender->sendMessage("§cYou do not have permission to end an event.");
                    return true;
                }
                if (!$this->sumoevent){
                    $sender->sendMessage(TF::RED . self::NO_EVENT);
                    return true;
                }
                $this->endSumoEvent();
                $sender->sendMessage("§cThe event was ended successfully.");
                break;
        }
        return true;
    }

    public function sumoDeath(EntityDamageEvent $event) {
        $victim = $event->getEntity();
        $world = $this->getConfig()->get("world");
        if ($victim instanceof Player) {
            if ($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if ($damager instanceof Player) {
                    if (!in_array($damager->getName(), $this->fighting) && $damager->getLevel()->getFolderName() === "Sumo-Event") $event->setCancelled(true);
                    $name = $victim->getName();
                    if (!in_array($name, $this->fighting)) return;
                    /*if (in_array($name, $this->participants)) {
                        $this->removePlayer($name);
                    }
                    $this->removeFighting($name);
                    if ($damager->getLevel()->getName() === $world) {
                        $this->endRound($victim, $damager);
                    }
                    if (in_array($damager->getName(), $this->fighting)) {
                        $this->removeFighting($damager->getName());
                    }

                    $victim->setCurrentTotalXp(0);
                    $victim->setXpProgress(0);
                    $victim->setXpLevel(0);
                    $victim->removeAllEffects();*/
                }
            }
        }
    }

    public function playSound(Player $player, int $id) {
        $pk = new LevelSoundEventPacket();
        $pk->sound = $id;
        $pk->position = new Vector3($player->x, $player->y, $player->z);
        $player->dataPacket($pk);
    }

    public function onLevelChange(EntityLevelChangeEvent $event) {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            $name = $player->getName();
            if(!in_array($name, $this->fighting)) return;
            if(in_array($name, $this->participants)){
                $this->removePlayer($name);
                $this->roundinprogress = false;
                $this->getScheduler()->scheduleRepeatingTask(new EventTask($this), 20);
            }
            $this->removeFighting($name);
        }
    }

    public function onMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        if ($player instanceof Player) {
            if ($player->getY() < 50) {
                $name = $player->getName();
                if(in_array($name, $this->participants)) {
                    $this->removePlayer($name);
                    $this->roundinprogress = false;
                    $this->getScheduler()->scheduleRepeatingTask(new EventTask($this), 20);
                    $sumomap = $this->getServer()->getLevelByName("Sumo-Event");
                    $pos = new Position(9984.55, 93, 10003.49, $sumomap);
                    $player->teleport($pos);
                    $light = new AddActorPacket();
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
                    $this->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $light);
                    $this->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $sound);
                    foreach ($this->getServer()->getOnlinePlayers() as $players) {
                        $this->playSound($players, 62);
                    }
                    $player->setCurrentTotalXp(0);
                    $player->setXpProgress(0);
                    $player->setXpLevel(0);
                    $player->removeAllEffects();
                }
            }
        }
    }

    /*public function onKill(PlayerMoveEvent $event) {
        if(!$this->started) return;
        $victim = $event->getPlayer();
        $name = $victim->getName();
        if(!in_array($name,$this->fighting)) return;
        $world = $this->getConfig()->get("world");
        if (intval($victim->y) <= 3 && $victim->getLevel()->getName() === $world) {
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
            $this->getServer()->broadcastPacket($victim->getLevel()->getPlayers(), $light);
            $this->getServer()->broadcastPacket($victim->getLevel()->getPlayers(), $sound);

            $victim->setCurrentTotalXp(0);
            $victim->setXpProgress(0);
            $victim->setXpLevel(0);
            $victim->kill();
        }
    }*/

    public function getRemainingSumoEventCount() : int {
        return count($this->participants);
    }

    public function onQuit(PlayerQuitEvent $event){
        if (!$this->sumoevent) return;
        $player = $event->getPlayer();
        $name = $player->getName();
        if (in_array($name, $this->participants)){
            $this->removePlayer($name);
        }
        if (in_array($name, $this->fighting)){
            $this->removeFighting($name);
        }
    }

    public function endSumoEvent() {
        if ($this->started){
            if (count($this->participants) <= 1){
                //$winner = $this->participants[array_key_first($this->participants)];
                $this->roundinprogress = false;
                $this->getServer()->broadcastPopup("§aThe sumo event has ended!");
            } else {
                $this->getServer()->broadcastMessage("§aThe sumo event has ended. A winner could not be determined.");
            }
            $world = $this->getServer()->getLevelByName("Sumo-Event");
            foreach ($this->getServer()->getLevelByName("Sumo-Event")->getEntities() as $players){
                if ($players instanceof Player){
                    $players->removeAllEffects();
                    $lobby = $this->getServer()->getLevelByName("Lobby");
                    $pos = new Position(-1228.5, 21.04, 1692.62, $lobby);
                    $players->teleport($pos);
                }
            }
        }
        $this->sumoevent = false;
        $this->started = false;
        $this->participants = [];
        $this->round = 0;
    }

    public function endRound(Player $player, Player $player2){
        if(!$this->roundinprogress) return;
        $world = $this->getConfig()->get("world");
        if(in_array($player->getName(), $this->participants)){
            $winner = $player;
            $loser = $player2;
        } elseif(in_array($player2->getName(), $this->participants)){
            $winner = $player2;
            $loser = $player;
        }
        if($winner->getLevel()->getName() === $world){
            $winner->teleport($this->getServer()->getLevelByName($world)->getSafeSpawn());
            $winner->getInventory()->clearAll();
            $winner->getArmorInventory()->clearAll();
            $this->getServer()->broadcastMessage("§a" . $winner->getName() . " won the match vs. " . $loser->getName() . ".");
            if (count($this->participants) <= 1) {
                $this->endSumoEvent();
            }
            $this->roundinprogress = false;
        }
    }

    public function removePlayer(string $string){
        if (($key = array_search($string, $this->participants)) !== false) {
            unset($this->participants[$key]);
        }
    }

    public function removeFighting(string $string){
        if(($key = array_search($string, $this->fighting)) !== false) {
            unset($this->fighting[$key]);
        }
    }

    public function sumoEffect(Player $player) {
        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 1500000, 10, false));
    }
}