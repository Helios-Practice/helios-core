<?php

namespace HeliosTeam\Practice\Commands;

use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use HeliosTeam\Practice\Main;

class PracticeCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("practice", "the practice join/quit command", "§aUsage: §7/practice help");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        $duelStickPlayers = $this->getPlugin()->getListener()->duelStickPlayers;
        if(!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "§cUse this command in-game.");
            return false;
        }
        if(!isset($args[0])) {
            $sender->sendMessage($this->getUsage());
            return false;
        }
        switch(array_shift($args)) {
            case "help":
                $help = [
                    "" => "Practice Help Page",
                    "join" => "Join a duel",
                    "quit" => "Quit a duel",
                    "ffa" => "Join a ffa arena",
                ];
                foreach($help as $key => $value) {
                    $sender->sendMessage(TextFormat::RED . $key . TextFormat::AQUA . " » " . TextFormat::YELLOW . $value);
                }
                break;
            case "join":
                if(count($args) !== 1) {
                    $sender->sendMessage(TextFormat::WHITE . "§aUsage: §7/practice join {arena}");
                    return false;
                }
                if($arena = $this->getPlugin()->getArenaByName($args[0])) {
                    $this->getPlugin()->getServer()->loadLevel($arena->getWorldName());
                    $arenaname = $arena->getName();
                    $activeduels = $this->getPlugin()->getActiveDuels()->getAll();
                    if(!isset($activeduels[$arenaname]["opponent"])) {
                        $this->getPlugin()->getActiveDuels()->setNested("$arenaname.opponent", "none");
                        $this->getPlugin()->getActiveDuels()->setNested("$arenaname.player1", $sender->getName());
                        $this->getPlugin()->getActiveDuels()->save();
                        $arena->join($sender);
                        $this->plugin->GiveQueueItem($sender);
                    } elseif($this->getPlugin()->getActiveDuels()->getNested("$arenaname.opponent") === "none") {
                        $this->getPlugin()->getActiveDuels()->setNested("$arenaname.player2", $sender->getName());
                        $this->getPlugin()->getActiveDuels()->save();
                        $arena->join($sender);
                    }
                }else{
                    $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "§cThat arena does not exist.");
                }
                break;
            case "quit":
                if($arena = $this->getPlugin()->getArenaByPlayer($sender)) {
                    $arena->quit($sender);
                    $this->getPlugin()->RemoveQueueItem($sender);
                }else{
                    $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "You aren't in an arena.");
                }
                break;
            case "ffa":
                if(count($args) !== 1) {
                    $sender->sendMessage(TextFormat::WHITE . "§aUsage: §7/practice ffa {arena}");
                    return false;
                }
                $arenaname = $args[0];
                if($arena = $this->getPlugin()->getFFAArenasConfig()->getNested("$arenaname.name") === $arenaname) {
                    $name = $sender->getName();
                    $this->getPlugin()->getActiveFFA()->remove("$name");
                    $sender->getServer()->loadLevel($this->getPlugin()->getFFAArenasConfig()->getNested("$arenaname.world"));
                    $this->getPlugin()->addFFAKit($sender, $this->getPlugin()->getFFAArenasConfig()->getNested("$arenaname.kit"));
                    $activeffa = $this->getPlugin()->getActiveFFA()->getAll();
                    if(!isset($activeffa[$name]["none"])) {
                        $this->getPlugin()->getActiveFFA()->setNested("$name.none", "abc");
                        $this->getPlugin()->getActiveFFA()->setNested("$name.arena", $arenaname);
                        $this->getPlugin()->getActiveFFA()->save();
                    }
                    $sender->teleport($this->getPlugin()->getServer()->getLevelbyName($this->getPlugin()->getFFAArenasConfig()->getNested("$arenaname.world"))->getSpawnLocation());
                    $this->getPlugin()->getScoreboardUtil()->setFFAScoreboard($sender);
                } else {
                    $sender->sendMessage("§cThat arena does not exist.");
                }
                break;
            default:
                $sender->sendMessage($this->getUsage());
                break;
        }
        return true;
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }
}
