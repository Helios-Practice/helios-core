<?php

namespace HeliosTeam\Practice\Commands;

use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use HeliosTeam\Practice\Main;

class DuelCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("duel", "duel a player", "§aUsage: §7/duel {player}");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        $duelStickPlayers = $this->getPlugin()->getListener()->duelStickPlayers;
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "Use this command in-game.");
            return false;
        }
        if (!isset($args[0])) {
            $sender->sendMessage($this->getUsage());
            return false;
        }

        if (count($args) !== 1) {
            $sender->sendMessage("§aUsage: §7/duel {player}");
            return false;
        }
        $target = $this->getPlugin()->getServer()->getPlayer($args[0]);
        if (!($target instanceof Player)) {
            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . $args[0] . " is not online.");
            return false;
        }
        if ($target->getName() === $sender->getName()) {
            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . " You can't duel yourself.");
            return false;
        }
        $this->getPlugin()->getUI()->duelStickUI($sender, $target);
        return true;
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }
}
