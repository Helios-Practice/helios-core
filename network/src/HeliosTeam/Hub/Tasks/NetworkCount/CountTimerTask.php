<?php

namespace HeliosTeam\Hub\Tasks\NetworkCount;

use HeliosTeam\Hub\Main;
use HeliosTeam\Hub\Tasks\NetworkCount\Async\AsyncNetworkCount;
use pocketmine\scheduler\Task;

class CountTimerTask extends Task {

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
            new AsyncNetworkCount();
            $this->timer = 5;
        }
    }
}