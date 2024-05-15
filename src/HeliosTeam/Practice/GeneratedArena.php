<?php


namespace HeliosTeam\Practice;


use HeliosTeam\Practice\Tasks\FixMenuTask;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class GeneratedArena {

    const GAME_IDLE = 0;
    const GAME_STARTING = 1;
    const GAME_RUNNING = 2;

    private $plugin;
    private $name;
    private $world;
    private $spawns;
    private $kit;
    private $type;
    private $countdown;
    public $state = self::GAME_IDLE;
    private $players = [];
    private $cps = [];
    private $hits = [];
    private $blockPlaces = [];
    private $gameTime;
    private $occupiedSpawns;

    public function __construct(Main $plugin, string $name, string $world, array $spawns, string $kit, string $type) {
        $this->plugin = $plugin;
        $this->name = $name;
        $this->world = $world;
        $this->spawns = $spawns;
        $this->kit = $kit;
        $this->type = $type;
        $this->countdown = $this->getPlugin()->getCountdown();
        $this->gameTime = 0;
    }

    public function tick(): void {

        if($this->isIdle()) {
            $this->broadcastPopup(TextFormat::RED . "You are currently queued.");
        }
        if($this->isStarting()) {
            if($this->countdown === 0) $this->start();
            foreach ($this->getPlayers() as $player) {
                $arenaname = $this->getPlugin()->getArenaByPlayer($player)->getName();
                $player1 = $this->getPlugin()->getServer()->getPlayer($this->getPlugin()->getActiveDuels()->getNested("$arenaname.player1"));
                $player2 = $this->getPlugin()->getServer()->getPlayer($this->getPlugin()->getActiveDuels()->getNested("$arenaname.player2"));
                $this->getPlugin()->getScoreboardUtil()->setPlayer1Scoreboard($player1, $player2);
                $this->getPlugin()->getScoreboardUtil()->setPlayer2Scoreboard($player2, $player1);
            }
            $this->broadcastTitle(TextFormat::RED . $this->countdown);
            $this->countdown--;
        }
        if($this->isRunning()) {
            foreach($this->getPlayers() as $player) {
                $arenaname = $this->getPlugin()->getArenaByPlayer($player)->getName();
                $player1 = $this->getPlugin()->getServer()->getPlayer($this->getPlugin()->getActiveDuels()->getNested("$arenaname.player1"));
                $player2 = $this->getPlugin()->getServer()->getPlayer($this->getPlugin()->getActiveDuels()->getNested("$arenaname.player2"));
                $cps = $this->getPlugin()->preciseCpsCounter->getCps($player);
                $this->gameTime++;
                $this->getPlugin()->getScoreboardUtil()->setPlayer1Scoreboard($player1, $player2);
                $this->getPlugin()->getScoreboardUtil()->setPlayer2Scoreboard($player2, $player1);
                $this->addCps($player, $cps);
            }
        }
    }

    public function join(Player $player): void {
        if(!$this->isIdle()) {
            $player->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "The duel is on going!");
            return;
        }
        if($this->getPlugin()->getArenaByPlayer($player)) {
            $player->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "You alredy are in a match!");
            return;
        }


        $this->players[] = $player;
        $this->broadcastMessage(str_replace(["{player}", "{players}"], [$player->getName(), count($this->getPlayers())], $this->getPlugin()->getJoinMessage()));
        if(count($this->getPlayers()) === 2 && $this->isIdle()) $this->preStart();

    }

    public function quit(Player $player, $silent = false): void {
        if(!$this->inArena($player)) {
            return;
        }
        $arenaname = $this->getPlugin()->getArenaByPlayer($player)->getName();
        if(!$silent) $this->broadcastMessage(str_replace(["{player}", "{players}"], [$player->getName(), count($this->getPlayers()) - 1], $this->getPlugin()->getQuitMessage()));
        unset($this->players[array_search($player, $this->getPlayers(), true)]);
        if(!$this->isIdle() and count($this->getPlayers()) === 1) {
            $winner = reset($this->players);
            $this->eloUpdate($winner, $player);
            $this->statsUpdate($winner, $player);
            $this->saveDuelHistory($winner, $player);
            $this->stop(str_replace(["{winner}", "{loser}", "{elo_won}", "{elo_lost}"], [$winner->getName(), $player->getName(), $this->getPlugin()->getEloToAdd(), $this->getPlugin()->getEloToSub()], $this->getPlugin()->getFinishMessage()));
        }elseif(count($this->getPlayers()) < 2) {
            $this->getPlugin()->getActiveDuels()->remove($arenaname);
            $this->getPlugin()->getActiveDuels()->save();
            $this->state = self::GAME_IDLE;
        }
    }

    public function closePlayer(Player $player): void {
        if(!$this->inArena($player)) {
            return;
        }
        $arenaname = $this->getPlugin()->getArenaByPlayer($player)->getName();
        $this->getPlugin()->getActiveDuels()->remove($arenaname);
        $this->getPlugin()->getActiveDuels()->save();
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->setHealth($player->getMaxHealth());
        $player->setFood($player->getMaxFood());
        $player->removeAllEffects();
        unset($this->players[array_search($player, $this->getPlayers(), true)]);
        $player->teleport($this->getPlugin()->getServer()->getDefaultLevel()->getSpawnLocation());
        $this->getPlugin()->getScheduler()->scheduleDelayedTask(new FixMenuTask($this->getPlugin(), $player), 20);
    }

    public function preStart(): void {

        $players = $this->getPlayers();

        foreach($this->getPlayers() as $player) {
            $this->occupiedSpawns[$player->getLowerCaseName()] = $spawn = array_shift($this->spawns);
            $player->teleport(new Position($spawn[0], $spawn[1], $spawn[2], $this->getWorld()));
            $player->setGamemode($player::SURVIVAL);
            $player->setHealth($player->getMaxHealth());
            $player->setFood($player->getMaxFood());
            $player->removeAllEffects();
            $player->getInventory()->clearAll();

        }
        $this->state = self::GAME_STARTING;
    }

    public function start(): void {
        foreach($this->getPlayers() as $player) {
            $this->hits[$player->getName()] = 0;
            $this->getPlugin()->addKit($player, $this->getKit());
            $player->addTitle(TextFormat::YELLOW . "Match started", TextFormat::RED . "Enjoy!");

        }
        $this->state = self::GAME_RUNNING;
    }

    public function stop(string $message): void {
        if(!$this->isRunning()) {
            return;
        }
        foreach($this->getPlayers() as $player) {
            $this->closePlayer($player);
        }
        foreach($this->blockPlaces as $vector3) {
            $this->getWorld()->setBlock($vector3, BlockFactory::get(Block::AIR));
        }
        $this->getPlugin()->getServer()->broadcastMessage($this->getPlugin()->getPrefix() . $message);
        $this->players = [];
        $this->cps = [];
        $this->hits = [];
        $this->blockPlaces = [];
        $this->spawns += array_values($this->occupiedSpawns);
        $this->occupiedSpawns = [];
        $this->countdown = $this->getPlugin()->getCountdown();
        $this->state = self::GAME_IDLE;
    }

    public function eloUpdate(Player $winner, Player $loser): void {
        if($this->getType() !== "ranked") {
            return;
        }
        $kit = $this->getKit();
        $winnerName = $winner->getName();
        $loserName = $loser->getName();
        $winnerRankBefore = $this->getPlugin()->getRank($winnerName);
        $loserRankBefore = $this->getPlugin()->getRank($loserName);
        $eloToAdd = (int)$this->getPlugin()->getEloToAdd();
        $eloToSub = (int)$this->getPlugin()->getEloToSub();
        $winnerElo = (int)$this->getPlugin()->getPlayers()->getNested("$winnerName.$kit");
        $loserElo = (int)$this->getPlugin()->getPlayers()->getNested("$loserName.$kit");
        $loserEloNew = (($loserElo - $eloToSub) >= 0) ? $loserElo - $eloToSub : 0;
        $this->getPlugin()->getPlayers()->setNested("$winnerName.$kit", $winnerElo + $eloToAdd);
        $this->getPlugin()->getPlayers()->setNested("$loserName.$kit", $loserEloNew);
        $this->getPlugin()->getPlayers()->save();
        $winnerRankAfter = $this->getPlugin()->getRank($winnerName);
        $loserRankAfter = $this->getPlugin()->getRank($loserName);
        if($winnerRankAfter !== $winnerRankBefore) $this->getPlugin()->pureChat->setPrefix($this->getPlugin()->getRankPrefix($winnerName), $winner);
        if($loserRankAfter !== $loserRankBefore) $this->getPlugin()->pureChat->setPrefix($this->getPlugin()->getRankPrefix($loserName), $loser);
    }

    public function statsUpdate(Player $winner, Player $loser): void {
        $kit = $this->getKit();
        $winnerName = $winner->getName();
        $loserName = $loser->getName();
        $wins = (int)$this->getPlugin()->getPlayersInfo()->getNested("$winnerName.wins");
        $loses= (int)$this->getPlugin()->getPlayersInfo()->getNested("$loserName.loses");
        $this->getPlugin()->getPlayersInfo()->setNested("$winnerName.$kit.wins", $wins + 1);
        $this->getPlugin()->getPlayersInfo()->setNested("$loserName.$kit.loses", $loses + 1);
        if($this->getType() === "ranked") {
            $winnerRankeds = (int)$this->getPlugin()->getPlayersInfo()->getNested("$winnerName.ranked-played");
            $loserRankeds = (int)$this->getPlugin()->getPlayersInfo()->getNested("$loserName.ranked-played");
            $this->getPlugin()->getPlayersInfo()->setNested("$winnerName.ranked-played", $winnerRankeds + 1);
            $this->getPlugin()->getPlayersInfo()->setNested("$loserName.ranked-played", $loserRankeds + 1);
        }
        $this->getPlugin()->getPlayersInfo()->save();
    }

    public function saveDuelHistory(Player $winner, Player $loser): void {
        $winnerName = $winner->getName();
        $loserName = $loser->getName();
        $winnerItems = ["0:0:0"];
        $loserItems = ["0:0:0"];
        $players = $this->getPlugin()->getPlayerDuels()->getAll();
        $winnerArmor = [
            $winner->getArmorInventory()->getHelmet()->getId(),
            $winner->getArmorInventory()->getChestplate()->getId(),
            $winner->getArmorInventory()->getLeggings()->getId(),
            $winner->getArmorInventory()->getBoots()->getId(),
        ];
        $loserArmor = [
            $loser->getArmorInventory()->getHelmet()->getId(),
            $loser->getArmorInventory()->getChestplate()->getId(),
            $loser->getArmorInventory()->getLeggings()->getId(),
            $loser->getArmorInventory()->getBoots()->getId(),
        ];
        foreach($winner->getInventory()->getContents() as $item) {
            $winnerItems = [implode(":", [$item->getId(), $item->getDamage(), $item->getCount()])];
        }
        foreach($loser->getInventory()->getContents() as $item) {
            $loserItems = [implode(":", [$item->getId(), $item->getDamage(), $item->getCount()])];
        }
        if(isset($players[$winnerName][$loserName])) {
            $this->getPlugin()->getPlayerDuels()->removeNested("$winnerName.$loserName");
        }
        if(isset($players[$loserName][$winnerName])) {
            $this->getPlugin()->getPlayerDuels()->removeNested("$loserName.$winnerName");
        }
        $winnerInformations = [
            "kit" => $this->getKit(),
            "winner" => $winnerName,
            "date" => date("d/m/Y | H:i:s"),
            "my-stats" => [
                "items" => $winnerItems,
                "armor" => $winnerArmor,
                "life" => intval($winner->getHealth()),
                "food" => intval($winner->getFood()),
                "ping" => $winner->getPing(),
                "cps" => $this->getCpsAverage($winnerName),
                "hits" => $this->hits[$winnerName]
            ],
            "his-stats" => [
                "items" => $loserItems,
                "armor" => $loserArmor,
                "life" => intval($loser->getHealth()),
                "food" => intval($loser->getFood()),
                "ping" => $loser->getPing(),
                "cps" => $this->getCpsAverage($loserName),
                "hits" => $this->hits[$loserName]
            ]
        ];
        $loserInformations = [
            "kit" => $this->getKit(),
            "winner" => $winnerName,
            "date" => date("d/m/Y | H:i:s"),
            "my-stats" => [
                "items" => $loserItems,
                "armor" => $loserArmor,
                "life" => intval($loser->getHealth()),
                "food" => intval($loser->getFood()),
                "ping" => $loser->getPing(),
                "cps" => $this->getCpsAverage($loserName),
                "hits" => $this->hits[$loserName]
            ],
            "his-stats" => [
                "items" => $winnerItems,
                "armor" => $winnerArmor,
                "life" => intval($winner->getHealth()),
                "food" => intval($winner->getFood()),
                "ping" => $winner->getPing(),
                "cps" => $this->getCpsAverage($winnerName),
                "hits" => $this->hits[$winnerName]
            ]
        ];
        $this->getPlugin()->getPlayerDuels()->setNested("$winnerName.$loserName", $winnerInformations);
        $this->getPlugin()->getPlayerDuels()->setNested("$loserName.$winnerName", $loserInformations);
        $this->getPlugin()->getPlayerDuels()->save();
    }

    public function getPlayers(): array {
        return $this->players;
    }

    public function isIdle(): bool {
        return $this->state == self::GAME_IDLE;
    }

    public function isStarting(): bool {
        return $this->state == self::GAME_STARTING;
    }

    public function isRunning(): bool {
        return $this->state == self::GAME_RUNNING;
    }

    public function getStatus(): string {
        if($this->isIdle()) return "Idle";
        if($this->isStarting()) return "Starting";
        if($this->isRunning()) return "Running";
    }

    public function inArena(Player $player) {
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

    public function getType(): string {
        return $this->type;
    }

    public function getCountdown(): int {
        return $this->countdown;
    }

    public function addCps(Player $player, float $cps) {
        $this->cps[$player->getName()][] = $cps;
    }

    public function getCpsAverage(string $name): float {
        return (array_sum($this->cps[$name]) / count($this->cps[$name]));
    }

    public function addHit(Player $player) {
        $this->hits[$player->getName()]++;
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

    public function broadcastMessage(string $msg) {
        foreach($this->getPlayers() as $player) {
            $player->sendMessage($this->getPlugin()->getPrefix() . $msg);
        }
    }

    public function broadcastPopup(string $msg) {
        foreach($this->getPlayers() as $player) {
            $player->sendActionBarMessage($msg);
        }
    }

    public function broadcastTitle(string $msg) {
        foreach($this->getPlayers() as $player) {
            $player->addTitle($msg);
        }
    }

    public function getPlugin(): Main {
        return $this->plugin;
    }

    public function getPlayersInQueue(string $kit, string $type) {
        $inQueueArenas = 0;
        foreach($this->getPlugin()->getArenas() as $arena) {
            if($kit === $arena->getKit() and $type === $arena->getType()) {
                if(count($arena->getPlayers()) === 1) $inQueueArenas++;
            }
        }
        return $inQueueArenas;
    }

    public function getPlayersInFight(string $kit, string $type){
        $inGameArenas = 0;
        foreach($this->getPlugin()->getArenas() as $arena) {
            if($kit === $arena->getKit() and $type === $arena->getType()) {
                if(count($arena->getPlayers()) === 2) $inGameArenas++;
            }
        }
        return $inGameArenas;
    }

    public function getDuration(): string {
        return gmdate("H:i:s", $this->gameTime);
    }
}