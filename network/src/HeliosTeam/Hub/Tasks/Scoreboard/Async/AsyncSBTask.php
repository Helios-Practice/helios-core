<?php

namespace HeliosTeam\Hub\Tasks\Scoreboard\Async;

use HeliosTeam\Hub\Classes\QueryCounter;
use HeliosTeam\Hub\EventListener;
use Libs\libmquery\PMQuery;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncSBTask extends AsyncTask {

    public function __construct() {
        Server::getInstance()->getAsyncPool()->submitTask($this);
    }

    public function onRun() {
        $query = QueryCounter::countAll();
        $this->setResult($query);
    }

    public function onCompletion(Server $server) {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            EventListener::setHubScoreboard($player, $this->getResult() + count(Server::getInstance()->getOnlinePlayers()));
        }
    }
}
