<?php

namespace HeliosTeam\Practice\Utils;

use pocketmine\Player;

class Device{

    private static $listOfOs = ["Unknown", "Android", "iOS", "macOS", "FireOS", "GearVR", "HoloLens", "Windows10", "Windows", "EducalVersion", "Dedicated", "PlayStation4", "Switch", "XboxOne"];
    public static $device;
    public static $os;

    public static function getPlayerDevice(Player $player): ?string
    {
        $name = strtolower($player->getName());
        if (!isset(self::$device[$name]) or self::$device[$name] == null) return null;
        return self::$device[$name];
    }

    public static function getPlayerOs(Player $player): ?string
    {
        $name = strtolower($player->getName());
        if (!isset(self::$os[$name]) or self::$os[$name] == null) return null;
        return self::$listOfOs[self::$os[$name]];
    }

    public static function setPlayerOs(Player $player, string $os)
    {
        self::$os[strtolower($player->getName())] = $os;
    }
}