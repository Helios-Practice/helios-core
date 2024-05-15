<?php

namespace HeliosTeam\Practice\Managers\Types;

use HeliosTeam\Practice\Commands\AcceptCommand;
use HeliosTeam\Practice\Commands\ArenaCommand;
use HeliosTeam\Practice\Commands\DuelCommand;
use HeliosTeam\Practice\Commands\EventCommand;
use HeliosTeam\Practice\Commands\FFACmd;
use HeliosTeam\Practice\Commands\PracticeCommand;
use HeliosTeam\Practice\Main;

class CommandManager {

    public function __construct(Main $main) {
        $main->getServer()->getCommandMap()->register(strtolower($main->getName()), new DuelCommand($main));
        $main->getServer()->getCommandMap()->register(strtolower($main->getName()), new PracticeCommand($main));
        $main->getServer()->getCommandMap()->register("accept", new AcceptCommand($main));
        $main->getServer()->getCommandMap()->register("arena", new ArenaCommand($main));
        $main->getServer()->getCommandMap()->register("ffa", new FFACmd($main));
        $main->getServer()->getCommandMap()->register("event", new EventCommand($main));
    }
}