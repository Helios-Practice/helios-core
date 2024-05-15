<?php

namespace HeliosTeam\Hub\Tasks\NetworkCount\Async;

use HeliosTeam\Hub\Classes\QueryCounter;
use HeliosTeam\Hub\EventListener;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncNetworkCount extends AsyncTask {

    public function __construct() {
        Server::getInstance()->getAsyncPool()->submitTask($this);
    }

    public function onRun() {
        $query = QueryCounter::countAll();
        $this->setResult($query);
    }

    public function onCompletion(Server $server) {
        EventListener::$count = $this->getResult();
    }
}
