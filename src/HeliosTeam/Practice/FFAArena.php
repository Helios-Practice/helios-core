<?php
declare(strict_types=1);

namespace HeliosTeam\Practice;

use pocketmine\Player;
use pocketmine\level\Level;

class FFAArena {

    private $plugin;
    private $name;
    private $world;
    private $kit;
    private $players = [];
    private $blockPlaces = [];
    private $type;

    public function __construct(Main $plugin, string $name, string $world, string $kit, string $type) {
        $this->plugin = $plugin;
        $this->name = $name;
        $this->world = $world;
        $this->kit = $kit;
        $this->type = $type;
    }

    public function tick(): void {
        // TODO
    }

    public function getPlayers(): array {
        return $this->players;
    }

    public function inFFAArena(Player $player) {
        return in_array($player, $this->getPlayers(), true);
    }

    public function getWorld(): Level {
        return $this->getPlugin()->getServer()->getLevelByName($this->getWorldName());
    }

    public function getWorldName(): string {
        return $this->world;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getKit(): string {
        return $this->kit;
    }

    public function getPlacedBlocks(): array {
        return $this->blockPlaces;
    }

    public function addPlacedBlock($x, $y, $z) {
        $this->blockPlaces[] = new Vector3($x, $y, $z);
    }

    public function removePlacedBlock($x, $y, $z) {
        $vector3 = new Vector3($x, $y, $z);
        unset($this->blockPlaces[array_search($vector3, $this->blockPlaces)]);
    }

    public function getPlugin(): Main {
        return $this->plugin;
    }
}
