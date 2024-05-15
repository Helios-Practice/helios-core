<?php

declare(strict_types=1);

namespace HeliosTeam\Practice;

use HeliosTeam\Practice\Managers\HeliosManager;
use HeliosTeam\Practice\Other\Scoreboard;
use HeliosTeam\Practice\Utils\Arena;
use HeliosTeam\Practice\Utils\ScoreboardAPI;
use HeliosTeam\Practice\SumoEvent;
use pocketmine\command\Command;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\item\ItemFactory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use HeliosTeam\Practice\Other\UI;
use HeliosTeam\Practice\Tasks\TimerTask;
use HeliosTeam\Practice\Tasks\BroadcastTask;
use Libs\Webhooks\Webhook;
use Libs\Webhooks\Message;
use Libs\Webhooks\Embed;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;

use function array_search;
use function array_shift;
use function explode;
use function file;
use function file_exists;
use function file_put_contents;
use function implode;
use function str_replace;
use function strtolower;
use function trim;
use const FILE_IGNORE_NEW_LINES;
use const FILE_SKIP_EMPTY_LINES;

class Main extends PluginBase {

    private $config;
    private $arenasCfg;
    private $arenas = [];
    private $listener;
    public $floatingTexts = [];

    const SWISH_SOUNDS = [
        LevelSoundEventPacket::SOUND_ATTACK,
        LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE,
        LevelSoundEventPacket::SOUND_ATTACK_STRONG
    ];

    private $clicks = [];
    private $deviceInput = [];
    private $actions = [];
    public $score = null;
    private $FFAarenas = [];
    private $ffaarenasCfg;

    # Gets the main class instance.

    private static $instance;
    public $activeffacfg;
    public $pureChat;
    private $purePerms;
    public $scoreboardutil;
    private $ui;

    private $activeduelscfg;
    private $playerDuels;
    private $placenbreakCfg;
    private $playersInfo;
    private $players;
    private $ffakits;
    private $kits;
    public $preciseCpsCounter;
    public $sumoevent;

    private $bans = [];

    public function onEnable(): void {

        new HeliosManager($this);

        $this->getServer()->getPluginManager()->registerEvents($this->listener = new PlayerListener($this), $this);

        //$this->sumoevent = new SumoEvent($this);
        $this->ui = new UI($this);
        $this->score = new ScoreboardAPI();
        $this->scoreboardutil = new Scoreboard($this);

        if(!is_dir($this->getDataFolder() . "bans/")){
            @mkdir($this->getDataFolder() . "bans/", 0777, true);
        }

        $this->reloadConfig();
        if(file_exists($this->getDataFolder().'bans/clientbans.txt')){
            $file = file($this->getDataFolder().'bans/clientbans.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach($file as $line){
                $array = explode('|', trim($line));
                $this->bans[$array[0]] = $array[1];
            }
        }

        $this->saveDefaultConfig();
        $this->loadAllConfig();
        $this->getScheduler()->scheduleRepeatingTask(new BroadcastTask($this), 20);
        $this->pureChat = $this->getServer()->getPluginManager()->getPlugin("PureChat");
        $this->purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
        $preciseCpsCounter = $this->getServer()->getPluginManager()->getPlugin("PreciseCpsCounter");
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("me"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("version"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("plugins"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("timings"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("event"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("accept"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("usrinfo"));

        foreach ($this->getArenasConfig()->getAll() as $name => $arena) {
            $this->getServer()->loadLevel($arena["world"]);
            $this->addArena($name, $arena["world"], $arena["spawns"], $arena["kit"], $arena["type"]);
        }

        $this->getScheduler()->scheduleRepeatingTask(new TimerTask($this), 20);

        $webHook = new Webhook("https://discordapp.com/api/webhooks/791753392871047208/tzT33qj3aw3wEjbX6_N_Cr9q7zMKgpQO3kEqCY7spPbyefw9wVZVRUl2nQPXDMZkURXO");
        $msg = new Message();
        $embed = new Embed();
        $embed->setTitle("Online");
        $embed->setColor(0x4CDD60);
        $embed->setDescription("Helios Practice is now **online**!");
        $embed->setFooter("Helios Practice");
        $msg->addEmbed($embed);
        $webHook->send($msg);
    }

    public function onLoad() : void {
        self::$instance = $this;
        foreach ($this->getServer()->getLevels() as $level) {
            $this->getServer()->loadLevel($level->getFolderName());
            $level->setTime(7000);
            $level->stopTime();
        }
    }

    public static function getInstance(): Main {
        return self::$instance;
    }

    public function onDisable() {
        $webHook = new Webhook("https://discordapp.com/api/webhooks/791753392871047208/tzT33qj3aw3wEjbX6_N_Cr9q7zMKgpQO3kEqCY7spPbyefw9wVZVRUl2nQPXDMZkURXO");
        $msg = new Message();
        $embed = new Embed();
        $embed->setTitle("Offline");
        $embed->setColor(0xDD4C4C);
        $embed->setDescription("Helios Practice is now **offline**!");
        $embed->setFooter("Helios Practice");
        $msg->addEmbed($embed);
        $webHook->send($msg);
        $this->saveData();
        foreach ($this->getArenas() as $arena) {
            $arenaname = $arena->getName();
            $this->getActiveDuels()->remove($arenaname);
            $this->getActiveDuels()->save();
            $arena->stop("");
        }
    }

    private function saveData() {
        $string = '';
        foreach($this->bans as $client => $name){
            $string .= $client.'|'.$name."\n";
        }
        file_put_contents($this->getDataFolder().'bans/clientbans.txt', $string);
    }

    public function isClientIdBanned(string $cid) : bool{
        return isset($this->bans[$cid]);
    }

    public function isPlayerBanned(Player $player) : bool{
        return $this->isClientIdBanned((string)$player->getClientId());
    }

    public function banClient(Player $player, string $reason = '', bool $kick = true, bool $save = true){
        $this->bans[$player->getClientId()] = strtolower($player->getName());
        if($kick === true){
            $player->kick("§dYou have been permanently banned from Helios Practice!\n§dBanned by: §bCONSOLE\n§dReason: §bunfair advantage\n§dDiscord Server: §bhttp://bit.ly/heliospractice", false);
        }
        if($save === true){
            $this->saveData();
        }
    }

    public function onPreLogin(PlayerPreLoginEvent $event) : void {
        if ($this->isPlayerBanned($event->getPlayer())) {
            $event->getPlayer()->kick("§dYou have been permanently banned from Helios Practice!\n§dBanned by: §bCONSOLE\n§dReason: §bunfair advantage\n§dDiscord Server: §bhttp://bit.ly/heliospractice", false);
        }
    }

    public function pardonClient(string $name, bool $save = true) : bool{
        if(($key = array_search(strtolower($name), $this->bans, true)) !== false){
            unset($this->bans[$key]);
            if($save === true){
                $this->saveData();
            }
            return true;
        }
        return false;
    }

    public function loadAllConfig(): void {
        $this->kits = new Config($this->getDataFolder() . "kits.yml", Config::YAML);
        $this->ffakits = new Config($this->getDataFolder() . "ffakits.yml", Config::YAML);
        $this->players = new Config($this->getDataFolder() . "players.yml", Config::YAML);
        $this->playersInfo = new Config($this->getDataFolder() . "players-info.yml", Config::YAML);
        $this->playerDuels = new Config($this->getDataFolder() . "player-duels.yml", Config::YAML);
        $this->arenasCfg = new Config($this->getDataFolder() . "arenas.yml");
        $this->ffaarenasCfg = new Config($this->getDataFolder() . "ffaarenas.yml", Config::YAML);
        $this->activeffacfg = new Config($this->getDataFolder() . "activeffaplayer.yml", Config::YAML);
        $this->activeduelscfg = new Config($this->getDataFolder() . "activeduels.yml", Config::YAML);
    }

    public function addKit(Player $player, string $kit): void {
        $kits = $this->getKits()->getAll();
        $player->getInventory()->clearAll();
        $player->removeAllEffects();
        $player->getArmorInventory()->clearAll();
        $player->setHealth(20);
        $player->setFood(20);
        foreach ($kits[$kit]["effects"] as $effects) {
            $effectDel = explode(":", $effects);
            $effect = new EffectInstance(Effect::getEffect((int)$effectDel[0]), (int)$effectDel[1], (int)$effectDel[2], false);
            $player->addEffect($effect);
        }

        foreach ($kits[$kit]["commands"] as $cmd) {
            $this->getServer()->dispatchCommand(new ConsoleCommandSender, str_replace("{player}", $player->getName(), $cmd));
        }

        foreach ($kits[$kit]["items"] as $items) {
            $item = explode(":", $items);
            $itemAdd = ItemFactory::get((int)$item[0], (int)$item[1], (int)$item[2]);
            if (isset($item[3])) {
                $itemAdd->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($item[3]), (int)$item[4]));
            }
            $player->getInventory()->addItem($itemAdd);
        }

        if (is_array(explode(":", (string)$kits[$kit]["helmet"]))) {
            $armor = explode(":", (string)$kits[$kit]["helmet"]);
            $helmet = ItemFactory::get((int)$armor[0]);
            if (isset($armor[1])) {
                $helmet->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        } else {
            $armor = $kits[$kit]["helmet"];
            $helmet = ItemFactory::get((int)$armor);
        }

        $player->getArmorInventory()->setHelmet($helmet);

        if (is_array(explode(":", (string)$kits[$kit]["chestplate"]))) {
            $armor = explode(":", (string)$kits[$kit]["chestplate"]);
            $chestplate = ItemFactory::get((int)$armor[0]);
            if (isset($armor[1])) {
                $chestplate->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        } else {
            $armor = $kits[$kit]["chestplate"];
            $chestplate = ItemFactory::get((int)$armor);
        }

        $player->getArmorInventory()->setChestplate($chestplate);
        if (is_array(explode(":", (string)$kits[$kit]["leggings"]))) {
            $armor = explode(":", (string)$kits[$kit]["leggings"]);
            $leggings = ItemFactory::get((int)$armor[0]);
            if (isset($armor[1])) {
                $leggings->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        } else {
            $armor = $kits[$kit]["leggings"];
            $leggings = ItemFactory::get((int)$armor);
        }

        $player->getArmorInventory()->setLeggings($leggings);
        if (is_array(explode(":", (string)$kits[$kit]["boots"]))) {
            $armor = explode(":", (string)$kits[$kit]["boots"]);
            $boots = ItemFactory::get((int)$armor[0]);
            if (isset($armor[1])) {
                $boots->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        } else {
            $armor = $kits[$kit]["boots"];
            $boots = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setBoots($boots); // test
    }

    public function sendMenu(Player $player): void {
        $player->removeAllEffects();
        $player->setFlying(false);
        $player->setAllowFlight(false);
        $player->setHealth(20);
        $player->setFood(20);
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->clearAll();
        $player->setGamemode(2);
        $player->setFood(20);
        $player->setXpProgress(0);
        $player->setXpLevel(0);

        $specitem = Item::get(268, 0, 1);
        $specitem->setCustomName("§r§l§aSpectate");
        $player->getInventory()->setItem(2, $specitem);

        $eventitem = Item::get(450, 0, 1);
        $eventitem->setCustomName("§r§l§aJoin Sumo Event");
        $player->getInventory()->setItem(5, $eventitem);

        $config = $this->getConfig();

        $unrankedItem = explode(":", $this->getConfig()->get("unranked-item"));
        $ffaItem = explode(":", $this->getConfig()->get("ffa-item"));
        $cosmeticsItem = explode(":", $this->getConfig()->get("cosmetics-item"));
        $statslbItem = explode(":", $this->getConfig()->get("statslb-item"));
        $modsItem = explode(":", $this->getConfig()->get("mods-item"));

        $player->getInventory()->setItem((int)$unrankedItem[0], (ItemFactory::get((int)$unrankedItem[1])->setCustomName($unrankedItem[2])));
        $player->getInventory()->setItem((int)$ffaItem[0], (ItemFactory::get((int)$ffaItem[1])->setCustomName($ffaItem[2])));
        $player->getInventory()->setItem((int)$cosmeticsItem[0], (ItemFactory::get((int)$cosmeticsItem[1])->setCustomName($cosmeticsItem[2])));
        $player->getInventory()->setItem((int)$statslbItem[0], (ItemFactory::get((int)$statslbItem[1])->setCustomName($statslbItem[2])));
        $player->getInventory()->setItem((int)$modsItem[0], (ItemFactory::get((int)$modsItem[1])->setCustomName($modsItem[2])));
    }

    public function addFFAKit(Player $player, string $kit): void {
        $kits = $this->getFFAKits()->getAll();
        $player->getInventory()->clearAll();
        $player->removeAllEffects();
        $player->getArmorInventory()->clearAll();
        $player->setHealth(20);
        $player->setFood(20);
        foreach ($kits[$kit]["effects"] as $effects) {
            $effectDel = explode(":", $effects);
            $effect = new EffectInstance(Effect::getEffect((int)$effectDel[0]), (int)$effectDel[1], (int)$effectDel[2], false);
            $player->addEffect($effect);
        }

        foreach ($kits[$kit]["commands"] as $cmd) {
            $this->getServer()->dispatchCommand(new ConsoleCommandSender, str_replace("{player}", $player->getName(), $cmd));
        }

        foreach ($kits[$kit]["items"] as $items) {
            $item = explode(":", $items);
            $itemAdd = ItemFactory::get((int)$item[0], (int)$item[1], (int)$item[2]);
            if (isset($item[3])) {
                $itemAdd->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($item[3]), (int)$item[4]));
            }
            $player->getInventory()->addItem($itemAdd);
        }

        if (is_array(explode(":", (string)$kits[$kit]["helmet"]))) {
            $armor = explode(":", (string)$kits[$kit]["helmet"]);
            $helmet = ItemFactory::get((int)$armor[0]);
            if (isset($armor[1])) {
                $helmet->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        } else {
            $armor = $kits[$kit]["helmet"];
            $helmet = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setHelmet($helmet);

        if (is_array(explode(":", (string)$kits[$kit]["chestplate"]))) {
            $armor = explode(":", (string)$kits[$kit]["chestplate"]);
            $chestplate = ItemFactory::get((int)$armor[0]);
            if (isset($armor[1])) {
                $chestplate->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        } else {
            $armor = $kits[$kit]["chestplate"];
            $chestplate = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setChestplate($chestplate);

        if (is_array(explode(":", (string)$kits[$kit]["leggings"]))) {
            $armor = explode(":", (string)$kits[$kit]["leggings"]);
            $leggings = ItemFactory::get((int)$armor[0]);
            if (isset($armor[1])) {
                $leggings->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        } else {
            $armor = $kits[$kit]["leggings"];
            $leggings = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setLeggings($leggings);
        if (is_array(explode(":", (string)$kits[$kit]["boots"]))) {
            $armor = explode(":", (string)$kits[$kit]["boots"]);
            $boots = ItemFactory::get((int)$armor[0]);
            if (isset($armor[1])) {
                $boots->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        } else {
            $armor = $kits[$kit]["boots"];
            $boots = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setBoots($boots); // test
    }

    public function GiveQueueItem(Player $player): void {
        $player->getInventory()->clearAll();
        $queueItem = explode(":", $this->getConfig()->get("queue-item"));
        $player->getInventory()->setItem((int)$queueItem[0], (ItemFactory::get((int)$queueItem[1])->setCustomName($queueItem[2])));
    }

    public function RemoveQueueItem(Player $player): void {
        $player->getInventory()->clearAll();
        $this->sendMenu($player);
    }

    public function addArena(string $name, string $world, array $spawns, string $kit, string $type): Arena {
        return $this->arenas[$name] = new Arena($this, $name, $world, $spawns, $kit, $type);
    }

    public function addFFAArena(string $name, string $world, string $kit, string $type): FFAArena {
        return $this->FFAarenas[$name] = new FFAArena($this, $name, $world, $kit, $type);
    }

    public function getGlobalElo(string $playerName) {
        $players = $this->getPlayers()->getAll();
        foreach ($this->getKits()->getAll() as $kitName => $kitData) {
            $elo[] = $players[$playerName][$kitName];
            $finalElo = array_sum($elo);
        }
        return $finalElo ?? null;
    }

    public function getRank(string $playerName) {
        $globalElo = $this->getGlobalElo($playerName);
        foreach ($this->getRanks() as $rankName => $rankData) {
            if ($globalElo >= $rankData["elo"]) $finalRank = $rankName;
        }
        return $finalRank ?? null;
    }

    public function getRankPrefix(string $playerName): string {
        return $this->getRanks()[$this->getRank($playerName)]["prefix"];
    }

    public function getMaxRankeds(Player $player): int {
        $group = $this->purePerms->getUserDataMgr()->getGroup($player);
        $finalGroup = (in_array($group, array_keys($this->getGroups()))) ? $group : "default";
        return $this->getGroups()[$finalGroup];
    }

    public function getKits(): Config {
        return $this->kits;
    }

    public function getFFAKits(): Config {
        return $this->ffakits;
    }

    public function getPlayers(): Config {
        return $this->players;
    }

    public function getPlayersInfo(): Config {
        return $this->playersInfo;
    }

    public function getActiveFFA(): Config {
        return $this->activeffacfg;
    }

    public function getFFAArenasConfig(): Config {
        return $this->ffaarenasCfg;
    }

    public function getPlayerDuels(): Config {
        return $this->playerDuels;
    }

    public function getArenasConfig(): Config {
        return $this->arenasCfg;
    }

    public function getArenas(): array {
        return $this->arenas;
    }

    public function getFFAArenas(): array {
        return $this->FFAarenas;
    }

    public function getArenaByPlayer($player) {
        foreach ($this->getArenas() as $arena)
            if ($arena->inArena($player)) {
                return $arena;
            }
        return null;
    }

    public function getFFAArenaByPlayer(Player $player) {
        foreach ($this->getFFAArenas() as $arena)
            if ($arena->inFFAArena($player)) {
                return $arena;
            }
        return null;
    }

    public function getFFAArenaByWorld(string $world) {
        foreach ($this->getFFAArenasConfig()->getAll() as $name => $arena) {
            if ($arena["world"] === $world) {
                return $arena["name"];
            }
        }
        return null;
    }

    public function getArenaByName(string $name) {
        if (isset($this->getArenas()[$name])) {
            return $this->getArenas()[$name];
        }
        return null;
    }

    public function getFFAArenaByName(string $name) {
        if (isset($this->getFFAArenas()[$name])) {
            return $this->getFFAArenas()[$name];
        }
        return null;
    }

    public function getPrefix(): string {
        return $this->getConfig()->get("prefix");
    }

    public function getCountdown(): int {
        return $this->getConfig()->get("countdown");
    }

    public function getJoinMessage(): string {
        return $this->getConfig()->get("join-message");
    }

    public function getQuitMessage(): string {
        return $this->getConfig()->get("quit-message");
    }

    public function getFinishMessage(): string {
        return $this->getConfig()->get("finish-message");
    }

    public function getEloToAdd(): int {
        return $this->getConfig()->get("elo-to-add");
    }

    public function getEloToSub(): int {
        return $this->getConfig()->get("elo-to-sub");
    }

    public function getRanks(): array {
        return $this->getConfig()->get("ranks");
    }

    public function getGroups(): array {
        return $this->getConfig()->get("groups");
    }

    public function getUI(): UI {
        return $this->ui;
    }

    public function getListener(): PlayerListener {
        return $this->listener;
    }

    public function setDeviceInput(string $player, int $input): void {
        $this->deviceInput[$player] = $input;
    }

    public function getDeviceInput(Player $player): int {
        if (!isset($this->deviceInput[$player->getLowerCaseName()])) {
            return -1;
        }
        return $this->deviceInput[$player->getLowerCaseName()];
    }

    public function getClicks(Player $player): int {

        $clicks = $this->updateClicks($player);

        return count($clicks);
    }

    public function addClick(Player $player, Position $pos = null): void {

        if (!isset($this->clicks[$player->getLowerCaseName()])) {
            $this->clicks[$player->getLowerCaseName()] = [];
        }

        $time = (int)round(microtime(true) * 1000);
        $clicks = $this->updateClicks($player, false);

        if ($pos !== null) {
            $actions = $this->getAction($player);
            $lastAction = (int)$actions['previous']['action'];
            $lastActionTime = $actions['previous']['time'];
            $currentActionTime = $actions['current']['time'];
            if ($lastAction === PlayerActionPacket::ACTION_ABORT_BREAK) {
                $difference = $currentActionTime - $lastActionTime;
                if ($difference > 5) {
                    $clicks[$time] = true;
                    $this->clicks[$player->getLowerCaseName()] = $clicks;
                }
            }
            return;
        }

        $clicks[$time] = true;
        $this->clicks[$player->getLowerCaseName()] = $clicks;
    }

    public function updateClicks(Player $player, bool $update = true): array {

        if (!isset($this->clicks[$player->getLowerCaseName()])) {
            return [];
        }

        $clicks = $this->clicks[$player->getLowerCaseName()];
        $removedClicks = [];

        $currentMilliseconds = (int)round(microtime(true) * 1000);

        foreach ($clicks as $millis => $value) {
            $difference = $currentMilliseconds - $millis;
            if ($difference >= 1000) {
                $removedClicks[$millis] = true;
            }
        }

        $clicks = array_diff_key($clicks, $removedClicks);
        if ($update) {
            $this->clicks[$player->getLowerCaseName()] = $clicks;
        }

        return $clicks;
    }

    public function setAction(Player $player, int $action): void {

        $time = round(microtime(true) * 1000);

        if (!isset($this->actions[$player->getLowerCaseName()])) {

            $this->actions[$player->getLowerCaseName()] = [
                'previous' => [
                    'action' => $action,
                    'time' => $time
                ],
                'current' => [
                    'action' => $action,
                    'time' => $time
                ]
            ];

        } else {

            $actions = $this->actions[$player->getLowerCaseName()];
            $previous = $actions['current'];

            $this->actions[$player->getLowerCaseName()] = [
                'previous' => $previous,
                'current' => [
                    'action' => $action,
                    'time' => $time
                ]
            ];
        }
    }

    private function getAction(Player $player) {
        if (!isset($this->actions[$player->getLowerCaseName()])) {
            return [];
        }
        return $this->actions[$player->getLowerCaseName()];
    }

    public function getScoreboardUtil(): Scoreboard {
        return $this->scoreboardutil;
    }

    public function getActiveDuels(): Config {
        return $this->activeduelscfg;
    }

    public function getOpponent(Player $player) {
        $opponent = $this->getPlayerDuels()->get($player->getName());
        return $opponent;
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        switch ($cmd->getName()) {
            case "history":
                if ($sender instanceof Player) {
                    if (!isset($args[0])) {
                        $this->getUI()->duelHistoryUI($sender);
                    }
                }
                break;
            case "ping":
                if ($sender instanceof Player) {
                    if (!isset($args[0])) {
                        $sender->sendMessage("§7Your ping:§a " . $sender->getPing() . "ms");
                    } else {
                        $sender->sendMessage("§7Their ping:§a " . $this->getServer()->getPlayer($args[0])->getPing() . "ms");
                        if (!$this->getServer()->getPlayer($args[0])->isOnline()) {
                            $sender->sendMessage("§cThat player is not online.");
                        }
                    }
                }
                break;
            case "stats":
                if ($sender instanceof Player) {
                    if (!isset($args[0])) {
                        $this->getUI()->statsUI($sender);
                    }
                }
                break;
            case "leaderboard":
                if ($sender instanceof Player) {
                    if (!isset($args[0])) {
                        $this->getUI()->eloUI($sender);
                    }
                }
                break;
            case "rekit":
                if ($sender instanceof Player) {
                    $name = $sender->getName();
                    $activeffa = $this->getActiveFFA()->getAll();
                    $ffaarenanameplayer = $this->getFFAArenaByPlayer($sender);
                    if (!isset($args[0])) {
                        if ($arena = $this->getArenaByPlayer($sender)) {
                            if ($sender->getLevel() === $this->getConfig()->get("lobby")) {
                                $sender->sendMessage("§cYou cannot rekit in the lobby.");
                            } else {
                                $arenaname = $arena->getName();
                                $activeduels = $this->getActiveDuels()->getAll();
                                $player1 = $this->getServer()->getPlayer($this->getActiveDuels()->getNested("$arenaname.player1"));
                                $player2 = $this->getServer()->getPlayer($this->getActiveDuels()->getNested("$arenaname.player2"));
                                $arena->getPlugin()->addKit($player1, $arena->getKit());
                                $arena->getPlugin()->addKit($player2, $arena->getKit());
                                //$player2->sendMessage("§cYou cannot rekit in a duel arena! As a result, both players have been rekitted.");
                                //$player1->sendMessage("§cYou cannot rekit in a duel arena! As a result, both players have been rekitted.");
                            }
                        } elseif ($arena = $this->getActiveFFA()->getNested("$name.none") === "abc") {
                            if ($sender->getLevel() === $this->getConfig()->get("lobby")) {
                                $sender->sendMessage("§cYou cannot rekit in the lobby!");
                            } else {
                                $arenaxname = $this->getActiveFFA()->getNested("$name.arena");
                                $this->addFFAKit($sender, $this->getFFAArenasConfig()->getNested("$arenaxname.kit"));
                                $sender->sendMessage("§aYou have refilled your inventory kit.");
                            }
                        } elseif (!isset($activeffa[$name])) {
                            $sender->sendMessage("§cThis command is on cooldown.");
                        } else {
                            $sender->sendMessage("§cThis command is on cooldown.");
                        }
                    }
                }
                break;
            case "hub":
                if ($sender instanceof Player) {
                    if (!isset($args[0])) {
                        $name = $sender->getName();
                        $config = $this->getConfig();
                        $this->getActiveFFA()->remove("$name");
                        $this->getActiveFFA()->save();
                        $lobby = $this->getServer()->getLevelByName($config->get("lobby"));
                        $pos = new Position(-1228.5, 21.04, 1692.62, $lobby);
                        $sender->teleport($pos);
                        $this->sendMenu($sender);
                        $sender->setGamemode(2);
                        $this->getScoreboardUtil()->setLobbyScoreboard($sender);
                        $eventitem = Item::get(450, 0, 1);
                        $eventitem->setCustomName("§r§l§aJoin Sumo Event");
                        $sender->getInventory()->setItem(5, $eventitem);
                    }
                }
                break;
            case "staff":
                if ($sender instanceof Player) {
                    if (!isset($args[0])) {
                        if ($sender->hasPermission("helios.staff")) {
                            $this->getServer()->dispatchCommand($sender, "vanish");
                            $sender->getInventory()->clearAll();
                            $sender->getArmorInventory()->clearAll();
                            $sender->removeAllEffects();
                            $sender->setHealth(20);
                            $sender->setFood(20);
                            $sender->setAllowFlight(true);
                            $sender->setFlying(true);
                            $hubitem = Item::get(376, 0, 1);
                            $hubitem->setCustomName("§r§l§cLeave staff mode");
                            $sender->getInventory()->setItem(4, $hubitem);
                            $reportitem = Item::get(340, 0, 1);
                            $reportitem->setCustomName("§r§l§aReport Manager");
                            $sender->getInventory()->setItem(5, $reportitem);
                            $tpitem = Item::get(339, 0, 1);
                            $tpitem->setCustomName("§r§l§aTeleport");
                            $sender->getInventory()->setItem(3, $tpitem);
                            $freezeblock = Item::get(79, 0, 1);
                            $freezeblock->setCustomName("§r§l§aFreeze a player");
                            $sender->getInventory()->setItem(2, $freezeblock);
                            $adminitem = Item::get(352, 0, 1);
                            $adminitem->setCustomName("§r§l§aAdmin Menu");
                            $sender->getInventory()->setItem(6, $adminitem);
                        }
                    }
                }
                break;
            case "alias":
                if (!isset($args[0])) {
                    $sender->sendMessage("§aUsage: §7/alias [player]");
                    return true;
                }
                if (!$sender->hasPermission("helios.staff")) return false;
                $name = strtolower($args[0]);
                $player = $this->getServer()->getPlayer($name);
                if ($player instanceof Player) {
                    $ip = $player->getPlayer()->getAddress();
                    $file = new Config($this->getDataFolder() . "ipdb/" . $ip . ".txt");
                    $names = $file->getAll(true);
                    $names = implode(', ', $names);
                    $sender->sendMessage("§aListing alternate accounts...");
                    $sender->sendMessage("§7" . $names);
                    return true;
                }
                break;
            case "freeze":
                if($sender->hasPermission("helios.staff")){
                    if(isset($args[0])){
                        $player = $sender->getServer()->getPlayer($args[0]);
                        if($player instanceof Player){
                            if(!in_array($player->getName(), $this->getListener()->freeze)){
                                $this->getServer()->broadcastMessage("§c".$player->getName()." has been frozen!");
                                $player->sendMessage("§cYou have been frozen. Do not log out!");
                                $player->setImmobile(true);
                                $player->sendTitle("§c§lSTOP!", "§7You have been frozen!");
                                $this->getListener()->freeze[$player->getName()] = $player->getName();
                            }else{
                                $this->getServer()->broadcastMessage("§c".$player->getName()." has been unfrozen.");
                                $player->sendMessage("§aYou can now move.");
                                $player->setImmobile(false);
                                unset($this->getListener()->freeze[$player->getName()]);
                            }
                        }else{$sender->sendMessage("§cCannot find specified player.");}
                    }else{$sender->sendMessage("§aUsage: §7/freeze {player}");}
                }else{$sender->sendMessage("§cYou don't have permission to use this command.");}
                return true;
            case 'pban':
                if(!isset($args[0])){
                    return false;
                }
                $p = array_shift($args);
                $player = $this->getServer()->getPlayer($p);
                if($player !== null && $player->isOnline()){
                    $this->banClient($player, isset($args[0]) ? implode(' ', $args) : '');
                    $sender->sendMessage('§b'.$p.' §dhas been permanently banned from Helios Practice.');
                    $webHook = new Webhook("https://discordapp.com/api/webhooks/756917746209390737/QQL9T4jpNhuhdPT06zXLa_zmK0wbJ-Nil4OgsoAkH9gzgLkQcS4f5H5Ixp1WmdRj-mzV");
                    $msg = new Message();
                    $embed = new Embed();
                    $embed->setTitle("The ban hammer has spoken!");
                    $embed->setColor(0xFF0000);
                    $embed->setDescription("**Staff:** CONSOLE\n**Banned: **" . $p . "\n**Reason:** unfair advantage\n**Length:** permanent (client-ban)");
                    $embed->setFooter("Helios Practice");
                    $msg->addEmbed($embed);
                    $webHook->send($msg);
                    $player->setBanned(true);
                }else{
                    $sender->sendMessage('§c'.$p.' is not online.');
                }
                return true;
            case 'pardon-client':
                if(!isset($args[0])){
                    return false;
                }
                if($this->pardonClient($args[0])){
                    $sender->sendMessage('§b'.$args[0]. ' §dhas been unbanned.');
                }else{
                    $sender->sendMessage('§c'.$args[0].' is not banned.');
                }
                return true;
        }
        return true;
    }
}