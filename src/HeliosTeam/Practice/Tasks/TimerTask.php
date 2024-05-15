<?php
declare(strict_types=1);

namespace HeliosTeam\Practice\Tasks;

use pocketmine\scheduler\Task;
use HeliosTeam\Practice\Main;

class TimerTask extends Task {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick): void {
        foreach ($this->getPlugin()->getArenas() as $arena) {
            $arena->tick();
        }
        $playersInfo = $this->getPlugin()->getPlayersInfo()->getAll();
        $playerDuels = $this->getPlugin()->getPlayerDuels()->getAll();
        if(!isset($playerDuels["date"])) {
            $this->getPlugin()->getPlayerDuels()->set("date", date("d/m/Y"));
            $this->getPlugin()->getPlayerDuels()->save();
        }
        if(isset($playerDuels["date"]) and $playerDuels["date"] !== date("d/m/Y")) {
            foreach($playerDuels as $name => $info) {
                $this->getPlugin()->getPlayerDuels()->remove($name);
            }
            $this->getPlugin()->getPlayerDuels()->save();
            foreach($playersInfo as $name => $info) {
                $this->getPlugin()->getPlayersInfo()->setNested("$name.ranked-played", 0);
            }
            $this->getPlugin()->getPlayersInfo()->save();
        }
        $players = $this->getPlugin()->getPlayers()->getAll();
    }

    public function getPlugin() : Main {
        return $this->plugin;
    }
}