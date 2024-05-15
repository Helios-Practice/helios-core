<?php

namespace HeliosTeam\Hub\Classes;

use HeliosTeam\Libs\Query\GameSpyQuery;
use HeliosTeam\Libs\Query\GameSpyQueryException;

class ExtendedQuery {

    public function getHubCount() : string {
        $query = new GameSpyQuery("51.222.25.239", 19132);
        $query->get("numplayers");
    }
}
