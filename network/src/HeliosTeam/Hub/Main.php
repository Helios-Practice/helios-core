<?php

namespace HeliosTeam\Hub;

use HeliosTeam\Hub\Tasks\NetworkCount\CountTimerTask;
use HeliosTeam\Hub\Classes\FormUI;
use HeliosTeam\Hub\Classes\QueryCounter;
use HeliosTeam\Hub\Tasks\Scoreboard\ScoreboardTask;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\level\biome\Biome;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {

    public $querycounter;
    public $formui;
    public $cooltime = 0;
    public $m_version = 2, $pk;

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($eventlistener = new EventListener($this), $this);
        EventListener::$count = 0;
        new ScoreboardTask($this, 1);
        new CountTimerTask($this, 1);
        $this->formui = new FormUI($this);
        $this->querycounter = new QueryCounter($this);
    }

    public function onChunkLoadEvent(ChunkLoadEvent $event) {
        for($x = 0; $x < 16; ++ $x)
            for($z = 0; $z < 16; ++ $z)
                $event->getChunk ()->setBiomeId ($x, $z, Biome::ICE_PLAINS );
    }
    public function onPlayerJoinEvent(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $pk = new LevelEventPacket();
        $pk->evid = 3001;
        $pk->data = 10000;
        $player->dataPacket ($pk);
    }

    public function getFormUI(): FormUI {
        return $this->formui;
    }

    public function getQueryCounter(): QueryCounter {
        return $this->querycounter;
    }
}