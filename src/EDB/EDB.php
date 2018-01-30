<?php
/**
 * Plugin creado por KaitoDoDo
 *
 * Plugin modificado por Ulises Gamer
 * PD: no digo que este plugin sea mio, solo le agregue mas slots..
 */

namespace EDB;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as TE;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\tile\Sign;
use pocketmine\level\Level;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use onebone\economyapi\EconomyAPI;
use pocketmine\level\sound\PopSound;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\block\Air;
use pocketmine\item\Item;
use pocketmine\event\entity\EntityLevelChangeEvent;

class EDB extends PluginBase implements Listener{

    public $prefix = TE::GRAY . "[" . TE::GREEN . TE::BOLD . "Runners" . TE::AQUA . " & " . TE::RED . "Beasts" . TE::RESET . TE::GRAY . "]";

    public $mode = 0;

    public $arenas = [];

    public $currentLevel = "";

    public $op = [];

    public function onEnable(){
        $this->getLogger()->info(TE::GREEN . "Escapa De La Bestia ENABLE");

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if (!empty($this->economy)) {
            $this->api = EconomyAPI::getInstance();
        }
        @mkdir($this->getDataFolder());
        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
        if ($config->get("arenas") != null) {
            $this->arenas = $config->get("arenas");
        }
        foreach ($this->arenas as $lev) {
            $this->getServer()->loadLevel($lev);
        }
        $config->save();
        $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
        $slots->save();
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 20);
    }

    public function onDisable(){
        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
        $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
        if ($config->get("arenas") != null) {
            $this->arenas = $config->get("arenas");
        }
        foreach ($this->arenas as $arena) {
            $slots->set("slot1" . $arena, 0);
            $slots->set("slot2" . $arena, 0);
            $slots->set("slot3" . $arena, 0);
            $slots->set("slot4" . $arena, 0);
            $slots->set("slot5" . $arena, 0);
            $slots->set("slot6" . $arena, 0);
            $slots->set("slot7" . $arena, 0);
            $slots->set("slot8" . $arena, 0);
            $slots->set("slot9" . $arena, 0);
            $slots->set("slot10" . $arena, 0);
            $slots->set("slot11" . $arena, 0);
            $slots->set("slot12" . $arena, 0);
            $slots->set("slot13" . $arena, 0);
            $slots->set("slot14" . $arena, 0);
            $slots->set("slot15" . $arena, 0);
            $slots->set("slot16" . $arena, 0);
            $slots->set("slot17" . $arena, 0);
            $slots->set("slot18" . $arena, 0);
            $slots->set("slot19" . $arena, 0);
            $slots->set("slot20" . $arena, 0);
            $config->set($arena . "inicio", 0);
            $slots->save();
            $this->reload($arena);
        }
    }

    public function reload($lev){
        if ($this->getServer()->isLevelLoaded($lev)) {
            $this->getServer()->unloadLevel($this->getServer()->getLevelByName($lev));
        }
        $zip = new \ZipArchive;
        $zip->open($this->getDataFolder() . 'arenas/' . $lev . '.zip');
        $zip->extractTo($this->getServer()->getDataPath() . 'worlds');
        $zip->close();
        unset($zip);
        return true;
    }

    public function onDeath(PlayerDeathEvent $event){
        $jugador = $event->getEntity();
        $mapa = $jugador->getLevel()->getFolderName();
        if (in_array($mapa, $this->arenas)) {
            $event->setDeathMessage("");
            if ($event->getEntity()->getLastDamageCause() instanceof EntityDamageByEntityEvent) {
                $asassin = $event->getEntity()->getLastDamageCause()->getDamager();
                if ($asassin instanceof Player) {
                    foreach ($jugador->getLevel()->getPlayers() as $pl) {
                        $pl->sendMessage($jugador->getNameTag() . TE::DARK_RED . " was killed by " . $asassin->getNameTag());
                    }
                }
            } else {
                foreach ($jugador->getLevel()->getPlayers() as $pl) {
                    $pl->sendMessage(TE::RED . $jugador->getNameTag() . TE::DARK_RED . " died");
                }
            }
            $jugador->setNameTag($jugador->getName());
        }
    }

    public function chang($pl){
        $level = $pl->getLevel()->getFolderName();
        if (in_array($level, $this->arenas)) {
            $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
            if ($slots->get("slot1" . $level) == $pl->getName()) {
                $slots->set("slot1" . $level, 0);
            }
            if ($slots->get("slot2" . $level) == $pl->getName()) {
                $slots->set("slot2" . $level, 0);
            }
            if ($slots->get("slot3" . $level) == $pl->getName()) {
                $slots->set("slot3" . $level, 0);
            }
            if ($slots->get("slot4" . $level) == $pl->getName()) {
                $slots->set("slot4" . $level, 0);
            }
            if ($slots->get("slot5" . $level) == $pl->getName()) {
                $slots->set("slot5" . $level, 0);
            }
            if ($slots->get("slot6" . $level) == $pl->getName()) {
                $slots->set("slot6" . $level, 0);
            }
            if ($slots->get("slot7" . $level) == $pl->getName()) {
                $slots->set("slot7" . $level, 0);
            }
            if ($slots->get("slot8" . $level) == $pl->getName()) {
                $slots->set("slot8" . $level, 0);
            }
            if ($slots->get("slot9" . $level) == $pl->getName()) {
                $slots->set("slot9" . $level, 0);
            }
            if ($slots->get("slot10" . $level) == $pl->getName()) {
                $slots->set("slot10" . $level, 0);
            }
            if ($slots->get("slot11" . $level) == $pl->getName()) {
                $slots->set("slot11" . $level, 0);
            }
            if ($slots->get("slot12" . $level) == $pl->getName()) {
                $slots->set("slot12" . $level, 0);
            }
            if ($slots->get("slot13" . $level) == $pl->getName()) {
                $slots->set("slot13" . $level, 0);
            }
            if ($slots->get("slot14" . $level) == $pl->getName()) {
                $slots->set("slot14" . $level, 0);
            }
            if ($slots->get("slot15" . $level) == $pl->getName()) {
                $slots->set("slot15" . $level, 0);
            }
            if ($slots->get("slot16" . $level) == $pl->getName()) {
                $slots->set("slot16" . $level, 0);
            }
            if ($slots->get("slot17" . $level) == $pl->getName()) {
                $slots->set("slot17" . $level, 0);
            }
            if ($slots->get("slot18" . $level) == $pl->getName()) {
                $slots->set("slot18" . $level, 0);
            }
            if ($slots->get("slot19" . $level) == $pl->getName()) {
                $slots->set("slot19" . $level, 0);
            }
            if ($slots->get("slot20" . $level) == $pl->getName()) {
                $slots->set("slot20" . $level, 0);
            }
            $slots->save();
        }
    }

    public function enCambioMundo(EntityLevelChangeEvent $event){
        $pl = $event->getEntity();
        if ($pl instanceof Player) {
            $lev = $event->getOrigin();
            if ($lev instanceof Level && in_array($lev->getFolderName(), $this->arenas)) {
                $level = $lev->getFolderName();
                $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
                if ($slots->get("slot1" . $level) == $pl->getName()) {
                    $slots->set("slot1" . $level, 0);
                }
                if ($slots->get("slot2" . $level) == $pl->getName()) {
                    $slots->set("slot2" . $level, 0);
                }
                if ($slots->get("slot3" . $level) == $pl->getName()) {
                    $slots->set("slot3" . $level, 0);
                }
                if ($slots->get("slot4" . $level) == $pl->getName()) {
                    $slots->set("slot4" . $level, 0);
                }
                if ($slots->get("slot5" . $level) == $pl->getName()) {
                    $slots->set("slot5" . $level, 0);
                }
                if ($slots->get("slot6" . $level) == $pl->getName()) {
                    $slots->set("slot6" . $level, 0);
                }
                if ($slots->get("slot7" . $level) == $pl->getName()) {
                    $slots->set("slot7" . $level, 0);
                }
                if ($slots->get("slot8" . $level) == $pl->getName()) {
                    $slots->set("slot8" . $level, 0);
                }
                if ($slots->get("slot9" . $level) == $pl->getName()) {
                    $slots->set("slot9" . $level, 0);
                }
                if ($slots->get("slot10" . $level) == $pl->getName()) {
                    $slots->set("slot10" . $level, 0);
                }
                if ($slots->get("slot11" . $level) == $pl->getName()) {
                    $slots->set("slot11" . $level, 0);
                }
                if ($slots->get("slot12" . $level) == $pl->getName()) {
                    $slots->set("slot12" . $level, 0);
                }
                if ($slots->get("slot13" . $level) == $pl->getName()) {
                    $slots->set("slot13" . $level, 0);
                }
                if ($slots->get("slot14" . $level) == $pl->getName()) {
                    $slots->set("slot14" . $level, 0);
                }
                if ($slots->get("slot15" . $level) == $pl->getName()) {
                    $slots->set("slot15" . $level, 0);
                }
                if ($slots->get("slot16" . $level) == $pl->getName()) {
                    $slots->set("slot16" . $level, 0);
                }
                if ($slots->get("slot17" . $level) == $pl->getName()) {
                    $slots->set("slot17" . $level, 0);
                }
                if ($slots->get("slot18" . $level) == $pl->getName()) {
                    $slots->set("slot18" . $level, 0);
                }
                if ($slots->get("slot19" . $level) == $pl->getName()) {
                    $slots->set("slot19" . $level, 0);
                }
                if ($slots->get("slot20" . $level) == $pl->getName()) {
                    $slots->set("slot20" . $level, 0);
                }
                $slots->save();
            }
        }
    }

    public function onLog(PlayerLoginEvent $event){
        $player = $event->getPlayer();
        if (in_array($player->getLevel()->getFolderName(), $this->arenas)) {
            $player->getInventory()->clearAll();
            $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
            $this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
            $player->teleport($spawn, 0, 0);
        }
    }

    public function onQuit(PlayerQuitEvent $event){
        $pl = $event->getPlayer();
        $level = $pl->getLevel()->getFolderName();
        if (in_array($level, $this->arenas)) {
            $pl->removeAllEffects();
            $pl->getInventory()->clearAll();
            $pl->setNameTag($pl->getName());
            $this->chang($pl);
        }
    }

    public function onBlockBr(BlockBreakEvent $event){
        $player = $event->getPlayer();
        $level = $player->getLevel()->getFolderName();
        if (in_array($level, $this->arenas)) {
            $event->setCancelled();
        }
    }

    public function onBlockPl(BlockPlaceEvent $event){
        $player = $event->getPlayer();
        $level = $player->getLevel()->getFolderName();
        if (in_array($level, $this->arenas)) {
            $event->setCancelled();
        }
    }

    public function onDam(EntityDamageEvent $event){
        if ($event instanceof EntityDamageByEntityEvent) {
            $player = $event->getEntity();
            $level = $player->getLevel()->getFolderName();
            if (in_array($level, $this->arenas)) {
                if ($player instanceof Player && $event->getDamager() instanceof Player) {
                    $golpeado = $player->getNameTag();
                    $golpeador = $event->getDamager()->getNameTag();
                    if ((strpos($golpeado, "§b(Runner)") !== false) && (strpos($golpeador, "§b(Runner)") !== false)) {
                        $event->setCancelled();
                    }
                }
            }
        }
    }

    public function onCommand(CommandSender $player, Command $cmd, $label, array $args){
        switch ($cmd->getName()) {
            case "edb":
                if ($player->isOp()) {
                    if (!empty($args[0])) {
                        if ($args[0] == "make") {
                            if (!empty($args[1])) {
                                if (file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1])) {
                                    $this->getServer()->loadLevel($args[1]);
                                    $this->getServer()->getLevelByName($args[1])->loadChunk($this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorX(), $this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorZ());
                                    array_push($this->arenas, $args[1]);
                                    $this->currentLevel = $args[1];
                                    $this->mode = 1;
                                    $player->sendMessage($this->prefix . "Toca Spawn Corredores!");
                                    $player->setGamemode(1);
                                    array_push($this->op, $player->getName());
                                    $player->teleport($this->getServer()->getLevelByName($args[1])->getSafeSpawn(), 0, 0);
                                    $name = $args[1];
                                    $this->zipper($player, $name);
                                } else {
                                    $player->sendMessage($this->prefix . "ERROR missing world.");
                                }
                            } else {
                                $player->sendMessage($this->prefix . "ERROR missing parameters.");
                            }
                        } else {
                            $player->sendMessage($this->prefix . "Invalid Command.");
                        }
                    } else {
                        $player->sendMessage($this->prefix . " §bEscapa De La Bestia Comandos!");
                        $player->sendMessage($this->prefix . " §b/edb make [world]: Crear el juego de EdlB!");
                    }
                }
                return true;

            case "edbstart":
                if ($player->isOp()) {
                    if (!empty($args[0])) {
                        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                        if ($config->get($args[0] . "StartTime") != null) {
                            $config->set($args[0] . "StartTime", 5);
                            $config->save();
                            $player->sendMessage($this->prefix . "§aEmpezando en 5...");
                        }
                    } else {
                        $level = $player->getLevel()->getFolderName();
                        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                        if ($config->get($level . "StartTime") != null) {
                            $config->set($level . "StartTime", 5);
                            $config->save();
                            $player->sendMessage($this->prefix . "§cEmpezando en 5...");
                        }
                    }
                }
                return true;
        }
    }

    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $tile = $player->getLevel()->getTile($block);

        if ($tile instanceof Sign) {
            if (($this->mode == 26) && (in_array($player->getName(), $this->op))) {
                $tile->setText(TE::AQUA . "[Unirse]", TE::GREEN . "0 / 20", "§f" . $this->currentLevel, $this->prefix);
                $this->refreshArenas();
                $this->currentLevel = "";
                $this->mode = 0;
                $player->sendMessage($this->prefix . "Arena Registered!");
                array_shift($this->op);
            } else {
                $text = $tile->getText();
                if ($text[3] == $this->prefix) {
                    if ($text[0] == TE::AQUA . "[Unirse]") {
                        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                        $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
                        $namemap = str_replace("§f", "", $text[2]);
                        $level = $this->getServer()->getLevelByName($namemap);
                        if (strpos($player->getNameTag(), "§c(Bestia)") !== false) {
                            $team = TE::RED . "Bestia";
                            if ($slots->get("slot6" . $namemap) == null) {
                                $thespawn = $config->get($namemap . "Spawn2");
                                $slots->set("slot6" . $namemap, $player->getName());
                            } else {
                                $player->sendMessage($this->prefix . TE::RED . "Ya hay Bestia en este juego.");
                                goto sinequipo;
                            }
                        } elseif ($slots->get("slot1" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot1" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot2" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot2" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot3" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot3" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot4" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot4" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot5" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot5" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot6" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn2");
                            $slots->set("slot6" . $namemap, $player->getName());
                            $player->setNameTag("§c(Bestia)" . TE::GOLD . $player->getName());
                            $team = TE::RED . "Bestia";
                        } elseif ($slots->get("slot7" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot7" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot8" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot8" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot9" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot9" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot10" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot10" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot11" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn2");
                            $slots->set("slot11" . $namemap, $player->getName());
                            $player->setNameTag("§c(Bestia)" . TE::GREEN . $player->getName());
                            $team = TE::RED . "Bestia";
                        } elseif ($slots->get("slot12" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot12" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot13" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot13" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot14" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot14" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot15" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot15" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot16" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn2");
                            $slots->set("slot16" . $namemap, $player->getName());
                            $player->setNameTag("§c(Bestia)" . TE::GOLD . $player->getName());
                            $team = TE::RED . "Bestia";
                        } elseif ($slots->get("slot17" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot17" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot18" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot18" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot19" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot19" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Corredor";
                        } elseif ($slots->get("slot20" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn2");
                            $slots->set("slot20" . $namemap, $player->getName());
                            $player->setNameTag("§c(Bestia)" . TE::GREEN . $player->getName());
                            $team = TE::RED . "Bestia";
                        } else {
                            nohay:
                            $player->sendMessage($this->prefix . TE::RED . "No hay lugares disponibles.");
                            goto sinequipo;
                        }
                        $slots->save();
                        $player->getInventory()->clearAll();
                        $player->removeAllEffects();
                        $player->setMaxHealth(20);
                        $player->setHealth(20);
                        $player->setFood(20);
                        $spawn = new Position($thespawn[0] + 0.5, $thespawn[1], $thespawn[2] + 0.5, $level);
                        $level->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
                        $player->teleport($spawn, 0, 0);
                        if (strpos($player->getNameTag(), "§c(Bestia)") !== false) {
                            $player->setGamemode(0);
                            $player->getInventory()->setHelmet(Item::get(Item::DIAMOND_HELMET));
                            $player->getInventory()->setChestplate(Item::get(Item::DIAMOND_CHESTPLATE));
                            $player->getInventory()->setLeggings(Item::get(Item::DIAMOND_LEGGINGS));
                            $player->getInventory()->setBoots(Item::get(Item::DIAMOND_BOOTS));
                            $player->getInventory()->setItem(0, Item::get(Item::DIAMOND_SWORD, 0, 1));
                            $player->getInventory()->setItem(1, Item::get(Item::GOLDEN_APPLE, 0, 3));
                            $player->getInventory()->setItem(2, Item::get(Item::BREAD, 0, 5));
                            $player->getInventory()->setItem(3, Item::get(Item::BOW, 0, 1));
                            $player->getInventory()->setItem(4, Item::get(Item::ARROW, 0, 15));
                            $player->getInventory()->sendArmorContents($player);
                            $player->getInventory()->setHotbarSlotIndex(0, 0);
                        }
                        $player->sendMessage($this->prefix . "-[corredor=runner|bestia=beast]-    you are a " . $team);
                        foreach ($level->getPlayers() as $playersinarena) {
                            $playersinarena->sendMessage($player->getNameTag() . " §f joined");
                        }
                        sinequipo:
                    } else {
                        $player->sendMessage($this->prefix . "you cant join now");
                    }
                }
            }
        } elseif (in_array($player->getName(), $this->op) && $this->mode == 1) {
            $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
            $config->set($this->currentLevel . "Spawn" . $this->mode, [
              $block->getX(),
              $block->getY() + 1,
              $block->getZ(),
            ]);
            $player->sendMessage($this->prefix . "Spawn Corredores registrado!");
            $this->mode++;
            $config->save();
        } elseif (in_array($player->getName(), $this->op) && $this->mode == 2) {
            $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
            $config->set($this->currentLevel . "Spawn" . $this->mode, [
              $block->getX(),
              $block->getY() + 1,
              $block->getZ(),
            ]);
            $player->sendMessage($this->prefix . "Spawn Bestia registrado!");
            $config->set("arenas", $this->arenas);
            $config->set($this->currentLevel . "inicio", 0);
            $player->sendMessage($this->prefix . "Toca un cartel para registrar Arena!");
            $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
            $this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
            $player->teleport($spawn, 0, 0);
            $config->save();
            $this->mode = 26;
        }
    }

    public function refreshArenas(){
        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
        $config->set("arenas", $this->arenas);
        foreach ($this->arenas as $arena) {
            $config->set($arena . "PlayTime", 515);
            $config->set($arena . "StartTime", 50);
        }
        $config->save();
    }

    public function zipper($player, $name){
        $path = realpath($player->getServer()->getDataPath() . 'worlds/' . $name);
        $zip = new \ZipArchive;
        @mkdir($this->getDataFolder() . 'arenas/', 0755);
        $zip->open($this->getDataFolder() . 'arenas/' . $name . '.zip', $zip::CREATE | $zip::OVERWRITE);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($files as $datos) {
            if (!$datos->isDir()) {
                $relativePath = $name . '/' . substr($datos, strlen($path) + 1);
                $zip->addFile($datos, $relativePath);
            }
        }
        $zip->close();
        $player->getServer()->loadLevel($name);
        unset($zip, $path, $files);
    }
}

class RefreshSigns extends PluginTask{

    public $prefix = "";

    public function __construct($plugin){
        $this->plugin = $plugin;
        $this->prefix = $this->plugin->prefix;
        parent::__construct($plugin);
    }

    public function onRun($tick){
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
                    $ingame = TE::AQUA . "[Unirse]";
                    $config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
                    if ($config->get($namemap . "PlayTime") != 515) {
                        $ingame = TE::DARK_PURPLE . "[In game]";
                    } elseif ($aop >= 20) {
                        $ingame = TE::GOLD . "[full]";
                    }
                    $t->setText($ingame, TE::GREEN . $aop . " / 20", $text[2], $this->prefix);
                }
            }
        }
    }
}

class GameSender extends PluginTask{

    public $prefix = "";

    public function __construct($plugin){
        $this->plugin = $plugin;
        $this->prefix = $this->plugin->prefix;
        parent::__construct($plugin);
    }

    public function getResetmap(){
        Return new ResetMap($this);
    }

    public function onRun($tick){
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
                        $config->set($arena . "inicio", 0);
                    } else {
                        if (count($playersArena) >= 6) {
                            $config->set($arena . "inicio", 1);
                            $config->save();
                        }
                        if ($config->get($arena . "inicio") == 1) {
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
                                $bestia = substr_count($names, "§c(Bestia)");
                                $corredor = substr_count($names, "§b(Runner)");
                                foreach ($playersArena as $pla) {
                                    if (strpos($pla->getNameTag(), "§c(Bestia)") !== false) {
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
                                    if (strpos($pla->getNameTag(), "§c(Bestia)") === false) {
                                        $pla->sendPopup(TE::BOLD . TE::RED . "Beast:" . $bestia . TE::AQUA . " Runners:" . $corredor . TE::YELLOW . " Dist. Bestia:" . TE::LIGHT_PURPLE . $dist . TE::RESET);
                                    }
                                }
                                if ($aop >= 1) {
                                    $winner = null;
                                    if ($bestia != 0 && $corredor == 0) {
                                        $winner = TE::RED . "Bestia" . TE::GREEN . " was eliminated by " . TE::AQUA . "Corredores" . TE::GREEN . " en ";
                                    }
                                    if ($bestia == 0 && $corredor != 0) {
                                        $winner = TE::AQUA . "Corredores" . TE::GREEN . " eliminated " . TE::RED . "Bestia" . TE::GREEN . " en ";
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
                                            $config->set($arena . "inicio", 0);
                                            $config->save();
                                        }
                                    }
                                }
                                $time--;
                                if ($time == 514) {
                                    $slots = new Config($this->plugin->getDataFolder() . "/slots.yml", Config::YAML);
                                    $slots->set("slot1" . $arena, 0);
                                    $slots->set("slot2" . $arena, 0);
                                    $slots->set("slot3" . $arena, 0);
                                    $slots->set("slot4" . $arena, 0);
                                    $slots->set("slot5" . $arena, 0);
                                    $slots->set("slot6" . $arena, 0);
                                    $slots->set("slot7" . $arena, 0);
                                    $slots->set("slot8" . $arena, 0);
                                    $slots->set("slot9" . $arena, 0);
                                    $slots->set("slot10" . $arena, 0);
                                    $slots->set("slot11" . $arena, 0);
                                    $slots->set("slot12" . $arena, 0);
                                    $slots->set("slot13" . $arena, 0);
                                    $slots->set("slot14" . $arena, 0);
                                    $slots->set("slot15" . $arena, 0);
                                    $slots->set("slot16" . $arena, 0);
                                    $slots->set("slot17" . $arena, 0);
                                    $slots->set("slot18" . $arena, 0);
                                    $slots->set("slot19" . $arena, 0);
                                    $slots->set("slot20" . $arena, 0);
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
                                        $pl->sendMessage(TE::GREEN . ">> " . TE::RED . "LA BESTIA HA SIDO LIBERADA" . TE::RESET);
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
                                            if (strpos($pl->getNameTag(), "§c(Bestia)") === false) {
                                                $this->plugin->api->addMoney($pl, 1000);
                                            }
                                            $pl->setNameTag($pl->getName());
                                            $this->getResetmap()->reload($levelArena);
                                            $config->set($arena . "inicio", 0);
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
                                    if (strpos($pl->getNameTag(), "§c(Bestia)") === false) {
                                        $this->plugin->api->addMoney($pl, 1000);
                                    }
                                    $this->getResetmap()->reload($levelArena);
                                    $config->set($arena . "inicio", 0);
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