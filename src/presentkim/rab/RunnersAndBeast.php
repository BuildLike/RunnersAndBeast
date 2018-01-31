<?php
/**
 * Plugin creado por KaitoDoDo
 *
 * Plugin modificado por Ulises Gamer
 * PD: no digo que este plugin sea mio, solo le agregue mas slots..
 */

namespace presentkim\rab;

use presentkim\rab\task\GameSendTask;
use presentkim\rab\task\RefreshSignTask;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as TE;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
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
use pocketmine\item\Item;
use pocketmine\event\entity\EntityLevelChangeEvent;

class RunnersAndBeast extends PluginBase implements Listener{

    private static $instance = null;

    public static $prefix = TE::GRAY . "[" . TE::GREEN . TE::BOLD . "Runners" . TE::AQUA . " & " . TE::RED . "Beast" . TE::RESET . TE::GRAY . "]";

    public static function getInstance() : self{
        return self::$instance;
    }

    public $mode = 0;

    public $arenas = [];

    public $currentLevel = "";

    public $op = [];

    public function onLoad() : void{
        if (self::$instance === null) {
            self::$instance = $this;
        }
    }

    public function onEnable() : void{
        $this->getLogger()->info(TE::GREEN . "Escape From The Beast Enable");

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if (class_exists(EconomyAPI::class)) {
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
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new GameSendTask($this), 20);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshSignTask($this), 20);
    }

    public function onDisable() : void{
        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
        $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
        if ($config->get("arenas") != null) {
            $this->arenas = $config->get("arenas");
        }
        foreach ($this->arenas as $arena) {
            for ($i = 1; $i < 21; ++$i) {
                $slots->set("slot{$i}{$arena}", 0);
            }
            $config->set($arena . "start", 0);
            $slots->save();
            $this->reload($arena);
        }
    }

    public function reload($lev) : void{
        if ($this->getServer()->isLevelLoaded($lev)) {
            $this->getServer()->unloadLevel($this->getServer()->getLevelByName($lev));
        }
        $zip = new \ZipArchive;
        $zip->open($this->getDataFolder() . 'arenas/' . $lev . '.zip');
        $zip->extractTo($this->getServer()->getDataPath() . 'worlds');
        $zip->close();
        unset($zip);
    }

    public function onDeath(PlayerDeathEvent $event) : void{
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

    public function chang($pl) : void{
        $level = $pl->getLevel()->getFolderName();
        if (in_array($level, $this->arenas)) {
            $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
            for ($i = 1; $i < 21; ++$i) {
                if ($slots->get($key = "slot{$i}{$level}") === $pl->getName()) {
                    $slots->set("$key", 0);
                }
            }
            $slots->save();
        }
    }

    public function enCambioMundo(EntityLevelChangeEvent $event) : void{
        $pl = $event->getEntity();
        if ($pl instanceof Player) {
            $lev = $event->getOrigin();
            if ($lev instanceof Level && in_array($lev->getFolderName(), $this->arenas)) {
                $level = $lev->getFolderName();
                $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
                for ($i = 1; $i < 21; ++$i) {
                    if ($slots->get($key = "slot{$i}{$level}") === $pl->getName()) {
                        $slots->set("$key", 0);
                    }
                }
                $slots->save();
            }
        }
    }

    public function onLog(PlayerLoginEvent $event) : void{
        $player = $event->getPlayer();
        if (in_array($player->getLevel()->getFolderName(), $this->arenas)) {
            $player->getInventory()->clearAll();
            $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
            $this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
            $player->teleport($spawn, 0, 0);
        }
    }

    public function onQuit(PlayerQuitEvent $event) : void{
        $pl = $event->getPlayer();
        $level = $pl->getLevel()->getFolderName();
        if (in_array($level, $this->arenas)) {
            $pl->removeAllEffects();
            $pl->getInventory()->clearAll();
            $pl->setNameTag($pl->getName());
            $this->chang($pl);
        }
    }

    public function onBlockBr(BlockBreakEvent $event) : void{
        $player = $event->getPlayer();
        $level = $player->getLevel()->getFolderName();
        if (in_array($level, $this->arenas)) {
            $event->setCancelled();
        }
    }

    public function onBlockPl(BlockPlaceEvent $event) : void{
        $player = $event->getPlayer();
        $level = $player->getLevel()->getFolderName();
        if (in_array($level, $this->arenas)) {
            $event->setCancelled();
        }
    }

    public function onDam(EntityDamageEvent $event) : void{
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

    public function onCommand(CommandSender $player, Command $cmd, $label, array $args) : bool{
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
                            $player->sendMessage(self::$prefix . "Touch spawn of runners!");
                            $player->setGamemode(1);
                            array_push($this->op, $player->getName());
                            $player->teleport($this->getServer()->getLevelByName($args[1])->getSafeSpawn(), 0, 0);
                            $name = $args[1];
                            $this->zipper($player, $name);
                        } else {
                            $player->sendMessage(self::$prefix . "ERROR missing world.");
                        }
                    } else {
                        $player->sendMessage(self::$prefix . "ERROR missing parameters.");
                    }
                } elseif ($args[0] == "start") {
                    if (!empty($args[1])) {
                        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                        if ($config->get($args[1] . "StartTime") != null) {
                            $config->set($args[1] . "StartTime", 5);
                            $config->save();
                            $player->sendMessage(self::$prefix . "§aStarting i 5...");
                        }
                    } else {
                        $level = $player->getLevel()->getFolderName();
                        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                        if ($config->get($level . "StartTime") != null) {
                            $config->set($level . "StartTime", 5);
                            $config->save();
                            $player->sendMessage(self::$prefix . "§cStarting i 5...");
                        }
                    }
                } else {
                    $player->sendMessage(self::$prefix . " §bEscape From The Beast Commands!");
                    $player->sendMessage(self::$prefix . " §b/edb make [world]: Create the EDLB game!");
                }
            }
        }
        return true;
    }

    public function onInteract(PlayerInteractEvent $event) : void{
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $tile = $player->getLevel()->getTile($block);

        if ($tile instanceof Sign) {
            if (($this->mode == 26) && (in_array($player->getName(), $this->op))) {
                $tile->setText(TE::AQUA . "[Join]", TE::GREEN . "0 / 20", "§f" . $this->currentLevel, self::$prefix);
                $this->refreshArenas();
                $this->currentLevel = "";
                $this->mode = 0;
                $player->sendMessage(self::$prefix . "Arena Registered!");
                array_shift($this->op);
            } else {
                $text = $tile->getText();
                if ($text[3] == self::$prefix) {
                    if ($text[0] == TE::AQUA . "[Join]") {
                        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                        $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
                        $namemap = str_replace("§f", "", $text[2]);
                        $level = $this->getServer()->getLevelByName($namemap);
                        if (strpos($player->getNameTag(), "§c(Beast)") !== false) {
                            $team = TE::RED . "Beast";
                            if ($slots->get("slot6" . $namemap) == null) {
                                $thespawn = $config->get($namemap . "Spawn2");
                                $slots->set("slot6" . $namemap, $player->getName());
                            } else {
                                $player->sendMessage(self::$prefix . TE::RED . "There is already Beast in this game.");
                                goto noequip;
                            }
                        } elseif ($slots->get("slot1" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot1" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot2" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot2" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot3" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot3" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot4" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot4" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot5" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot5" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot6" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn2");
                            $slots->set("slot6" . $namemap, $player->getName());
                            $player->setNameTag("§c(Beast)" . TE::GOLD . $player->getName());
                            $team = TE::RED . "Beast";
                        } elseif ($slots->get("slot7" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot7" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot8" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot8" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot9" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot9" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot10" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot10" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot11" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn2");
                            $slots->set("slot11" . $namemap, $player->getName());
                            $player->setNameTag("§c(Beast)" . TE::GREEN . $player->getName());
                            $team = TE::RED . "Beast";
                        } elseif ($slots->get("slot12" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot12" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot13" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot13" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot14" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot14" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot15" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot15" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot16" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn2");
                            $slots->set("slot16" . $namemap, $player->getName());
                            $player->setNameTag("§c(Beast)" . TE::GOLD . $player->getName());
                            $team = TE::RED . "Beast";
                        } elseif ($slots->get("slot17" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot17" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot18" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot18" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot19" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn1");
                            $slots->set("slot19" . $namemap, $player->getName());
                            $player->setNameTag("§b(Runner)" . TE::GREEN . $player->getName());
                            $team = TE::AQUA . "Runner";
                        } elseif ($slots->get("slot20" . $namemap) == null) {
                            $thespawn = $config->get($namemap . "Spawn2");
                            $slots->set("slot20" . $namemap, $player->getName());
                            $player->setNameTag("§c(Beast)" . TE::GREEN . $player->getName());
                            $team = TE::RED . "Beast";
                        } else {
                            $player->sendMessage(self::$prefix . TE::RED . "No places available.");
                            goto noequip;
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
                        if (strpos($player->getNameTag(), "§c(Beast)") !== false) {
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
                        $player->sendMessage(self::$prefix . "-[runner|beast]-    you are a " . $team);
                        foreach ($level->getPlayers() as $playersinarena) {
                            $playersinarena->sendMessage($player->getNameTag() . " §f joined");
                        }
                        noequip:
                    } else {
                        $player->sendMessage(self::$prefix . "you cant join now");
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
            $player->sendMessage(self::$prefix . "Spawn Runneres registered!");
            $this->mode++;
            $config->save();
        } elseif (in_array($player->getName(), $this->op) && $this->mode == 2) {
            $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
            $config->set($this->currentLevel . "Spawn" . $this->mode, [
              $block->getX(),
              $block->getY() + 1,
              $block->getZ(),
            ]);
            $player->sendMessage(self::$prefix . "Spawn Beast registered!");
            $config->set("arenas", $this->arenas);
            $config->set($this->currentLevel . "start", 0);
            $player->sendMessage(self::$prefix . "Touch a sign to register Arena!");
            $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
            $this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
            $player->teleport($spawn, 0, 0);
            $config->save();
            $this->mode = 26;
        }
    }

    public function refreshArenas() : void{
        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
        $config->set("arenas", $this->arenas);
        foreach ($this->arenas as $arena) {
            $config->set($arena . "PlayTime", 515);
            $config->set($arena . "StartTime", 50);
        }
        $config->save();
    }

    public function zipper($player, $name) : void{
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