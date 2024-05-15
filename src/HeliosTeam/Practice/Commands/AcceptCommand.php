<?php

namespace HeliosTeam\Practice\Commands;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class AcceptCommand extends PluginCommand {

    public function __construct(Plugin $owner) {
        parent::__construct("accept", $owner);
        parent::setDescription("Accept a player");
        parent::setAliases(["a"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        $duelStickPlayers = $this->getPlugin()->getListener()->duelStickPlayers;
        if (!isset($args[0])) {
            if(!$sender instanceof Player) return false;
                $sender->sendMessage("§cPlease enter a player.");
            return false;
        }
        if(!isset($duelStickPlayers[$args[0]]) or !isset($duelStickPlayers[$args[0]][$sender->getName()])) {
            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . $args[0] . " has not send a duel request.");
            return false;
        }
        $target = $this->getPlugin()->getServer()->getPlayer($args[0]);
        if(!($target instanceof Player)) {
            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . $args[0] . " is no longer online.");
            return false;
        }
        $arenas = [];
        $kit = $duelStickPlayers[$args[0]][$sender->getName()];
        foreach($this->getPlugin()->getArenas() as $arena) {
            if($arena->getType() === "unranked" and $arena->getKit() === $kit and count($arena->getPlayers()) === 0) $arenas[] = $arena->getName();
        }
        if(empty($arenas)) {
            $arenas = [];
            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "No open arenas.");
            return false;
        }
        $randomArena = $arenas[array_rand($arenas)];
        $this->getPlugin()->getServer()->dispatchCommand($sender, "practice join " . $randomArena);
        $this->getPlugin()->getServer()->dispatchCommand($target, "practice join " . $randomArena);
        unset($duelStickPlayers[$args[0]][$sender->getName()]);

    }
}