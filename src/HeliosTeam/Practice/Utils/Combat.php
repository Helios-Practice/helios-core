<?php

namespace HeliosTeam\Practice\Utils;

use HeliosTeam\Practice\Events\PlayerEvents;
use pocketmine\Player;
use pocketmine\Server;

class Combat {

    public static $combat;

    public static function CreateCombatLink(Player $damager, Player $target)
    {
        self::$combat[$damager->getName()][] = $target->getName();
        self::$combat[$target->getName()][] = $damager->getName();
    }

    public static function getCombatLink(Player $player) : Player
    {
        $player = Server::getInstance()->getPlayerExact(PlayerEvents::$combat[$player->getName()]);
        if ($player->isOnline()){
            return $player;
        }
    }

    public static function isInCombat(Player $player)
    {
        return isset(self::$combat[$player->getName()]);
    }
}