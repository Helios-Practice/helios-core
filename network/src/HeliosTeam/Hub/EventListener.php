<?php

namespace HeliosTeam\Hub;

use HeliosTeam\Hub\Classes\QueryCounter;
use HeliosTeam\Hub\Tasks\Scoreboard\ScoreboardTask;
use HeliosTeam\Hub\Tasks\VisibilityTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\item\ItemFactory;
use pocketmine\Player;
use pocketmine\item\Item;
use Libs\FormAPI\SimpleForm;
use pocketmine\Server;

class EventListener implements Listener {

    private $plugin;
    public static $count;
    private static $main;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        self::$main = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $event->setJoinMessage("§8[§2+§8] §a" . $player->getName());
        $player->setFood(20);
        $player->setXpProgress(0);
        $player->setHealth(20);
        $player->setXpLevel(0);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->removeAllEffects();
        $player->teleport($this->getPlugin()->getServer()->getDefaultLevel()->getSafeSpawn());
        $this->setLoadingScoreboard($player);
        $hubitem = Item::get(345, 0, 1);
        $hubitem->setCustomName("§r§l§aTransfer");
        $player->getInventory()->setItem(4, $hubitem);
    }

    public function onItemDrop(PlayerDropItemEvent $event) {
        $event->setCancelled(true);
    }

    public function onExhaust(PlayerExhaustEvent $event) {
        $event->setCancelled(true);
    }

    public function transferForm(Player $player): SimpleForm {
        $form = new SimpleForm (function (Player $event, $data) {
            $player = $event->getPlayer();

            if ($data === null) {
                return;
            }
            switch ($data) {
                case 0:
                    $player->transfer("51.222.25.239", 19133);
                    break;
                case 1;
                    $player->transfer("");
                    break;
                case 2;
                    $this->getPlugin()->getServer()->dispatchCommand($player, "leaderboards");
                    break;
            }
        });

        $form->setTitle("§l§8TRANSFER FORM");
        $form->setContent("Transfer to a region below:");
        $form->addButton("§8NA Practice\n§r§8Online: " . QueryCounter::countNA(true));
        $form->addButton("§8EU Practice\n§r§8Online: " . QueryCounter::countEU(true));
        $form->addButton("§8NA UHCs\n§r§8Online: " . QueryCounter::countUHC(true));
        $form->addButton("§8Hub Server\n§r§8Online: " . QueryCounter::countHub(true));
        $player->sendForm($form);
        return $form;
    }

    public function compassItem(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $action = $event->getAction();
        $item = $event->getItem();
        if ($action == PlayerInteractEvent::RIGHT_CLICK_AIR) {
            switch($item->getId()) {
                case Item::COMPASS:
                    $this->transferForm($player);
                    break;
            }
        }
    }

    public static function setHubScoreboard(Player $player, int $total) {
        ScoreboardAPI::removeScore($player);
        ScoreboardAPI::setScore($player, "§l§aHELIOS | HUB");
        ScoreboardAPI::setScoreLine($player, 1, "§f---------------------");
        ScoreboardAPI::setScoreLine($player, 2, " §fOnline: §a" . strval($total));
        ScoreboardAPI::setScoreLine($player, 3, " §fPing: §a" . $player->getPing());
        ScoreboardAPI::setScoreLine($player, 4, "§f");
        ScoreboardAPI::setScoreLine($player, 5, " §aheliosmc.tk");
        ScoreboardAPI::setScoreLine($player, 6, "§r---------------------");
    }

    public function setLoadingScoreboard(Player $player) {
        ScoreboardAPI::removeScore($player);
        ScoreboardAPI::setScore($player, "§l§aHELIOS | HUB");
        ScoreboardAPI::setScoreLine($player, 1, "§f---------------------");
        ScoreboardAPI::setScoreLine($player, 2, " §fOnline: §aLoading...");
        ScoreboardAPI::setScoreLine($player, 3, " §fPing: §aLoading...");
        ScoreboardAPI::setScoreLine($player, 4, "§f");
        ScoreboardAPI::setScoreLine($player, 5, " §aheliosmc.tk");
        ScoreboardAPI::setScoreLine($player, 6, "§r---------------------");
    }

    public function onQuery(QueryRegenerateEvent $event) {
        $totalonline = self::$count + count(Server::getInstance()->getOnlinePlayers());
        $event->setPlayerCount((int)$totalonline);
        $event->setMaxPlayerCount($totalonline + 1);
    }

    public function getPlugin() : Main {
        return $this->plugin;
    }
}