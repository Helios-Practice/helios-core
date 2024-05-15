<?php

namespace HeliosTeam\Practice\Managers\Types;

class Permissions {

    protected static Permissions $instance;

    # Administrator permissions.

    public const BREAK_BLOCKS = "helios.blocks.break";
    public const PLACE_BLOCKS = "helios.blocks.place";
    public const SET_RANKS = "helios.ranks.set";
    public const ANTICHEAT_ALERTS = "helios.anticheat.alerts";
    public const VANISH = "helios.vanish";
    public const GAMEMODEUI = "helios.gamemode";
    public const ALIAS = "helios.alias";
    public const PLAYERINFO = "helios.playerinfo";

    # Punishment system permissions.

    public const TEMPMUTE = "helios.tempmute";
    public const TEMPMUTE_IP = "helios.tempmute.ip";
    public const PERMMUTE = "helios.permmute";
    public const PERMMUTE_IP = "helios.permmute.ip";
    public const KICK = "helios.kick";
    public const TEMPBAN = "helios.tempban";
    public const TEMPBAN_IP = "helios.tempban.ip";
    public const PERMBAN = "helios.permban";
    public const PERMBAN_IP = "helios.permban.ip";
    public const HBAN = "helios.hban";

    public function getInstance() : self {
        return self::$instance;
    }
}
