<?php

namespace HeliosTeam\Practice\Commands;

use HeliosTeam\Practice\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class AliasCommand extends PluginCommand
{

    public function __construct(Main $main)
    {
        parent::__construct("alias", $main);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!isset($args[0])) {
            $sender->sendmessage(TextFormat::RED . "§bUsage: §7" . $this->getUsage() . "");
            return true;
        }
        $name = strtolower($args[0]);
        $player = $this->getPlugin()->getServer()->getPlayer($name);
        if ($player instanceof Player) {
            $ip = $player->getPlayer()->getAddress();
            $file = new Config($this->getPlugin()->getDataFolder() . "ipdb/" . $ip . ".txt");
            $names = $file->getAll(true);
            $names = implode(', ', $names);
            $sender->sendMessage("§aListing alternate accounts...");
            $sender->sendMessage("§7" . $names);
            return true;
        }else{
            $simpleauth = $this->getPlugin()->getServer()->getPluginManager()->getPlugin("SimpleAuth");
            if($simpleauth !== null){
                $saconfig = $simpleauth->getDataProvider()->getPlayerData($name);
                if($saconfig !== null && isset($saconfig['ip']) && strlen($saconfig['ip']) > 0){
                    $lastip = $saconfig['ip'];
                    $file = new Config($this->getPlugin()->getDataFolder() . "ipdb/" . $lastip . ".txt");
                    $names = $file->getAll(true);
                    $names = implode(', ', $names);
                    $sender->sendMessage(TextFormat::GREEN . "Showing accounts used by the same IP address as " . $name . "...");
                    $sender->sendMessage(TextFormat::AQUA . $names . "");
                    return true;
                }else{
                    $sender->sendMessage(TextFormat::RED . "§cThe specified player is not online.");
                }
            }else{
                $sender->sendMessage(TextFormat::RED . "§cThe specified player is not online.");
            }
        }
        return true;
    }
}