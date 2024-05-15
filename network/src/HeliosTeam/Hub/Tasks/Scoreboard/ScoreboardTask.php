<?php

namespace HeliosTeam\Hub\Tasks\Scoreboard;

use HeliosTeam\Hub\Main;
use HeliosTeam\Hub\Tasks\Scoreboard\Async\AsyncSBTask;
use pocketmine\scheduler\Task;

class ScoreboardTask extends Task {

    private $main;
    private $timer;

    public function __construct(Main $main, int $timer)
    {
        $main->getScheduler()->scheduleRepeatingTask($this, 20);
        $this->main = $main;
        $this->timer = $timer;
    }

    public function onRun(int $currentTick) {
        $this->timer--;
        if($this->timer <= 0) {
            new AsyncSBTask();
            $this->timer = 5;
        }
    }
}
