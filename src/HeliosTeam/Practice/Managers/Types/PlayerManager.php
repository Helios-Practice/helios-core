<?php


namespace HeliosTeam\Practice\Managers\Types;


use HeliosTeam\Practice\Main;
use HeliosTeam\Practice\Other\PlayerInterface;
use HeliosTeam\Practice\Tasks\CombatTask;
use pocketmine\Player;
use pocketmine\utils\Config;
use HeliosTeam\Practice\Managers\HeliosManager;

class PlayerManager implements PlayerInterface {

    private Main $main;

    public function __construct(Main $main) {
        $this->main = $main;
    }

    public function IsInLogger(Player $player): bool {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $combat = "combat";
        return $config->getNested("$combat.isincombat");
    }

    public function IsInFFA(Player $player): bool {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $ffa = "ffa";
        return $config->getNested("$ffa.inffa");
    }

    public function getFFAArena(Player $player): bool {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $ffa = "ffa";
        return $config->getNested("$ffa.arena");
    }

    public function setInFFA(Player $player, string $arena): void {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $ffa = "ffa";
        $config->setNested("$ffa.inffa", true);
        $config->setNested("$ffa.arena", $arena);
        $config->save();
    }

    public function IsInDuel(Player $player): bool {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $duels = "duels";
        return $config->get("$duels.induels");
    }

    public function getDuelArena(Player $player): bool {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $duels = "duels";
        return $config->get("$duels.arena");
    }

    public function getDuelOpponent(Player $player): bool {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $duels = "duels";
        return $config->get("$duels.opponent");
    }

    public function addToDuels(Player $player, Player $opponent, string $arena): void {
        $name = $player->getName();
        $opponentname = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $opponentconfig = new Config($this->getMain()->getDataFolder() . "players/" . $opponentname . ".yml", CONFIG::YAML);
        $duels = "duels";
        $config->setNested("$duels.induels", true);
        $opponentconfig->setNested("$duels.induels", true);
        $config->save();
        $opponentconfig->save();
        $config->setNested("$duels.arena", $arena);
        $opponentconfig->setNested("$duels.arena", $arena);
        $config->save();
        $opponentconfig->save();
        $config->setNested("$duels.opponent", $opponentname);
        $opponentconfig->setNested("$duels.opponent", $name);
        $config->save();
        $opponentconfig->save();
    }

    public function removeFromDuels(Player $player, Player $opponent, string $arena): void {
        $name = $player->getName();
        $opponentname = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $opponentconfig = new Config($this->getMain()->getDataFolder() . "players/" . $opponentname . ".yml", CONFIG::YAML);
        $duels = "duels";
        $config->setNested("$duels.induels", false);
        $opponentconfig->setNested("$duels.induels", false);
        $config->save();
        $opponentconfig->save();
        $config->removeNested("$duels.arena");
        $opponentconfig->removeNested("$duels.arena");
        $config->save();
        $opponentconfig->save();
        $config->removeNested("$duels.opponent");
        $opponentconfig->removeNested("$duels.opponent");
        $config->save();
        $opponentconfig->save();
    }

    public function getAttacker(Player $player): Player
    {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $combat = "combat";
        return $this->getMain()->getServer()->getPlayer($config->get("$combat.attacker"));
    }

    public function getOpponent(Player $player): Player
    {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $duels = "duels";
        return $this->getMain()->getServer()->getPlayer($config->get("$duels.opponent"));
    }

    public function getDeaths(string $name): int {
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        return (int) $config->get("deaths");
    }

    public function getKills(string $name): int {
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        return (int) $config->get("kills");
    }

    public function getStreaks(string $name): int {
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        return (int) $config->get("streaks");
    }

    public function addDeathPoint(Player $player, int $points = 1): void {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $config->set("deaths", $this->getDeaths($name) + $points);
        $config->save();
    }

    public function addKillPoint(Player $player, int $points = 1): void {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $config->set("kills", $this->getKills($name) + $points);
        $config->save();
    }

    public function addStreak(Player $player, int $points = 1): void {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $config->set("streaks", $this->getStreaks($name) + $points);
        $config->save();
    }

    public function resetStreak(Player $player) {
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $config->set("streaks", 0);
        $config->save();
    }

    public function addToLogger(Player $player, string $attackername): void {
        $name = $player->getName();
        $combat = "combat";
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $attackercfg = new Config($this->getMain()->getDataFolder() . "players/" . $attackername . ".yml", CONFIG::YAML);
        $attacker = $this->getMain()->getServer()->getPlayer($attackername);
        $config->setNested("$combat.incombat", true);
        $config->setNested("$combat.attacker", $attackername);
        $config->save();
        $config->setNested("$combat.incombat", true);
        $config->setNested("$combat.attacker", $name);
        $attackercfg->save();
        $this->getMain()->getScheduler()->scheduleRepeatingTask(new CombatTask($this->getMain(), $player), 20);
        $this->getMain()->getScheduler()->scheduleRepeatingTask(new CombatTask($this->getMain(), $attacker), 20);
    }

    public function removeFromLogger(Player $player): void {
        $combat = "combat";
        $name = $player->getName();
        $config = new Config($this->getMain()->getDataFolder() . "players/" . $name . ".yml", CONFIG::YAML);
        $config->setNested("$combat.incombat", false);
        $config->removeNested("$combat.attacker");
        $config->save();
        $player->sendMessage("You are now removed from Combat");
    }

    public function getIP(Player $player): bool {
        return $player->getAddress();
    }

    public function getUUID(Player $player) : \pocketmine\utils\UUID {
        return $player->getUniqueId();
    }

    public function getCID(Player $player): bool {
        return $player->getClientId();
    }

    public function getXUID(Player $player): bool {
        return $player->getXuid();
    }

    public function getMain(): Main {
        return $this->main;
    }
}