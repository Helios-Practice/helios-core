<?php

namespace HeliosTeam\Hub\Classes;

use HeliosTeam\Hub\Main;
use Libs\libmquery\PMQuery;
use Libs\libmquery\PmQueryException;
use pocketmine\Server;

class QueryCounter {

    private $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    //If number value = to false it'll return Offline string (If the server is offline ofc)
    public static function countHub(bool $numbervalue) {
        try {
            $query = PMQuery::query("51.222.25.239", 19132);
            return (int) $query['Players'];
        } catch (PmQueryException $e) {
            if ($numbervalue === true) {
                return 0;
            } else {
                return 'Offline';
            }
        }
    }

    public static function countNA(bool $numbervalue) {
        try {
            $query = PMQuery::query("51.222.25.239", 19133);
            return (int) $query['Players'];
        } catch (PmQueryException $e) {
            if ($numbervalue === true) {
                return 0;
            } else {
                return 'Offline';
            }
        }
    }

    public static function countEU(bool $numbervalue) {
        try {
            $query = PMQuery::query("51.77.159.114", 19132);
            return (int) $query['Players'];
        } catch (PmQueryException $e) {
            if ($numbervalue === true) {
                return 0;
            } else {
                return 'Offline';
            }
        }
    }

    public static function countUHC(bool $numbervalue) {
        try {
            $query = PMQuery::query("51.222.25.239", 19134);
            return (int) $query['Players'];
        } catch (PmQueryException $e) {
            if ($numbervalue === true) {
                return 0;
            } else {
                return 'Offline';
            }
        }
    }

    public static function countDEV(bool $numbervalue) {
        try {
            $query = PMQuery::query("51.77.159.114", 19133);
            return (int) $query['Players'];
        } catch (PmQueryException $e) {
            if ($numbervalue === true) {
                return 0;
            } else {
                return 'Offline';
            }
        }
    }

    public static function countAll() : int {
        $nacount = self::countNA(true);
        $eucount = self::countEU(true);
        $uhccount = self::countUHC(true);
        return $nacount + $eucount + $uhccount;
    }

    public function countPractice() {
        $nacount = $this->countNA(true);
        $eucount = $this->countEU(true);
        return $nacount + $eucount;
    }
}