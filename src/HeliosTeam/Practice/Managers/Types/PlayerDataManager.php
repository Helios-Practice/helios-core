<?php

namespace HeliosTeam\Practice\Managers\Types;

use HeliosTeam\Practice\Main;
use HeliosTeam\Practice\PlayerListener;

class PlayerDataManager {

    private Main $main;
    private PlayerListener $listener;

    public function __construct(Main $main, PlayerListener $listener) {
        $this->main = $main;
        $this->listener = $listener;
    }
}
