<?php

namespace HeliosTeam\Practice\Managers\Types;

use HeliosTeam\Practice\Entity\SplashPotion;
use pocketmine\entity\Entity;

class EntityManager {

    public function __construct() {
        Entity::registerEntity(SplashPotion::class, true, ["SplashPotion1"]);
    }
}