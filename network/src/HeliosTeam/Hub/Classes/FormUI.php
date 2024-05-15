<?php


namespace HeliosTeam\Hub\Classes;

use HeliosTeam\Hub\Main;

class FormUI
{
    /** @var Main $plugin */
    private $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    /*public function LobbyFormUI(Player $player)
    {
        $form = new SimpleForm (function (Player $event, $data) {
            $player = $event->getPlayer();

            if ($data === null) {
                return;
            }
            switch ($data) {
                case 0:

                    break;
                case 1:

                    break;
                case 2:

                    break;
                case 3:

                    break;
                case 4:

                    break;
                case 5:
                    if ($player->hasPermission("staff.servers")) {
                        //Transfer Code
                    } else {
                        //Close button
                    }
                break;
                case 6:
                    if ($player->hasPermission("staff.servers")) {
                        //Transfer
                    }
                break;
            }
        });
        $form->setTitle("§l§aServer Selector");
        $form->setContent("");
        $form->addButton("§aNA Practice\n§rPlaying: §a" . $this->getPlugin()->getQueryCounter()->countNA(true) . "", 0, "textures/items/beef_cooked");
        $form->addButton("§aEU Practice\n§rPlaying: §a" . $this->getPlugin()->getQueryCounter()->countEU(true) . "", 0, "textures/items/potion_bottle_resistance");
        $form->addButton("§aUHC Server\n§rPlaying: §a" . $this->getPlugin()->getQueryCounter()->countUHC(true) . "", 0, "textures/items/potion_bottle_resistance");
        $form->addButton("§aCreative Server\n§rPlaying: §a" . $this->getPlugin()->getQueryCounter()->countCREATIVE(true) . "", 0, "textures/items/potion_bottle_resistance");
        $form->addButton("§aDevelopment Server\n§rPlaying: §a" . $this->getPlugin()->getQueryCounter()->countDEV(true) . "", 0, "textures/items/potion_bottle_resistance");
        $form->addButton("§8<-- Back", 0, "textures/ui/arrowLeft");
        $player->sendForm($form);
        return $form;
    }*/

    public function getPlugin(): Main {
        return $this->plugin;
    }
}