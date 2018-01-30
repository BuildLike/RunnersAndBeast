<?php

namespace EDB\task;

use EDB\ResetMap;
use pocketmine\level\Level;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\level\sound\PopSound;
use pocketmine\scheduler\PluginTask;
use pocketmine\tile\Sign;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TE;

class GameSendTask extends PluginTask{

    public $prefix = "";

    public function __construct($plugin){
        $this->plugin = $plugin;
        $this->prefix = $this->plugin->prefix;
        parent::__construct($plugin);
    }

    public function getResetmap() : ResetMap{
        Return new ResetMap($this);
    }

    public function onRun($tick) : void{
        $config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
        $arenas = $config->get("arenas");
        if (!empty($arenas)) {
            foreach ($arenas as $arena) {
                $time = $config->get($arena . "PlayTime");
                $timeToStart = $config->get($arena . "StartTime");
                $levelArena = $this->plugin->getServer()->getLevelByName($arena);
                if ($levelArena instanceof Level) {
                    $playersArena = $levelArena->getPlayers();
                    if (count($playersArena) == 0) {
                        $config->set($arena . "PlayTime", 515);
                        $config->set($arena . "StartTime", 50);
                        $config->set($arena . "start", 0);
                    } else {
                        if (count($playersArena) >= 6) {
                            $config->set($arena . "start", 1);
                            $config->save();
                        }
                        if ($config->get($arena . "start") == 1) {
                            if ($timeToStart > 0) {
                                $timeToStart--;
                                foreach ($playersArena as $pl) {
                                    $pl->sendPopup(TE::WHITE . "the game starts in" . TE::GREEN . $timeToStart . TE::RESET);
                                    if ($timeToStart <= 5) {
                                        $levelArena->addSound(new PopSound($pl));
                                    }
                                    if ($timeToStart <= 0) {
                                        $levelArena->addSound(new AnvilUseSound($pl));
                                    }
                                }
                                if ($timeToStart == 49) {
                                    $levelArena->setTime(7000);
                                    $levelArena->stopTime();
                                }
                                if ($timeToStart <= 0) {
                                    $tiles = $levelArena->getTiles();
                                    foreach ($tiles as $tile) {
                                        if ($tile instanceof Sign) {
                                            $text = $tile->getText();
                                            if (strtolower($text[0]) == "runner") {
                                                $levelArena->setBlock($tile->add(0, 2, 0), new Air());
                                                $levelArena->setBlock($tile->add(0, 3, 0), new Air());
                                            }
                                        }
                                    }
                                }
                                $config->set($arena . "StartTime", $timeToStart);
                            } else {
                                $aop = count($levelArena->getPlayers());
                                $colors = [];
                                foreach ($playersArena as $pl) {
                                    array_push($colors, $pl->getNameTag());
                                }
                                $names = implode("-", $colors);
                                $bestia = substr_count($names, "§c(Beast)");
                                $corredor = substr_count($names, "§b(Runner)");
                                foreach ($playersArena as $pla) {
                                    if (strpos($pla->getNameTag(), "§c(Beast)") !== false) {
                                        $x = $pla->x;
                                        $z = $pla->z;
                                    }
                                }
                                foreach ($playersArena as $pla) {
                                    $x1 = $pla->x;
                                    $z1 = $pla->z;
                                    $x3 = pow($x1 - $x, 2);
                                    $z3 = pow($z1 - $z, 2);
                                    $lol = $x3 + $z3;
                                    $dist = intval(sqrt($lol));
                                    if (strpos($pla->getNameTag(), "§c(Beast)") === false) {
                                        $pla->sendPopup(TE::BOLD . TE::RED . "Beast:" . $bestia . TE::AQUA . " Runners:" . $corredor . TE::YELLOW . " Dist. Beast:" . TE::LIGHT_PURPLE . $dist . TE::RESET);
                                    }
                                }
                                if ($aop >= 1) {
                                    $winner = null;
                                    if ($bestia != 0 && $corredor == 0) {
                                        $winner = TE::RED . "Beast" . TE::GREEN . " was eliminated by " . TE::AQUA . "Runners" . TE::GREEN . " en ";
                                    }
                                    if ($bestia == 0 && $corredor != 0) {
                                        $winner = TE::AQUA . "Runners" . TE::GREEN . " eliminated " . TE::RED . "Beast" . TE::GREEN . " en ";
                                    }
                                    if ($winner != null) {
                                        foreach ($playersArena as $pl) {
                                            foreach ($this->plugin->getServer()->getOnlinePlayers() as $plpl) {
                                                $plpl->sendMessage($this->prefix . TE::YELLOW . ">> " . $winner . TE::AQUA . $arena);
                                            }
                                            $pl->getInventory()->clearAll();
                                            $pl->removeAllEffects();
                                            $pl->setNameTag($pl->getName());
                                            $pl->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
                                            if (!empty($this->plugin->api)) {
                                                $this->plugin->api->addMoney($pl, 1000);
                                            }
                                            $this->getResetmap()->reload($levelArena);
                                            $config->set($arena . "PlayTime", 515);
                                            $config->set($arena . "StartTime", 50);
                                            $config->set($arena . "start", 0);
                                            $config->save();
                                        }
                                    }
                                }
                                $time--;
                                if ($time == 514) {
                                    $slots = new Config($this->plugin->getDataFolder() . "/slots.yml", Config::YAML);
                                    for ($i = 1; $i < 21; ++$i) {
                                        $slots->set("slot{$i}{$arena}", 0);
                                    }
                                    $slots->save();
                                    foreach ($playersArena as $pl) {
                                        $pl->sendMessage(TE::YELLOW . ">--------------------------------");
                                        $pl->sendMessage(TE::YELLOW . ">" . TE::RED . "Attention:" . TE::GOLD . " game has started");
                                        $pl->sendMessage(TE::YELLOW . ">" . TE::WHITE . "using the map" . TE::AQUA . $arena);
                                        $pl->sendMessage(TE::YELLOW . ">" . TE::GREEN . "Runners have" . TE::AQUA . "15" . TE::GREEN . " seconds left");
                                        $pl->sendMessage(TE::YELLOW . ">--------------------------------");
                                    }
                                }
                                if ($time == 500) {
                                    foreach ($playersArena as $pl) {
                                        $levelArena->addSound(new AnvilUseSound($pl));
                                        $pl->sendMessage(TE::GREEN . ">> " . TE::RED . "THE BEAST HAS BEEN RELEASED" . TE::RESET);
                                    }
                                    $tiles = $levelArena->getTiles();
                                    foreach ($tiles as $tile) {
                                        if ($tile instanceof Sign) {
                                            $text = $tile->getText();
                                            if (strtolower($text[0]) == "beast") {
                                                $levelArena->setBlock($tile->add(0, 2, 0), new Air());
                                                $levelArena->setBlock($tile->add(0, 3, 0), new Air());
                                            }
                                        }
                                    }
                                }
                                if ($time >= 300) {
                                    $time2 = $time - 180;
                                    $minutes = $time2 / 60;
                                } else {
                                    $minutes = $time / 60;
                                    if (is_int($minutes) && $minutes > 0) {
                                        foreach ($playersArena as $pl) {
                                            $pl->sendMessage($this->prefix . TE::YELLOW . $minutes . " " . TE::GREEN . "minutes remaining");
                                        }
                                    } else {
                                        if ($time == 30 || $time == 15 || $time == 10 || $time == 5 || $time == 4 || $time == 3 || $time == 2 || $time == 1) {
                                            foreach ($playersArena as $pl) {
                                                $pl->sendMessage($this->prefix . TE::YELLOW . $time . " " . TE::GREEN . "seconds remaining");
                                            }
                                        }
                                    }
                                    if ($time <= 0) {
                                        foreach ($playersArena as $pl) {
                                            $pl->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn(), 0, 0);
                                            $pl->sendMessage($this->prefix . TE::AQUA . "Runners" . TE::GREEN . " eliminated  " . TE::RED . "Beast" . TE::GREEN . " in " . TE::AQUA . $arena);
                                            $pl->getInventory()->clearAll();
                                            $pl->removeAllEffects();
                                            $pl->setFood(20);
                                            $pl->setHealth(20);
                                            if (strpos($pl->getNameTag(), "§c(Beast)") === false) {
                                                $this->plugin->api->addMoney($pl, 1000);
                                            }
                                            $pl->setNameTag($pl->getName());
                                            $this->getResetmap()->reload($levelArena);
                                            $config->set($arena . "start", 0);
                                            $config->save();
                                        }
                                        $time = 515;
                                    }
                                }
                                $config->set($arena . "PlayTime", $time);
                            }
                        } else {
                            if ($timeToStart <= 0) {
                                foreach ($playersArena as $pl) {
                                    $this->getOwner()->getServer()->broadcastMessage($this->prefix . TE::AQUA . "Runners" . TE::GREEN . "eliminated" . TE::RED . "Beast" . TE::GREEN . " in " . TE::AQUA . $arena);
                                    $pl->teleport($this->getOwner()->getServer()->getDefaultLevel()->getSafeSpawn(), 0, 0);
                                    $pl->getInventory()->clearAll();
                                    $pl->removeAllEffects();
                                    $pl->setHealth(20);
                                    $pl->setFood(20);
                                    $pl->setNameTag($pl->getName());
                                    if (strpos($pl->getNameTag(), "§c(Beast)") === false) {
                                        $this->plugin->api->addMoney($pl, 1000);
                                    }
                                    $this->getResetmap()->reload($levelArena);
                                    $config->set($arena . "start", 0);
                                    $config->save();
                                }
                                $config->set($arena . "PlayTime", 515);
                                $config->set($arena . "StartTime", 50);
                            } else {
                                foreach ($playersArena as $pl) {
                                    $pl->sendPopup(TE::LIGHT_PURPLE . "waiting for more players" . TE::RESET);
                                }
                                $config->set($arena . "PlayTime", 515);
                                $config->set($arena . "StartTime", 50);
                            }
                        }
                    }
                }
            }
        }
        $config->save();
    }
}