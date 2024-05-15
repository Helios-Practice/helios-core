<?php

namespace HeliosTeam\Practice\Other;

use HeliosTeam\Practice\Utils\Arena;
use Libs\Forms\SimpleForm;
use Libs\Forms\CustomForm;

use muqsit\invmenu\InvMenu;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use HeliosTeam\Practice\Main;
use HeliosTeam\Practice\PlayerListener;
use pocketmine\event\Listener;
use pocketmine\utils\Utils;

class UI {

    private $plugin;
    private $type = [];
    private $targets = [];
    private $sfidante = [];
    private $formAPI;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
        $this->formAPI = $this->getPlugin()->getServer()->getPluginManager()->getPlugin("FormAPI");
    }

    public function getArenaInfo(Arena $arena): string {
        return TextFormat::YELLOW . "Status: " . TextFormat::RED . $arena->getStatus() . TextFormat::BLACK . " | " . TextFormat::YELLOW . "Players: " . TextFormat::RED . count($arena->getPlayers());
    }

    public function getArenasInfo(string $kit, string $type): string {
        $inGameArenas = 0;
        $inQueueArenas = 0;
        foreach($this->plugin->getArenas() as $arena) {
            if($kit === $arena->getKit() and $type === $arena->getType()) {
                if(count($arena->getPlayers()) === 2) $inGameArenas++;
                if(count($arena->getPlayers()) === 1) $inQueueArenas++;
            }
        }
        return TextFormat::YELLOW . "§8Playing: " . $inGameArenas . TextFormat::BLACK . " " . TextFormat::YELLOW . "§8Queued: " . $inQueueArenas;
    }

    public function joinUI(Player $player): void {
        $form = $this->formAPI->createSimpleForm(function(Player $event, ?string $data) {
            if($data === null) return;
            $playerName = $event->getName();
            $rankedsPlayed = $this->getPlugin()->getPlayersInfo()->getNested("$playerName.ranked-played");
            switch($data) {
                case "ranked":
                    if($rankedsPlayed >= $this->getPlugin()->getMaxRankeds($event)) {
                        $event->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "You played the maximum number of ranked games for today");
                        return;
                    }
                    $this->kitJoinUI($event, $data);
                    break;
                 case "unranked":
                     $this->kitJoinUI($event, $data);
                     break;
                 case "quit":
                     $this->getPlugin()->getServer()->dispatchCommand($event, "practice quit");
                     break;
            }
        });
        $form->setTitle(TextFormat::YELLOW . "Practice");
        $form->setContent(TextFormat::BLUE . "Choose a type");
        $form->addButton(TextFormat::BOLD . TextFormat::BLUE . "Ranked", -1, "", "ranked");
        $form->addButton(TextFormat::BOLD . TextFormat::BLUE . "Unranked", -1, "", "unranked");
        $form->sendToPlayer($player);
    }

    public function kitJoinUI(Player $player, string $type): void {
        $this->type[$player->getName()] = $type;
        $form = $this->formAPI->createSimpleForm(function(Player $event, ?string $data) use ($player) {
            if($data === null) return;
            $arenas1 = [];
            $arenas0 = [];
            foreach($this->getPlugin()->getArenas() as $arena) {
                if($this->type[$event->getName()] === $arena->getType() and $data === $arena->getKit()) {
                    if(count($arena->getPlayers()) === 1) $arenas1[] = $arena->getName();
                    if(count($arena->getPlayers()) === 0) $arenas0[] = $arena->getName();
                }
            }
            $arenas = (!empty($arenas1)) ? $arenas1 : ((!empty($arenas0)) ? $arenas0 : []);
            if(empty($arenas)) {
                $event->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "There aren't empty arenas!");
                return;
            }
            $randomArena = $arenas[array_rand($arenas)];
            $this->getPlugin()->getServer()->dispatchCommand($event, "practice join " . $randomArena);
            unset($this->type[$event->getName()]);
        });
        $form->setTitle(TextFormat::YELLOW . "§l§8UNRANKED DUELS");
        $form->setContent(TextFormat::BLUE . "§fChoose an arena:");
        foreach($this->getPlugin()->getKits()->getAll() as $kit => $info) {
            $form->addButton("§8" . $kit . TextFormat::EOL . $this->getArenasInfo($kit, $type), 0, $info["image-path"], $kit);
        }
        $form->sendToPlayer($player);
    }

    public function duelStickUI(Player $player, Player $target): void {
        $this->targets[$player->getName()] = $target;
        $form = $this->formAPI->createSimpleForm(function(Player $event, ?string $data) {
            if($data === null) return;
            $target = $this->targets[$event->getName()];
            $this->getPlugin()->getListener()->duelStickPlayers[$event->getName()][$target->getName()] = $data;
            $event->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "You challenged " . TextFormat::YELLOW . $target->getName() . TextFormat::RED . " in a " . TextFormat::YELLOW . $data . TextFormat::RED . " duel.");
            $target->sendMessage($this->getPlugin()->getPrefix() . TextFormat::YELLOW . $event->getName() . TextFormat::RED . " challenged you in a " . TextFormat::YELLOW . $data . TextFormat::RED . " duel." . TextFormat::WHITE . " Use /duel accept " . $event->getName());
            unset($this->targets[$event->getName()]);
        });
        $form->setTitle(TextFormat::YELLOW . "Practice Kits");
        $form->setContent(TextFormat::BLUE . "Choose a kit for the duel");
        foreach($this->getPlugin()->getKits()->getAll() as $kit => $info) {
            $form->addButton(TextFormat::BOLD . TextFormat::RED . $kit, 0, $info["image-path"], $kit);
        }
        $form->sendToPlayer($player);
    }

    public function seeDuelRequests(Player $player): void {
        $kits = $this->getPlugin()->getKits()->getAll();
        $duelStickPlayers = $this->getPlugin()->getListener()->duelStickPlayers;
        if(!is_array($duelStickPlayers)) {
            $player->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "You haven't any duel request!");
            return;
        }
        $form = $this->formAPI->createSimpleForm(function(Player $event, ?string $data) {
            if($data !== null) $this->getPlugin()->getServer()->dispatchCommand($event, "duel accept " . $data);
        });
        $form->setTitle(TextFormat::YELLOW . "Your Duel Requests");
        $form->setContent(TextFormat::BLUE . "Click a request to accept it");
        foreach($duelStickPlayers as $sender => $array) {
            if(isset($array[$player->getName()])) $form->addButton(TextFormat::YELLOW . $sender . TextFormat::EOL . TextFormat::RED . "Kit: " . TextFormat::WHITE . $array[$player->getName()], 0, $kits[$array[$player->getName()]]["image-path"], $sender);
        }
        $form->sendToPlayer($player);
    }

    public function statsUI(Player $player): void {
        $playerName = $player->getName();
        $kits = $this->getPlugin()->getKits()->getAll();
        $playersInfo = $this->getPlugin()->getPlayersInfo()->getAll();
        $playerElo = $this->getPlugin()->getPlayers()->get($player->getName());
        foreach($playerElo as $kit => $points) {
            $elo[] = $points;
        }
        $globalElo = array_sum($elo);
        foreach($playersInfo[$playerName] as $kit => $stats) {
            $wins[] = $stats["wins"];
            $loses[] = $stats["loses"];
        }
        $globalWins = array_sum($wins);
        $globalLoses = array_sum($loses);
        $form = $this->formAPI->createSimpleForm(function(Player $event, ?string $data) {
            if($data !== null) $this->statsUI($event);
        });
        $form->setTitle(TextFormat::YELLOW . "§l§8YOUR STATISTICS");
        $form->addButton(TextFormat::YELLOW . "§8Total Statistics: " . TextFormat::RED . $globalElo . TextFormat::EOL . TextFormat::YELLOW . "§8Wins: " . $globalWins . TextFormat::BLACK . " §8| " . TextFormat::YELLOW . "§8Losses: " . $globalLoses, -1, "", $kit);
        foreach($playerElo as $kit => $points) {
            $form->addButton($kit . ": " . $points . TextFormat::EOL . TextFormat::YELLOW . "§8Wins: " . $playersInfo[$playerName][$kit]["wins"] . TextFormat::BLACK . " §8| " . TextFormat::YELLOW . "§8Losses: " . $playersInfo[$playerName][$kit]["loses"], 0, $kits[$kit]["image-path"], $kit);
        }
        $form->sendToPlayer($player);
    }

    public function eloUI(Player $player): void {
        $playerName = $player->getName();
        $kits = $this->getPlugin()->getKits()->getAll();
        $form = $this->formAPI->createSimpleForm(function(Player $event, ?string $data) {
            if($data === null) return;
            if($data === "global") {
                $this->topEloGlobalUI($event);
            }else{
                $this->topEloUI($event, $data);
            }
        });
        $form->setTitle(TextFormat::YELLOW . "§l§8ELO LEADERBOARDS");
        $form->addButton(TextFormat::YELLOW . "§8Global Leaderboards" . TextFormat::EOL . TextFormat::BLUE . "§8Click to view.", -1, "", "global");
        foreach($kits as $kitName => $kitData) {
            $form->addButton($kitName . TextFormat::EOL . TextFormat::BLUE . "§8Click to view.", 0, $kits[$kitName]["image-path"], $kitName);
        }
        $form->sendToPlayer($player);
    }

    public function topEloGlobalUI(Player $player): void {
        $top = [];
        $text = "";
        $number = 0;
        $form = $this->formAPI->createSimpleForm(function(Player $event, ?int $data) {
            if($data !== null) $this->eloUI($event);
        });
        foreach($this->getPlugin()->getPlayers()->getAll() as $name => $kits) {
            $top[$name] = $this->getPlugin()->getGlobalElo($name);
        }
        if(is_array($top)) arsort($top);
        foreach(array_slice($top, 0, 10) as $name => $points) {
            $number++;
            $text = "§a" . $number . ") §7" . $name . " §a[" . $points . "]" . TextFormat::EOL;
        }
        $form->setTitle(TextFormat::YELLOW . "§l§8GLOBAL ELO LEADERBOARDS");
        $form->setContent($text);
        $form->addButton(TextFormat::BOLD . TextFormat::RED . "§8Go Back", 0, $this->getPlugin()->getConfig()->get("back-button-image"));
        $form->sendToPlayer($player);
    }

    public function topEloUI(Player $player, string $kit): void {
        $top = [];
        $text = "";
        $number = 0;
        $form = $this->formAPI->createSimpleForm(function(Player $event, ?int $data) {
            if($data !== null) $this->eloUI($event);
        });
        foreach($this->getPlugin()->getPlayers()->getAll() as $name => $kits) {
            $top[$name] = $kits[$kit];
        }
        if(is_array($top)) arsort($top);
        foreach(array_slice($top, 0, 10) as $name => $points) {
            $number++;
            $text = "§a" . $number . ") §7" . $name . " §a[" . $points . "]" . TextFormat::EOL;
        }
        $form->setTitle("§l§8" . $kit . " ELO LEADERBOARDS");
        $form->setContent($text);
        $form->addButton(TextFormat::BOLD . TextFormat::RED . "§8Go Back", 0, $this->getPlugin()->getConfig()->get("back-button-image"));
        $form->sendToPlayer($player);
    }

    public function duelHistoryUI(Player $player): void {
        $kits = $this->getPlugin()->getKits()->getAll();
        $duels = $this->getPlugin()->getPlayerDuels()->get($player->getName());
        if(!is_array($duels)) {
            $player->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "No duels history to display.");
            return;
        }
        $form = $this->formAPI->createSimpleForm(function(Player $event, ?string $data) {
            if($data !== null) $this->duelHistoryInfoUI($event, $data);
        });
        $form->setTitle(TextFormat::YELLOW . "§l§8DUEL HISTORY");
        $form->setContent(TextFormat::BLUE . "§rSelect a prior match:");
        foreach($duels as $sfidante => $info) {
            $form->addButton("§8" . $sfidante . TextFormat::EOL . "§8" . $info["date"], 0, $kits[$info["kit"]]["image-path"], $sfidante);
        }
        $form->sendToPlayer($player);
    }

    public function duelHistoryInfoUI(Player $player, string $sfidante): void {
        $this->sfidante[$player->getName()] = $sfidante;
        $form = $this->formAPI->createSimpleForm(function(Player $event, ?string $data) {
            if($data === null) return true;
            switch($data) {
                case "my-stats":
                    //$this->playerInventoryGUI($event, $this->sfidante[$event->getName()]);
                    break;
                case "his-stats":
                    //$this->sfidanteInventoryGUI($event, $this->sfidante[$event->getName()]);
                    break;
                case "general-stats":
                    $this->duelInfoUI($event, $this->sfidante[$event->getName()]);
                    break;
                case "back":
                    $this->duelHistoryUI($event);
                    break;
            }
        });
        $form->setTitle(TextFormat::YELLOW . "§l§8DUEL VS. " . $sfidante);
        $form->setContent(TextFormat::BLUE . "§rExplore match stats below:");
        $form->addButton(TextFormat::RED . "§8Your Inventory\nComing soon!", 0, "textures/ui/absorption_effect", "my-stats");
        $form->addButton(TextFormat::RED . "§8" . $sfidante . TextFormat::RED . "§8's §8Inventory\nComing soon!", 0, "textures/ui/armor_full", "his-stats");
        $form->addButton(TextFormat::RED . "§8Match Statistics\nClick to view.", 0, "textures/items/paper", "general-stats");
        $form->addButton(TextFormat::BOLD . TextFormat::RED . "§r§8Go back", 0, $this->getPlugin()->getConfig()->get("back-button-image"), "back");
        $form->sendToPlayer($player);
    }

    public function duelInfoUI(Player $player, string $sfidante): void {
        unset($this->sfidante[$player->getName()]);
        $informations = [];
        $name = $player->getName();
        $info = $this->getPlugin()->getPlayerDuels()->getNested("$name.$sfidante");
        $form = $this->formAPI->createSimpleForm(function(Player $event, ?string $data) {
            if($data !== null) $this->duelHistoryInfoUI($event, $data);
        });
        foreach($info["my-stats"] as $key => $value) {
            if(!is_array($value)) $informations["my-stats"][]= TextFormat::AQUA . "§rYour " . $key . ": §a" . $value;
        }
        foreach($info["his-stats"] as $key => $value) {
            if(!is_array($value)) $informations["his-stats"][] = TextFormat::AQUA . "§rTheir " . $key . ": §a" . $value;
        }
        $form->setTitle(TextFormat::YELLOW . "§l§8DUEL VS. " . $sfidante);
        $form->setContent(TextFormat::AQUA . "§rWinner: §a" . $info["winner"] . TextFormat::EOL . TextFormat::EOL . implode(TextFormat::EOL, $informations["my-stats"]) . TextFormat::EOL . TextFormat::EOL . implode(TextFormat::EOL, $informations["his-stats"]) . "\n ");
        $form->addButton(TextFormat::BOLD . TextFormat::RED . "§8Go back", 0, $this->getPlugin()->getConfig()->get("back-button-image"), $sfidante);
        $form->sendToPlayer($player);
    }

    public function playerInventoryGUI(Player $player, string $sfidante): void {
        $number = 45;
        $name = $player->getName();
        $info = $this->getPlugin()->getPlayerDuels()->getNested("$name.$sfidante.my-stats");
        $specialItems = [
            (ItemFactory::get(Item::SKULL)->setCustomName(TextFormat::YELLOW . "Opponent: " . $sfidante)),
            (ItemFactory::get(Item::MELON)->setCustomName(TextFormat::YELLOW . "Life: " . $info["HP"])),
            (ItemFactory::get(Item::STEAK)->setCustomName(TextFormat::YELLOW . "Food: " . $info["hunger points"])),
            (ItemFactory::get(Item::EMERALD)->setCustomName(TextFormat::YELLOW . "Ping: " . $info["ping"])),
        ];
        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->readonly(true);
        $menu->setName(TextFormat::RED . "Your inventory");
        $menu->setInventoryCloseListener(function(Player $player): void {
            unset($this->sfidante[$player->getName()]);
        });
        foreach($info["armor"] as $armor) {
            $menu->getInventory()->addItem(ItemFactory::get((int)$armor));
        }
        foreach($info["items"] as $items) {
            $item = explode(":", $items);
            $menu->getInventory()->addItem(ItemFactory::get((int)$item[0], (int)$item[1], (int)$item[2]));
        }
        foreach($specialItems as $specialItem) {
            $menu->getInventory()->setItem($number, $specialItem);
            $number++;
        }
        $menu->send($player);
    }

    public function sfidanteInventoryGUI(Player $player, string $sfidante): void {
        $number = 45;
        $name = $player->getName();
        $info = $this->getPlugin()->getPlayerDuels()->getNested("$name.$sfidante.his-stats");
        $specialItems = [
            (ItemFactory::get(Item::SKULL)->setCustomName(TextFormat::YELLOW . "Opponent: " . $name)),
            (ItemFactory::get(Item::GLISTERING_MELON)->setCustomName(TextFormat::YELLOW . "Life: " . $info["HP"])),
            (ItemFactory::get(Item::STEAK)->setCustomName(TextFormat::YELLOW . "Food: " . $info["hunger points"])),
            (ItemFactory::get(Item::EMERALD)->setCustomName(TextFormat::YELLOW . "Ping: " . $info["ping"])),
        ];
        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST)
        ->readonly()
        ->setName(TextFormat::RED . $sfidante . " inventory")
        ->setInventoryCloseListener(function(Player $player): void {
            unset($this->sfidante[$player->getName()]);
        });
        foreach($info["armor"] as $armor) {
            $menu->getInventory()->addItem(ItemFactory::get((int)$armor));
        }
        foreach($info["items"] as $items) {
            $item = explode(":", $items);
            $menu->getInventory()->addItem(ItemFactory::get((int)$item[0], (int)$item[1], (int)$item[2]));
        }
        foreach($specialItems as $specialItem) {
            $menu->getInventory()->setItem($number, $specialItem);
            $number++;
        }
        $menu->send($player);
        unset($this->sfidante[$player->getName()]);
    }

    public function ffaForm(Player $player) {
        $form = new SimpleForm (function (Player $event, $data) {
            $player = $event->getPlayer();

            if ($data === null) {
                return;
            }
            switch ($data) {
                case 0:
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->removeAllEffects();
                    $player->setGamemode(2);
                    $this->getPlugin()->getServer()->dispatchCommand($player, "practice ffa NoDebuff");
                    break;
                case 1;
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->removeAllEffects();
                    $player->setGamemode(2);
                    $this->getPlugin()->getServer()->dispatchCommand($player, "practice ffa Fist");
                    break;
                case 2;
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->removeAllEffects();
                    $player->setGamemode(2);
                    $this->getPlugin()->getServer()->dispatchCommand($player, "practice ffa Sumo");
                    break;
                case 3;
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->removeAllEffects();
                    $player->setGamemode(2);
                    $this->getPlugin()->getServer()->dispatchCommand($player, "practice ffa Gapple");
                    break;
                case 4;
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->removeAllEffects();
                    $player->setGamemode(2);
                    $this->getPlugin()->getServer()->dispatchCommand($player, "practice ffa Combo");
                    break;
                case 5:
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->removeAllEffects();
                    $player->setGamemode(2);
                    $this->getPlugin()->getServer()->dispatchCommand($player, "practice ffa Resistance");
                    break;
                case 6:
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->removeAllEffects();
                    $player->setGamemode(2);
                    $this->getPlugin()->getServer()->dispatchCommand($player, "practice ffa Scrims");
                    break;
            }
        });
        $ndffa = $this->getPlugin()->getServer()->getLevelByName("NoDebuff-FFA");
        $ndffacount = count($ndffa->getPlayers());
        $cmbffa = $this->getPlugin()->getServer()->getLevelByName("Combo-FFA");
        $cmbffacount = count($cmbffa->getPlayers());
        $fistffa = $this->getPlugin()->getServer()->getLevelByName("Fist-FFA");
        $fistffacount = count($fistffa->getPlayers());
        $gappleffa = $this->getPlugin()->getServer()->getLevelByName("Gapple-FFA");
        $gappleffacount = count($gappleffa->getPlayers());
        $scrims = $this->getPlugin()->getServer()->getLevelByName("Scrims-1");
        $scrimscount = count($scrims->getPlayers());
        $sumoffa = $this->getPlugin()->getServer()->getLevelByName("Sumo-FFA");
        $sumoffacount = count($sumoffa->getPlayers());
        $resffa = $this->getPlugin()->getServer()->getLevelByName("Resistance-FFA");
        $rescount = count($resffa->getPlayers());
        $form->setTitle("§l§8FFA ARENAS");
        $form->setContent("Select an arena:");
        $form->addButton("§8NoDebuff\n§r§8Playing: " . $ndffacount . "", 0, "textures/items/potion_bottle_splash_heal");
        $form->addButton("§8Fist\n§r§8Playing: " . $fistffacount . "", 0, "textures/items/beef_cooked");
        $form->addButton("§8Sumo\n§r§8Playing: " . $sumoffacount . "", 0, "textures/items/totem.png");
        $form->addButton("§8Gapple\n§r§8Playing: " . $gappleffacount . "", 0, "textures/items/apple_golden");
        $form->addButton("§8Combo\n§r§8Playing: " . $cmbffacount . "", 0, "textures/items/fish_pufferfish_raw");
        $form->addButton("§8Resistance\n§r§8Playing: " . $rescount . "", 0, "textures/items/suspicious_stew");
        $form->addButton("§8Scrims\n§r§8Playing: " . $scrimscount . "", 0, "textures/items/villagebell");
        $player->sendForm($form);
        return $form;
    }

    public function spectateForm(Player $player) {
        $form = new SimpleForm (function (Player $event, $data) {
            $player = $event->getPlayer();

            if ($data === null) {
                return;
            }
            switch ($data) {
                case 0:
                    $player->getInventory()->clearAll();
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->teleport($this->getPlugin()->getServer()->getLevelByName("NoDebuff-FFA")->getSafeSpawn());
                    $hubitem = Item::get(345, 0, 1);
                    $hubitem->setCustomName("§r§l§cReturn to lobby");
                    $player->getInventory()->setItem(4, $hubitem);
                    $player->setGamemode(3);
                    break;
                case 1;
                    $player->getInventory()->clearAll();
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->teleport($this->getPlugin()->getServer()->getLevelByName("Fist-FFA")->getSafeSpawn());
                    $hubitem = Item::get(345, 0, 1);
                    $hubitem->setCustomName("§r§l§cReturn to lobby");
                    $player->getInventory()->setItem(4, $hubitem);
                    $player->setGamemode(3);
                    break;
                case 2;
                    $player->getInventory()->clearAll();
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->teleport($this->getPlugin()->getServer()->getLevelByName("Sumo-FFA")->getSafeSpawn());
                    $hubitem = Item::get(345, 0, 1);
                    $hubitem->setCustomName("§r§l§cReturn to lobby");
                    $player->getInventory()->setItem(4, $hubitem);
                    $player->setGamemode(3);
                    break;
                case 3:
                    $player->getInventory()->clearAll();
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->teleport($this->getPlugin()->getServer()->getLevelByName("Gapple-FFA")->getSafeSpawn());
                    $hubitem = Item::get(345, 0, 1);
                    $hubitem->setCustomName("§r§l§cReturn to lobby");
                    $player->getInventory()->setItem(4, $hubitem);
                    $player->setGamemode(3);
                    break;
                case 4:
                    $player->getInventory()->clearAll();
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->teleport($this->getPlugin()->getServer()->getLevelByName("Combo-FFA")->getSafeSpawn());
                    $hubitem = Item::get(345, 0, 1);
                    $hubitem->setCustomName("§r§l§cReturn to lobby");
                    $player->getInventory()->setItem(4, $hubitem);
                    $player->setGamemode(3);
                    break;
                case 5:
                    $player->getInventory()->clearAll();
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->teleport($this->getPlugin()->getServer()->getLevelByName("Resistance-FFA")->getSafeSpawn());
                    $hubitem = Item::get(345, 0, 1);
                    $hubitem->setCustomName("§r§l§cReturn to lobby");
                    $player->getInventory()->setItem(4, $hubitem);
                    $player->setGamemode(3);
                    break;
                case 6:
                    $player->getInventory()->clearAll();
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->teleport($this->getPlugin()->getServer()->getLevelByName("Scrims-1")->getSafeSpawn());
                    $hubitem = Item::get(345, 0, 1);
                    $hubitem->setCustomName("§r§l§cReturn to lobby");
                    $player->getInventory()->setItem(4, $hubitem);
                    $player->setGamemode(3);
                    break;
            }
        });
        $ndffa = $this->getPlugin()->getServer()->getLevelByName("NoDebuff-FFA");
        $ndffacount = count($ndffa->getPlayers());
        $cmbffa = $this->getPlugin()->getServer()->getLevelByName("Combo-FFA");
        $cmbffacount = count($cmbffa->getPlayers());
        $fistffa = $this->getPlugin()->getServer()->getLevelByName("Fist-FFA");
        $fistffacount = count($fistffa->getPlayers());
        $gappleffa = $this->getPlugin()->getServer()->getLevelByName("Gapple-FFA");
        $gappleffacount = count($gappleffa->getPlayers());
        $scrims = $this->getPlugin()->getServer()->getLevelByName("Scrims-1");
        $scrimscount = count($scrims->getPlayers());
        $sumoffa = $this->getPlugin()->getServer()->getLevelByName("Sumo-FFA");
        $sumoffacount = count($sumoffa->getPlayers());
        $resffa = $this->getPlugin()->getServer()->getLevelByName("Resistance-FFA");
        $rescount = count($resffa->getPlayers());
        $form->setTitle("§l§8SPECTATE MENU");
        $form->setContent("Select an arena:");
        $form->addButton("§8NoDebuff\n§r§8Playing: " . $ndffacount . "", 0, "textures/items/potion_bottle_splash_heal");
        $form->addButton("§8Fist\n§r§8Playing: " . $fistffacount . "", 0, "textures/items/beef_cooked");
        $form->addButton("§8Sumo\n§r§8Playing: " . $sumoffacount . "", 0, "textures/items/totem.png");
        $form->addButton("§8Gapple\n§r§8Playing: " . $gappleffacount . "", 0, "textures/items/apple_golden");
        $form->addButton("§8Combo\n§r§8Playing: " . $cmbffacount . "", 0, "textures/items/fish_pufferfish_raw");
        $form->addButton("§8Resistance\n§r§8Playing: " . $rescount . "", 0, "textures/items/suspicious_stew");
        $form->addButton("§8Scrims\n§r§8Playing: " . $scrimscount . "", 0, "textures/items/villagebell");
        $player->sendForm($form);
        return $form;
    }

    public function statsForm(Player $player) {
        $form = new SimpleForm (function (Player $event, $data) {
            $player = $event->getPlayer();

            if ($data === null) {
                return;
            }
            switch ($data) {
                case 0:
                    $this->getPlugin()->getServer()->dispatchCommand($player, "stats");
                    break;
                case 1;
                    $this->getPlugin()->getServer()->dispatchCommand($player, "history");
                    break;
                case 2;
                    $this->getPlugin()->getServer()->dispatchCommand($player, "leaderboard");
                    break;
            }
        });

        $form->setTitle("§l§8STATICTICS FORM");
        $form->setContent("View your statistics:");
        $form->addButton("§8Duels Statistics\n§r§8Click to view.");
        $form->addButton("§8Duels History\n§r§8Click to view.");
        $form->addButton("§8Elo Leaderboards\n§r§8Click to view.");
        $player->sendForm($form);
        return $form;
    }

    public function modsForm(Player $player) {
        $form = new SimpleForm (function (Player $event, $data) {
            $player = $event->getPlayer();

            if ($data === null) {
                return;
            }
            switch ($data) {
                case 0:
                    $this->getPlugin()->getServer()->dispatchCommand($player, "stats");
                    break;
                case 1;
                    $this->getPlugin()->getServer()->dispatchCommand($player, "history");
                    break;
                case 2;
                    $this->getPlugin()->getServer()->dispatchCommand($player, "leaderboard");
                    break;
            }
        });

        $form->setTitle("§l§8MODS FORM");
        $form->setContent("Enable mods below:");
        $form->addButton("§8Duels Statistics\n§r§8Click to view.");
        $form->addButton("§8Duels History\n§r§8Click to view.");
        $form->addButton("§8Elo Leaderboards\n§r§8Click to view.");
        $player->sendForm($form);
        return $form;
    }

    public function teleportForm(Player $player)
    {
        $plist = [];
        foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $p) {
            $plist[] = $p->getName();
        }
        $this->playerlist[$player->getName()] = $plist;
        $form = new CustomForm (function (Player $player, array $data = null) {
            if ($data === null) {
                return;
            }
            $index = $data[0];
            $playerName = $this->playerlist[$player->getName()] [$index];
            $playerTarget = $this->getPlugin()->getServer()->getPlayer($playerName);
            $player->teleport($playerTarget->getLocation());

        });
        $form->setTitle("§8§lTELEPORT MENU");
        $form->addDropdown("Select a player you'd like to teleport to. \n\nNOTE: Those who abuse this form and/or its priviledges will be punished!", $this->playerlist[$player->getName()]);
        $player->sendForm($form);
    }

    public function freezeForm(Player $player)
    {
        $plist = [];
        foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $p) {
            $plist[] = $p->getName();
        }
        $this->playerlist[$player->getName()] = $plist;
        $form = new CustomForm (function (Player $player, array $data = null) {
            if ($data === null) {
                return;
            }
            $index = $data[0];
            $playerName = $this->playerlist[$player->getName()] [$index];
            $this->getPlugin()->getServer()->dispatchCommand($player, 'freeze "' . $playerName . '"');
        });
        $form->setTitle("§8§lFREEZE/UNFREEZE FORM");
        $form->addDropdown("Select a player you'd like to freeze/unfreeze. \n\nNOTE: Those who abuse this form and/or its priviledges will be punished!", $this->playerlist[$player->getName()]);
        $player->sendForm($form);
    }

    public function statusForm(Player $player) {
        $form = new SimpleForm (function (Player $event, $data) {
            $player = $event->getPlayer();

            if ($data === null) {
                return;
            }

            switch ($data) {
                case 0:
                    $this->adminForm($player);
                    break;
            }
        });

        $server = $player->getServer();

        $time = microtime(true) - \pocketmine\START_TIME;

        $seconds = floor($time % 60);
        $minutes = null;
        $hours = null;
        $days = null;

        if($time >= 60){
            $minutes = floor(($time % 3600) / 60);
            if($time >= 3600){
                $hours = floor(($time % (3600 * 24)) / 3600);
                if($time >= 3600 * 24){
                    $days = floor($time / (3600 * 24));
                }
            }
        }

        $uptime = ($minutes !== null ?
                ($hours !== null ?
                    ($days !== null ?
                        "$days days "
                        : "") . "$hours hours "
                    : "") . "$minutes minutes "
                : "") . "$seconds seconds";

        $currenttps = $server->getTicksPerSecond();
        $averagetps = $server->getTicksPerSecondAverage();
        $cpu = $server->getTickUsage();
        $upload = round($server->getNetwork()->getUpload() / 1024, 2) . " KB/s";
        $download = round($server->getNetwork()->getDownload() / 1024, 2) . " KB/s";
        $threadcount = Utils::getThreadCount();
        $mUsage = Utils::getMemoryUsage(true);
        $mainthreadmem = number_format(round(($mUsage[0] / 1024) / 1024, 2)) . " MB";
        $maxmem = number_format(round(($mUsage[2] / 1024) / 1024, 2)) . " MB";

        $form->setTitle("§l§8SERVER STATUS");
        $form->setContent("§aUptime: §f" . $uptime . "\n§aCurrent TPS: §f" . $currenttps . "\n§aAverage TPS: §f" . $averagetps .  "\n§aCPU Usage: §f" . $cpu . "%\n§aUpload: §f" . $upload . "\n§aDownload: §f" . $download . "\n§aThreads: §f" . $threadcount . "\n§aMain Thread Memory: §f" . $mainthreadmem . "\n§aMax Memory: §f" . $maxmem . "\n ");
        $form->addButton("§7Go back");
        $player->sendForm($form);
        return $form;
    }


    public function adminForm(Player $player) {
        $form = new SimpleForm (function (Player $event, $data) {
            $player = $event->getPlayer();

            if ($data === null) {
                return;
            }
            switch ($data) {
                case 0:
                    $this->statusForm($player);
                    break;
                case 1;
                    $this->getPlugin()->getServer()->dispatchCommand($player, "stop");
                    break;
                case 2;
                    $this->gamemodeForm($player);
                    break;
            }
        });

        $form->setTitle("§l§8ADMIN FORM");
        $form->setContent("");
        $form->addButton("§l§8VIEW SERVER STATUS");
        $form->addButton("§l§8STOP THE SERVER");
        $form->addButton("§l§8CHANGE GAMEMODE");
        $player->sendForm($form);
        return $form;
    }

    public function gamemodeForm(Player $player) {
        $form = new SimpleForm (function (Player $event, $data) {
            $player = $event->getPlayer();

            if ($data === null) {
                return;
            }
            switch ($data) {
                case 0:
                    $player->removeAllEffects();
                    $player->setGamemode(1);
                    break;
                case 1;
                    $player->removeAllEffects();
                    $player->setGamemode(0);
                    break;
                case 2;
                    $player->removeAllEffects();
                    $player->setGamemode(2);
                    break;
                case 3:
                    $player->removeAllEffects();
                    $player->setGamemode(3);
                    break;
            }
        });

        $form->setTitle("§l§8GAMEMODE UI");
        $form->setContent("");
        $form->addButton("§l§8CREATIVE");
        $form->addButton("§l§8SURVIVAL");
        $form->addButton("§l§8ADVENTURE");
        $form->addButton("§l§8SPECTATOR");
        $player->sendForm($form);
        return $form;
    }

    public function getPlugin(): Main {
        return $this->plugin;
    }
}