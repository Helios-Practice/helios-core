<?php

namespace HeliosTeam\Practice\Managers\Types;

use HeliosTeam\Practice\Events\PlayerEvents;
use HeliosTeam\Practice\Main;

class EventsManager {
    public function __construct(Main $main) {
        new PlayerEvents($main);
    }
}