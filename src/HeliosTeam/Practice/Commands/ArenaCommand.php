<?php

namespace HeliosTeam\Practice\Commands;

use HeliosTeam\Practice\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class ArenaCommand extends PluginCommand
{

    public function __construct(Main $main)
    {
        parent::__construct("arena", $main);
        parent::setDescription("creat or set a duels arena");
        parent::setAliases(["setarena"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender->hasPermission("core.arena")) {
            if (!$sender instanceof Player) return false;
            $sender->sendMessage("§cYou do not have permission to use this command.");
            return false;
        }
        if (count($args) !== 3) {
            $sender->sendMessage("§aUsage: §7/arena {name} {type} {kit}");
            return false;
        }
        if (!in_array(strtolower($args[1]), ["ranked", "unranked", "ffa"])) {
            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "Enter type: ranked/unranked.");
            return false;
        }
        if (!in_array($args[2], $this->getPlugin()->getKits()->getAll(true))) {
            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "The " . $args[2] . " kit does not exist.");
            return false;
        }
        $senderName = $sender->getName();
        if ($sender instanceof Player) {
            $info = array(
                "spawns" => [],
                "kit" => $args[2],
                "type" => strtolower($args[1]),
                "world" => $sender->getLevel()->getFolderName(),
                "needed" => "none"
            );
            $this->getPlugin()->getListener()->setspawns[$senderName] = [(string)$args[0], 2];
            $this->getPlugin()->getArenasConfig()->setNested($args[0], $info);
            $this->getPlugin()->getArenasConfig()->save();
            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::YELLOW . "§7Right click to set the first spawn of the arena:" . TextFormat::RED . $args[0] . ".");
        }
    }
}
