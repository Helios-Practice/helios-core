<?php


namespace HeliosTeam\Practice\Commands;

use HeliosTeam\Practice\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class FFACmd extends PluginCommand {

    public function __construct(Main $main) {
        parent::__construct("ffa", $main);
        parent::setDescription("Create/set ffa");
        parent::setAliases(["setffa"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if (!$sender->hasPermission("core.arena")) {
            if (!$sender instanceof Player) return true;
            $sender->sendMessage("§cYou do not have permission to use this command.");
            return false;
        }
        if (count($args) !== 3) {
            $sender->sendMessage("§aUsage: §7/arena {name} {type} {kit}");
            return false;
        }
        if (!in_array(strtolower($args[1]), ["ffa"])) {
            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "The type must be: FFA.");
            return false;
        }
        if (!in_array($args[2], $this->getPlugin()->getFFAKits()->getAll(true))) {
            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "The " . $args[2] . " kit does not exist.");
            return false;
        }
        $senderName = $sender->getName();
        if ($sender instanceof Player) {
            $info = array(
                "name" => $args[0],
                "kit" => $args[2],
                "type" => strtolower($args[1]),
                "world" => $sender->getLevel()->getFolderName()
            );
            $this->getPlugin()->getFFAArenasConfig()->setNested($args[0], $info);
            $this->getPlugin()->getFFAArenasConfig()->save();
            $sender->sendMessage("§aFFA arena created successfully:" . $args[0] . ".");
        }
    }
}