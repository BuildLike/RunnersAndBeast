<?php

namespace presentkim\rab\task;

use pocketmine\scheduler\PluginTask;

use pocketmine\tile\Sign;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TE;

class RefreshSignTask extends PluginTask{

    public $prefix = "";

    public function __construct($plugin){
        $this->plugin = $plugin;
        $this->prefix = $this->plugin->prefix;
        parent::__construct($plugin);
    }

    public function onRun($tick) : void{
        $allplayers = $this->plugin->getServer()->getOnlinePlayers();
        $level = $this->plugin->getServer()->getDefaultLevel();
        $tiles = $level->getTiles();
        foreach ($tiles as $t) {
            if ($t instanceof Sign) {
                $text = $t->getText();
                if ($text[3] == $this->prefix) {
                    $aop = 0;
                    $namemap = str_replace("§f", "", $text[2]);
                    foreach ($allplayers as $player) {
                        if ($player->getLevel()->getFolderName() == $namemap) {
                            $aop = $aop + 1;
                        }
                    }
                    $ingame = TE::AQUA . "[Join]";
                    $config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
                    if ($config->get($namemap . "PlayTime") != 515) {
                        $ingame = TE::DARK_PURPLE . "[In game]";
                    } elseif ($aop >= 20) {
                        $ingame = TE::GOLD . "[Full]";
                    }
                    $t->setText($ingame, TE::GREEN . $aop . " / 20", $text[2], $this->prefix);
                }
            }
        }
    }
}