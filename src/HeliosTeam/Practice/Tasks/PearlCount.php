<?php

namespace HeliosTeam\Practice\Tasks;

use HeliosTeam\Practice\Events\PlayerEvents;
use HeliosTeam\Practice\Main;
use pocketmine\scheduler\Task;
use pocketmine\Player;

class PearlCount extends Task {

    private $plugin;
    private $player;
    private $timer;

    public function __construct(Main $plugin, Player $player, int $timer) {
        $this->plugin = $plugin;
        $this->timer = $timer;
        $this->player = $player;
    }

    public function onRun(int $currentTick) {

        $this->timer = $this->timer - 1;

        if ($this->player->isOnline()) {
            $this->timer--;
            switch ($this->timer) {
                case 99:
                    $this->player->setXpProgress(1);
                    break;
                case 98:
                    $this->player->setXpProgress(0.99);
                    break;
                case 97:
                    $this->player->setXpProgress(0.98);
                    $this->player->setXpLevel(9);
                    break;
                case 96:
                    $this->player->setXpProgress(0.96);
                    break;
                case 95:
                    $this->player->setXpProgress(0.95);
                    break;
                case 94:
                    $this->player->setXpProgress(0.94);
                    break;
                case 93:
                    $this->player->setXpProgress(0.93);
                    break;
                case 92:
                    $this->player->setXpProgress(0.92);
                    break;
                case 91:
                    $this->player->setXpProgress(0.91);
                    break;
                case 90:
                    $this->player->setXpProgress(0.9);
                    break;
                case 89:
                    $this->player->setXpProgress(0.89);
                    $this->player->setXpLevel(8);
                    break;
                case 88:
                    $this->player->setXpProgress(0.88);
                    break;
                case 87:
                    $this->player->setXpProgress(0.87);
                    break;
                case 86:
                    $this->player->setXpProgress(0.86);
                    break;
                case 85:
                    $this->player->setXpProgress(0.85);
                    break;
                case 84:
                    $this->player->setXpProgress(0.84);
                    break;
                case 83:
                    $this->player->setXpProgress(0.83);
                    break;
                case 82:
                    $this->player->setXpProgress(0.82);
                    break;
                case 81:
                    $this->player->setXpProgress(0.81);
                    break;
                case 80:
                    $this->player->setXpProgress(0.8);
                    break;
                case 79:
                    $this->player->setXpProgress(0.79);
                    $this->player->setXpLevel(7);
                    break;
                case 78:
                    $this->player->setXpProgress(0.78);
                    break;
                case 77:
                    $this->player->setXpProgress(0.77);
                    break;
                case 76:
                    $this->player->setXpProgress(0.76);
                    break;
                case 75:
                    $this->player->setXpProgress(0.75);
                    break;
                case 74:
                    $this->player->setXpProgress(0.74);
                    break;
                case 73:
                    $this->player->setXpProgress(0.73);
                    break;
                case 72:
                    $this->player->setXpProgress(0.72);
                    break;
                case 71:
                    $this->player->setXpProgress(0.71);
                    break;
                case 70:
                    $this->player->setXpProgress(0.7);
                    break;
                case 69:
                    $this->player->setXpProgress(0.69);
                    $this->player->setXpLevel(6);
                    break;
                case 68:
                    $this->player->setXpProgress(0.68);
                    break;
                case 67:
                    $this->player->setXpProgress(0.67);
                    break;
                case 66:
                    $this->player->setXpProgress(0.66);
                    break;
                case 65:
                    $this->player->setXpProgress(0.65);
                    break;
                case 64:
                    $this->player->setXpProgress(0.64);
                    break;
                case 63:
                    $this->player->setXpProgress(0.63);
                    break;
                case 62:
                    $this->player->setXpProgress(0.62);
                    break;
                case 61:
                    $this->player->setXpProgress(0.61);
                    break;
                case 60:
                    $this->player->setXpProgress(0.6);
                    break;
                case 59:
                    $this->player->setXpProgress(0.59);
                    $this->player->setXpLevel(5);
                    break;
                case 58:
                    $this->player->setXpProgress(0.58);
                    break;
                case 57:
                    $this->player->setXpProgress(0.57);
                    break;
                case 56:
                    $this->player->setXpProgress(0.56);
                    break;
                case 55:
                    $this->player->setXpProgress(0.55);
                    break;
                case 54:
                    $this->player->setXpProgress(0.54);
                    break;
                case 53:
                    $this->player->setXpProgress(0.53);
                    break;
                case 52:
                    $this->player->setXpProgress(0.52);
                    break;
                case 51:
                    $this->player->setXpProgress(0.51);
                    break;
                case 50:
                    $this->player->setXpProgress(0.5);
                    break;
                case 49:
                    $this->player->setXpProgress(0.49);
                    $this->player->setXpLevel(4);
                    break;
                case 48:
                    $this->player->setXpProgress(0.48);
                    break;
                case 47:
                    $this->player->setXpProgress(0.47);
                    break;
                case 46:
                    $this->player->setXpProgress(0.46);
                    break;
                case 45:
                    $this->player->setXpProgress(0.45);
                    break;
                case 44:
                    $this->player->setXpProgress(0.44);
                    break;
                case 43:
                    $this->player->setXpProgress(0.43);
                    break;
                case 42:
                    $this->player->setXpProgress(0.42);
                    break;
                case 41:
                    $this->player->setXpProgress(0.41);
                    break;
                case 40:
                    $this->player->setXpProgress(0.4);
                    break;
                case 39:
                    $this->player->setXpProgress(0.39);
                    $this->player->setXpLevel(3);
                    break;
                case 38:
                    $this->player->setXpProgress(0.38);
                    break;
                case 37:
                    $this->player->setXpProgress(0.37);
                    break;
                case 36:
                    $this->player->setXpProgress(0.36);
                    break;
                case 35:
                    $this->player->setXpProgress(0.35);
                    break;
                case 34:
                    $this->player->setXpProgress(0.34);
                    break;
                case 33:
                    $this->player->setXpProgress(0.33);
                    break;
                case 32:
                    $this->player->setXpProgress(0.32);
                    break;
                case 31:
                    $this->player->setXpProgress(0.31);
                    break;
                case 30:
                    $this->player->setXpProgress(0.3);
                    break;
                case 29:
                    $this->player->setXpProgress(0.29);
                    $this->player->setXpLevel(2);
                    break;
                case 28:
                    $this->player->setXpProgress(0.28);
                    break;
                case 27:
                    $this->player->setXpProgress(0.27);
                    break;
                case 26:
                    $this->player->setXpProgress(0.26);
                    break;
                case 25:
                    $this->player->setXpProgress(0.25);
                    break;
                case 24:
                    $this->player->setXpProgress(0.24);
                    break;
                case 23:
                    $this->player->setXpProgress(0.23);
                    break;
                case 22:
                    $this->player->setXpProgress(0.22);
                    break;
                case 21:
                    $this->player->setXpProgress(0.21);
                    break;
                case 20:
                    $this->player->setXpProgress(0.2);
                    break;
                case 19:
                    $this->player->setXpProgress(0.19);
                    $this->player->setXpLevel(1);
                    break;
                case 18:
                    $this->player->setXpProgress(0.18);
                    break;
                case 17:
                    $this->player->setXpProgress(0.17);
                    break;
                case 16:
                    $this->player->setXpProgress(0.16);
                    break;
                case 15:
                    $this->player->setXpProgress(0.15);
                    break;
                case 14:
                    $this->player->setXpProgress(0.14);
                    break;
                case 13:
                    $this->player->setXpProgress(0.13);
                    break;
                case 12:
                    $this->player->setXpProgress(0.12);
                    break;
                case 11:
                    $this->player->setXpProgress(0.11);
                    break;
                case 10:
                    $this->player->setXpProgress(0.1);
                    break;
                case 9:
                    $this->player->setXpProgress(0.09);
                    $this->player->setXpLevel(0);
                    break;
                case 8:
                    $this->player->setXpProgress(0.08);
                    break;
                case 7:
                    $this->player->setXpProgress(0.07);
                    break;
                case 6:
                    $this->player->setXpProgress(0.06);
                    break;
                case 5:
                    $this->player->setXpProgress(0.05);
                    break;
                case 4:
                    $this->player->setXpProgress(0.04);
                    break;
                case 3:
                    $this->player->setXpProgress(0.03);
                    break;
                case 2:
                    $this->player->setXpProgress(0.02);
                    break;
                case 1:
                    $this->player->setXpProgress(0.01);
                    break;
            }
            if ($this->timer <= 0) {
                $this->player->setXpProgress(0);
                unset(PlayerEvents::$cooldown[$this->player->getName()]);
                $this->plugin->getScheduler()->cancelTask($this->getTaskId());
            }
        } else {
            unset(PlayerEvents::$cooldown[$this->player->getName()]);
            $this->plugin->getScheduler()->cancelTask($this->getTaskId());
        }
    }
}
