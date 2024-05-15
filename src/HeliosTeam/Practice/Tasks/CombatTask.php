<?php

namespace HeliosTeam\Practice\Tasks;

use HeliosTeam\Practice\Main;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class CombatTask extends Task {

    private Main $main;
    private int $timer = 15;
    private Player $player;

    public function __construct(Main $main, Player $player) {
        $this->main = $main;
        $this->player = $player;
    }

    public function onRun(int $currentCurrentTick)
    {
        $this->timer--;
        if($this->timer === range(15, 2)) {
            $this->player->sendMessage("Test");
        }
        if($this->timer === 1) {
            $this->player->sendMessage("Dead");
        }
        if($this->timer === 0) {
            $this->main->getScheduler()->cancelTask($this->getTaskId());
        }
    }
}
