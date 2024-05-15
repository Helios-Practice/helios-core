<?php

namespace HeliosTeam\Practice\Managers\Types;

use HeliosTeam\Practice\Main;
use HeliosTeam\Practice\PlayerListener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\Player;

class KBManager implements Listener {

    const NODEBUFF_KB = 0.40;
    const NODEBUFF_DELAY = 10;
    const GAPPLE_KB = 0.37;
    const GAPPLE_DELAY = 8;
    const FIST_KB = 0.41;
    const FIST_DELAY = 7.5;
    const BUILD_KB = 0.40;
    const BUILD_DELAY = 9;
    const SOUP_KB = 0.39;
    const SOUP_DELAY = 10;
    const COMBO_KB = 0.24;
    const COMBO_DELAY = 2;

    private Main $main;
    private PlayerListener $listener;

    public function onEntityDamage(EntityDamageEvent $event) {
        if ($event instanceof EntityDamageByEntityEvent) {
            $entity = $event->getEntity();
            if ($entity instanceof Player) {
                switch ($entity->getLevel()->getName()) {
                    case "NoDebuff-D1" && "NoDebuff-D2" && "NoDebuff-D3" && "NoDebuff-D4":
                    case "NoDebuff-FFA":
                        $event->setKnockBack(self::NODEBUFF_KB);
                        $event->setAttackCooldown(self::NODEBUFF_DELAY);
                        break;
                    case "Gapple-D1" && "Gapple-D2" && "Gapple-D3" && "Gapple-D4":
                    case "Gapple-FFA":
                        $event->setKnockBack(self::GAPPLE_KB);
                        $event->setAttackCooldown(self::GAPPLE_DELAY);
                        break;
                    case "Fist-FFA" && "Resistance-FFA":
                        $event->setKnockBack(self::FIST_KB);
                        $event->setAttackCooldown(self::FIST_DELAY);
                        break;
                    case "BuildUHC-D1" && "BuildUHC-D2" && "BuildUHC-D3" && "BuildUHC-D4":
                    case "Build-FFA":
                        $event->setKnockBack(self::BUILD_KB);
                        $event->setAttackCooldown(self::BUILD_DELAY);
                        break;
                    case "Soup-FFA":
                        $event->setKnockBack(self::SOUP_KB);
                        $event->setAttackCooldown(self::SOUP_DELAY);
                        break;
                    case "Combo-FFA":
                        $event->setKnockBack(self::COMBO_KB);
                        $event->setAttackCooldown(self::COMBO_DELAY);
                        break;
                }
            }
        }
    }
}
