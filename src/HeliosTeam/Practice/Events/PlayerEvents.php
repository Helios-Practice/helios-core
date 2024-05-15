<?php

namespace HeliosTeam\Practice\Events;

use HeliosTeam\Practice\Main;
use HeliosTeam\Practice\Tasks\PearlCount;
use HeliosTeam\Practice\Utils\Api;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class PlayerEvents implements Listener {

    private $main;
    public static $cooldown = [];

    public function __construct(Main $main) {
        $this->main = $main;
        $main->getServer()->getPluginManager()->registerEvents($this, $main);
    }

    public function onJoin(PlayerJoinEvent $event): void {

        if(!is_dir($this->main->getDataFolder() . "ipdb/")){
            @mkdir($this->main->getDataFolder() . "ipdb/", 0777, true);
        }

        $name = $event->getPlayer()->getDisplayName();
        $ip = $event->getPlayer()->getAddress();

        $file = new Config($this->main->getDataFolder() . "ipdb/" . $ip . ".txt", CONFIG::ENUM);
        $file->set($name);
        $file->save();

        $name = $event->getPlayer()->getName();
        $player = $event->getPlayer();

        $ip = $player->getAddress();
        $file = new Config($this->main->getDataFolder() . "ipdb/" . $ip . ".txt");
        $names = $file->getAll(true);
        $alts = implode(', ', $names);
        foreach ($this->main->getServer()->getOnlinePlayers() as $players) {
            if ($players->hasPermission("helios.staff")) {
                $players->sendPopup("§c" . $event->getPlayer()->getName() . "'s alts: §7" . $alts);
            }
        }

        $player->setXpLevel(0);
        $player->setXpProgress(0);
        $this->main->sendMenu($event->getPlayer());
        $config = $this->main->getConfig();
        $players = $this->main->getPlayers()->getAll();
        $playersInfo = $this->main->getPlayersInfo()->getAll();
        $activeduels = $this->main->getActiveDuels()->getAll();
        foreach($this->main->getKits()->getAll(true) as $kit) {

            if(!isset($players[$name][$kit])) $this->main->getPlayers()->setNested("$name.$kit", (int)$this->main->getConfig()->get("default-elo"));
            if(!isset($playersInfo[$name][$kit]["wins"])) $this->main->getPlayersInfo()->setNested("$name.$kit.wins", 0);
            if(!isset($playersInfo[$name][$kit]["loses"])) $this->main->getPlayersInfo()->setNested("$name.$kit.loses", 0);
        }

        if(!isset($playersInfo[$name]["ranked-played"])) $this->main->getPlayersInfo()->setNested("$name.ranked-played", 0);
        $this->main->getPlayers()->save();
        $this->main->getPlayersInfo()->save();
        $this->main->pureChat->setPrefix($this->main->getRankPrefix($name), $event->getPlayer());
    }

    /*public function onPearl(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        if ($event->getItem()->getId() == Item::ENDER_PEARL) {
            if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR || $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
                if (!isset(PlayerEvents::$cooldown[$player->getName()])){
                    PlayerEvents::$cooldown[$player->getName()] = 1;
                    $timer = 99;
                    $this->main->getScheduler()->scheduleRepeatingTask(new PearlCount($this->main, $player, $timer), 4);
                }else{$event->setCancelled(true);}
            }
        }
    }*/

    public function onPearlThrow(ProjectileLaunchEvent $event) {
        $pearl = $event->getEntity();
        if ($pearl instanceof EnderPearl) {
            $player = $event->getEntity()->getOwningEntity();
            if ($player instanceof Player) {
                if (!isset(PlayerEvents::$cooldown[$player->getName()])) {
                    PlayerEvents::$cooldown[$player->getName()] = 1;
                    $timer = 99;
                    $this->main->getScheduler()->scheduleRepeatingTask(new PearlCount($this->main, $player, $timer), 4);
                } else {
                    $event->setCancelled(true);
                    $addedpearl = Item::get(368, 0, 1);
                    $player->getInventory()->setItem(1, $addedpearl);
                }
            }
        }
    }

    public function giveEnderPearl(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        if (isset(PlayerEvents::$cooldown[$player->getName()])) {
            if ($event->getItem()->getId() == Item::ENDER_PEARL) {
                $event->setCancelled(true);
            }
        }
    }
}