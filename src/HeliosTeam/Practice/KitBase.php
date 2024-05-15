<?php

namespace HeliosTeam\Practice;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLoader;
use pocketmine\Server;

class KitBase extends PluginBase {

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
    private $plugin;

    # Gets the main class instance.

    public $activeffacfg;

    public function addKit(Player $player, string $kit): void {
        $kits = Main::getInstance()->getKits()->getAll();
        $player->getInventory()->clearAll();
        $player->removeAllEffects();
        $player->getArmorInventory()->clearAll();
        $player->setHealth(20);
        $player->setFood(20);
        foreach($kits[$kit]["effects"] as $effects){
            // Delemiters are ID:TIME:AMP Visible is always set to false
            $effectDel = explode(":", $effects);
            $effect = new EffectInstance(Effect::getEffect((int)$effectDel[0]), (int)$effectDel[1], (int)$effectDel[2], false);
            $player->addEffect($effect);
        }
        foreach($kits[$kit]["commands"] as $cmd) {
            $this->getServer()->dispatchCommand(new ConsoleCommandSender, str_replace("{player}", $player->getName(), $cmd));
        }
        foreach($kits[$kit]["items"] as $items) {
            $item = explode(":", $items);
            $itemAdd = ItemFactory::get((int)$item[0], (int)$item[1], (int)$item[2]);
            if(isset($item[3])) {
                $itemAdd->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($item[3]), (int)$item[4]));
            }
            $player->getInventory()->addItem($itemAdd);
        }
        if(is_array(explode(":", (string)$kits[$kit]["helmet"]))) {
            $armor = explode(":", (string)$kits[$kit]["helmet"]);
            $helmet = ItemFactory::get((int)$armor[0]);
            if(isset($armor[1])) {
                $helmet->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        }else{
            $armor = $kits[$kit]["helmet"];
            $helmet = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setHelmet($helmet);
        if(is_array(explode(":", (string)$kits[$kit]["chestplate"]))) {
            $armor = explode(":", (string)$kits[$kit]["chestplate"]);
            $chestplate = ItemFactory::get((int)$armor[0]);
            if(isset($armor[1])) {
                $chestplate->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        }else{
            $armor = $kits[$kit]["chestplate"];
            $chestplate = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setChestplate($chestplate);
        if(is_array(explode(":", (string)$kits[$kit]["leggings"]))) {
            $armor = explode(":", (string)$kits[$kit]["leggings"]);
            $leggings = ItemFactory::get((int)$armor[0]);
            if(isset($armor[1])) {
                $leggings->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        }else{
            $armor = $kits[$kit]["leggings"];
            $leggings = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setLeggings($leggings);
        if(is_array(explode(":", (string)$kits[$kit]["boots"]))) {
            $armor = explode(":", (string)$kits[$kit]["boots"]);
            $boots = ItemFactory::get((int)$armor[0]);
            if(isset($armor[1])) {
                $boots->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        }else{
            $armor = $kits[$kit]["boots"];
            $boots = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setBoots($boots); // test
    }

    public function addFFAKit(Player $player, string $kit): void {
        $kits = $this->getFFAKits()->getAll();
        $player->getInventory()->clearAll();
        $player->removeAllEffects();
        $player->getArmorInventory()->clearAll();
        $player->setHealth(20);
        $player->setFood(20);
        foreach($kits[$kit]["effects"] as $effects){
            // Delemiters are ID:TIME:AMP Visible is always set to false
            $effectDel = explode(":", $effects);
            $effect = new EffectInstance(Effect::getEffect((int)$effectDel[0]), (int)$effectDel[1], (int)$effectDel[2], false);
            $player->addEffect($effect);
        }
        foreach($kits[$kit]["commands"] as $cmd) {
            $this->getServer()->dispatchCommand(new ConsoleCommandSender, str_replace("{player}", $player->getName(), $cmd));
        }
        foreach($kits[$kit]["items"] as $items) {
            $item = explode(":", $items);
            $itemAdd = ItemFactory::get((int)$item[0], (int)$item[1], (int)$item[2]);
            if(isset($item[3])) {
                $itemAdd->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($item[3]), (int)$item[4]));
            }
            $player->getInventory()->addItem($itemAdd);
        }
        if(is_array(explode(":", (string)$kits[$kit]["helmet"]))) {
            $armor = explode(":", (string)$kits[$kit]["helmet"]);
            $helmet = ItemFactory::get((int)$armor[0]);
            if(isset($armor[1])) {
                $helmet->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        }else{
            $armor = $kits[$kit]["helmet"];
            $helmet = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setHelmet($helmet);
        if(is_array(explode(":", (string)$kits[$kit]["chestplate"]))) {
            $armor = explode(":", (string)$kits[$kit]["chestplate"]);
            $chestplate = ItemFactory::get((int)$armor[0]);
            if(isset($armor[1])) {
                $chestplate->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        }else{
            $armor = $kits[$kit]["chestplate"];
            $chestplate = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setChestplate($chestplate);
        if(is_array(explode(":", (string)$kits[$kit]["leggings"]))) {
            $armor = explode(":", (string)$kits[$kit]["leggings"]);
            $leggings = ItemFactory::get((int)$armor[0]);
            if(isset($armor[1])) {
                $leggings->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        }else{
            $armor = $kits[$kit]["leggings"];
            $leggings = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setLeggings($leggings);
        if(is_array(explode(":", (string)$kits[$kit]["boots"]))) {
            $armor = explode(":", (string)$kits[$kit]["boots"]);
            $boots = ItemFactory::get((int)$armor[0]);
            if(isset($armor[1])) {
                $boots->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($armor[1]), (int)$armor[2]));
            }
        }else{
            $armor = $kits[$kit]["boots"];
            $boots = ItemFactory::get((int)$armor);
        }
        $player->getArmorInventory()->setBoots($boots); // test
    }
}
