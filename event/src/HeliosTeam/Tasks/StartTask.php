<?php

namespace HeliosTeam\Tasks;

use HeliosTeam\Events\Main;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class StartTask extends Task {

    private $plugin;
    private $timer = 16;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick) {
        $this->timer--;
        switch ($this->timer) {
            case 16:
                break;
            case 15:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 15...");
                    }
                }
                break;
            case 14:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 14...");
                    }
                }
                break;
            case 13:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 13...");
                    }
                }
                break;
            case 12:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 12...");
                    }
                }
                break;
            case 11:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 11...");
                    }
                }
                break;
            case 10:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 10...");
                    }
                }
                break;
            case 9:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 9...");
                    }
                }
                break;
            case 8:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 8...");
                    }
                }
                break;
            case 7:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 7...");
                    }
                }
                break;
            case 6:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 6...");
                    }
                }
                break;
            case 5:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 5...");
                    }
                }
                break;
            case 4:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 4...");
                    }
                }
                break;
            case 3:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 3...");
                    }
                }
                break;
            case 2:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 2...");
                    }
                }
                break;
            case 1:
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player && $player->getLevel()->getFolderName() === "Sumo-Event") {
                        $player->sendPopup("§aEvent starting in 1...");
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
