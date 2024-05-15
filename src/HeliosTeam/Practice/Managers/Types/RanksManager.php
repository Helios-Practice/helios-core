<?php

namespace HeliosTeam\Practice\Managers\Types;

use HeliosTeam\Practice\Main;
use HeliosTeam\Practice\PlayerListener;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\Player;

class RanksManager implements Listener {

    protected const GUEST = 1;
    protected const ELITE = 2;
    protected const MVP = 3;
    protected const MINIYT = 4;
    protected const FAMOUS = 5;
    protected const FAMOUS_PLUS = 6;
    protected const BUILDER = 7;
    protected const TRIAL_MOD = 8;
    protected const MOD = 9;
    protected const SENIOR_MOD = 10;
    protected const ADMIN = 11;
    protected const DEVELOPER = 12;
    protected const OWNER = 13;
    protected const H = 14;

    private Main $plugin;
    private PlayerListener $listener;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function getMain() : Main {
        return $this->getMain();
    }

    public function sendStaffAlert(string $message) : void {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $staff) {
            if ($staff->hasPermission("staff.alerts")) {
                $staff->sendActionBarMessage($message);
            }
        }
    }

    public static function getFormat($format) {
        switch($format) {
            case "Guest":
                $format = "[Guest]";
                return $format;
                break;
            case "H":
                $format = "[H]";
                return $format;
                break;
            case "Elite":
                $format = "[Elite]";
                return $format;
                break;
            case "MVP":
                $format = "[MVP]";
                return $format;
                break;
            case "MiniYT":
                $format = "[MiniYT]";
                return $format;
                break;
            case "Famous":
                $format = "[Famous]";
                return $format;
                break;
            case "Famous+":
                $format = "[Famous+]";
                return $format;
                break;
            case "Builder":
                $format = "[Builder]";
                return $format;
                break;
            case "Trial-Mod":
                $format = "[Trial-Mod]";
                return $format;
                break;
            case "Mod":
                $format = "[Mod]";
                return $format;
                break;
            case "Senior-Mod":
                $format = "[Senior-Mod]";
                return $format;
                break;
            case "Admin":
                $format = "[Admin]";
                return $format;
                break;
            case "Developer":
                $format = "[Developer]";
                return $format;
                break;
            case "Owner":
                $format = "[Owner]";
                return $format;
                break;
        } return true;
    }

    public function setRank(Player $player, string $rank) : void {
        $ranks = array("Guest", "H", "Elite", "MVP", "MiniYT", "Famous", "Famous+", "Builder", "Trial-Mod", "Mod", "Senior-Mod", "Admin", "Developer", "Owner");
        if (in_array($rank, $ranks)) {
            $player->sendMessage("Your rank has been set to: " . $rank . ".");
            $this->sendStaffAlert($player->getName() . " ---> " . $rank);
        }
    }

    public static function getNameTagFormat($rank) {
        switch($rank) {
            case "Guest":
        }
    }
}