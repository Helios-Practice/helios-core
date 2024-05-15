<?php

namespace HeliosTeam\Practice\Commands;

use HeliosTeam\Practice\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

class EventCommand extends PluginCommand {

    private $players = [];

    public function __construct(Main $main) {
        parent::__construct("event", $main);
        parent::setDescription("join or start a sumo event");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {

        if (!isset($args[0])) {
            $sender->sendmessage("§aUsage: §7" . $this->getUsage());
            return true;
        }

        if (!$sender instanceof Player) {
            $sender->sendMessage("§cPlease use this command in-game!");
        }

        return true;
    }
}