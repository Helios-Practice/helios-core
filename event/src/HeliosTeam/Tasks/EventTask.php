<?php

namespace HeliosTeam\Tasks;

use HeliosTeam\Events\Main;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class EventTask extends Task {

    private $plugin;
    private $timer = 6;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick) {
        $this->timer--;
        switch ($this->timer) {
            case 6:
                break;
            case 5:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aNext round in 5...");
                    }
                }
                break;
            case 4:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aNext round in 4...");
                    }
                }
                break;
            case 3:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aNext round in 3...");
                    }
                }
                break;
            case 2:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aNext round in 2...");
                    }
                }
                break;
            case 1:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aNext round in 1...");
                    }
                }
                break;
            case 0:
                $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), "sumo round");
                $this->plugin->getScheduler()->cancelTask($this->getTaskId());
                break;
        }
    }
}
