<?php

namespace HeliosTeam\Practice\Tasks;

use pocketmine\scheduler\Task;
use HeliosTeam\Practice\Main;

class BroadcastTask extends Task {

    private $plugin;
    private $time = 1;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick) {
        $this->time++;
        switch ($this->time) {
            case 60:
                $this->getPlugin()->getServer()->broadcastMessage("§7Thank you for playing on Helios Practice!");
                break;
            case 120:
                $this->getPlugin()->getServer()->broadcastMessage("§7See a hacker on the server, and no staff are online? Report the player using §a/helios§7.");
                break;
            case 180:
                $this->getPlugin()->getServer()->broadcastMessage("§7Join our Discord server for giveaways, events, and updates! §ahttps://bit.ly/heliospractice§7.");
                break;
            case 240:
                $this->getPlugin()->getServer()->broadcastMessage("§7Want to purchase a rank to help support Helios and receive cool cosmetics? Check out our store at §ahttp://store.heliospractice.us.to§7.");
                break;
            case 300:
                $this->getPlugin()->getServer()->broadcastMessage("§7Eager to apply for staff? Apply on our Discord server! §ahttps://bit.ly/heliospractice§7.");
                $this->time = 0;
                break;
        }
    }

    public function getPlugin(): Main {
        return $this->plugin;
    }
}
