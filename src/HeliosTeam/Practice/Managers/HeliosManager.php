<?php

namespace HeliosTeam\Practice\Managers;

use HeliosTeam\Practice\Main;
use HeliosTeam\Practice\Managers\Types\CommandManager;
use HeliosTeam\Practice\Managers\Types\EntityManager;
use HeliosTeam\Practice\Managers\Types\EventsManager;

class HeliosManager {

    public function __construct(Main $main) {
        new EntityManager();
        new CommandManager($main);
        new EventsManager($main);
    }
}