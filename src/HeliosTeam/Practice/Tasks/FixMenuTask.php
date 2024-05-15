<?php
declare(strict_types=1);

namespace HeliosTeam\Practice\Tasks;

use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use HeliosTeam\Practice\Main;

class FixMenuTask extends Task {

    /** @var Main */
    private $plugin;
    /** @var Player */
    private $player;

    /**
     * @param Main $plugin
     * @param Player $player
     */
    public function __construct(Main $plugin, Player $player) {
        $this->plugin = $plugin;
        $this->player = $player;
    }
    
    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick): void {
        $this->getPlugin()->sendMenu($this->player);
        $eventitem = Item::get(450, 0, 1);
        $eventitem->setCustomName("§r§l§aJoin Sumo Event");
        $this->player->getInventory()->setItem(5, $eventitem);
    }
    
    /**
     * @return Main
     */
    public function getPlugin(): Main {
        return $this->plugin;
    }
}
