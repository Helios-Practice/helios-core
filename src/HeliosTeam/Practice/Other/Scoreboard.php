<?php


namespace HeliosTeam\Practice\Other;

use HeliosTeam\Practice\Main;
use HeliosTeam\Practice\Utils\ScoreboardAPI;
use pocketmine\Player;

class Scoreboard {

    private $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function setLobbyScoreboard(Player $player) {
        $inGameArenas = 0;
        $inQueueArenas = "none";
        foreach($this->plugin->getArenas() as $arena) {
            if(count($arena->getPlayers()) === 2) $inGameArenas++;
            if(count($arena->getPlayers()) === 1) $inQueueArenas = 1;
        }
        foreach ($this->plugin->getArenas() as $arena) {
            $kit = $arena->getKit();
            $type = $arena->getType();
            $inGameArena = $arena->getPlayersInFight($kit, $type);
            $inQueue = $arena->getPlayersInQueue($kit, $type);
            ScoreboardAPI::removeScore($player);
            ScoreboardAPI::setScore($player, "§l§aHELIOS PRACTICE");
            ScoreboardAPI::setScoreLine($player, 1, "§f---------------------");
            ScoreboardAPI::setScoreLine($player, 2, " §fOnline: §a" . count($this->plugin->getServer()->getOnlinePlayers()));
            ScoreboardAPI::setScoreLine($player, 3, " §fRank: §a" . $this->plugin->getServer()->getPluginManager()->getPlugin("PurePerms")->getUserDataMgr()->getGroup($player)->getName());
            ScoreboardAPI::setScoreLine($player, 4, "");
            ScoreboardAPI::setScoreLine($player, 5, " §fTPS: §a" . $this->getPlugin()->getServer()->getTicksPerSecond() . "");
            ScoreboardAPI::setScoreLine($player, 6, " §fQueued: §a" . $inQueueArenas . "");
            ScoreboardAPI::setScoreLine($player, 7, " §f");
            ScoreboardAPI::setScoreLine($player, 8, " §aheliosmc.tk");
            ScoreboardAPI::setScoreLine($player, 9, "§r---------------------");
        }
    }

    public function setPlayer1Scoreboard(Player $player1, Player $player2): void {
        ScoreboardAPI::removeScore($player1);
        ScoreboardAPI::setScore($player1, "§l§aHELIOS PRACTICE");
        ScoreboardAPI::setScoreLine($player1, 1, "§f---------------------");
        ScoreboardAPI::setScoreLine($player1, 2, " §fVS: §a" . $player2->getName() . " ");
        ScoreboardAPI::setScoreLine($player1, 3, " §fMap: §a" . $player1->getLevel()->getFolderName() . " ");
        ScoreboardAPI::setScoreLine($player1, 4, "§a");
        ScoreboardAPI::setScoreLine($player1, 5, " §fYour Ping: §a" . $player1->getPing() . "ms");
        ScoreboardAPI::setScoreLine($player1, 6, " §fTheir Ping: §a" . $player2->getPing() . "ms");
        ScoreboardAPI::setScoreLine($player1, 7, "");
        ScoreboardAPI::setScoreLine($player1, 8, " §aheliosmc.tk");
        ScoreboardAPI::setScoreLine($player1, 9, "§r---------------------");
    }

    public function setPlayer2Scoreboard(Player $player2, Player $player1): void {
            ScoreboardAPI::removeScore($player2);
            ScoreboardAPI::setScore($player2, "§l§aHELIOS PRACTICE");
            ScoreboardAPI::setScoreLine($player2, 1, "§f---------------------");
            ScoreboardAPI::setScoreLine($player2, 2, " §fVS: §a" . $player1->getName(). " ");
            ScoreboardAPI::setScoreLine($player2, 3, " §fMap: §a" . $player2->getLevel()->getFolderName() . " ");
            ScoreboardAPI::setScoreLine($player2, 4, "§a");
            ScoreboardAPI::setScoreLine($player2, 5, " §fYour Ping: §a" . $player2->getPing() . "ms");
            ScoreboardAPI::setScoreLine($player2, 6, " §fTheir Ping: §a" . $player1->getPing() . "ms");
            ScoreboardAPI::setScoreLine($player2, 7, "");
            ScoreboardAPI::setScoreLine($player2, 8, " §aheliosmc.tk");
            ScoreboardAPI::setScoreLine($player2, 9, "§r---------------------");
    }

    public function createTitle(Player $player, string $title){

        $packet=new SetDisplayObjectivePacket();
        $packet->displaySlot="sidebar";
        $packet->objectiveName="objective";
        $packet->displayName=$title;
        $packet->criteriaName="dummy";
        $packet->sortOrder=0;
        $player->sendDataPacket($packet);
    }

    public function createLine(Player $player, int $line, string $content) {
        $packetline = new ScorePacketEntry();
        $packetline->objectiveName = "objective";
        $packetline->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
        $packetline->customName = " ".$content."   ";
        $packetline->score = $line;
        $packetline->scoreboardId = $line;
        $packet = new SetScorePacket();
        $packet->type = SetScorePacket::TYPE_CHANGE;
        $packet->entries[] = $packetline;
        $player->sendDataPacket($packet);
    }

    public function removeLine($player, int $line) {
        $entry = new ScorePacketEntry();
        $entry->objectiveName = "objective";
        $entry->score = $line;
        $entry->scoreboardId = $line;
        $packet = new SetScorePacket();
        $packet->type = SetScorePacket::TYPE_REMOVE;
        $packet->entries[] = $entry;
        $player->sendDataPacket($packet);
    }

    public function setFFAScoreboard(Player $player) {
        ScoreboardAPI::removeScore($player);
        ScoreboardAPI::setScore($player, "§l§aHELIOS PRACTICE");
        ScoreboardAPI::setScoreLine($player, 1, "§f---------------------");
        ScoreboardAPI::setScoreLine($player, 2, " §fArena: §a" . $player->getLevel()->getFolderName());
        ScoreboardAPI::setScoreLine($player, 3, " §fTPS: §a" . $this->getPlugin()->getServer()->getTicksPerSecond());
        ScoreboardAPI::setScoreLine($player, 4, "§a");
        ScoreboardAPI::setScoreLine($player, 5, " §fPing: §a" . $player->getPing() . "ms");
        ScoreboardAPI::setScoreLine($player, 6, "§f");
        ScoreboardAPI::setScoreLine($player, 7, " §aheliosmc.tk");
        ScoreboardAPI::setScoreLine($player, 8, "§r---------------------");
    }

    public function setEventScoreboard(Player $player) {
        ScoreboardAPI::removeScore($player);
        ScoreboardAPI::setScore($player, "§l§aSUMO EVENT");
        ScoreboardAPI::setScoreLine($player, 1, "§f---------------------");
        ScoreboardAPI::setScoreLine($player, 2, " §fArena: §a" . $player->getLevel()->getFolderName());
        ScoreboardAPI::setScoreLine($player, 3, " §fTPS: §a" . $this->getPlugin()->getServer()->getTicksPerSecond());
        ScoreboardAPI::setScoreLine($player, 4, "§a");
        ScoreboardAPI::setScoreLine($player, 5, " §fPing: §a" . $player->getPing() . "ms");
        ScoreboardAPI::setScoreLine($player, 6, "§f");
        ScoreboardAPI::setScoreLine($player, 7, " §aheliosmc.tk");
        ScoreboardAPI::setScoreLine($player, 8, "§r---------------------");
    }

    public function getPlugin(): Main {
        return $this->plugin;
    }
}