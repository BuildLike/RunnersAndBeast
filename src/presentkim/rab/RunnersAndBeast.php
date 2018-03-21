<?php
/**
 * Plugin creado por KaitoDoDo
 *
 * Plugin modificado por Ulises Gamer
 * PD: no digo que este plugin sea mio, solo le agregue mas slots..
 */

namespace presentkim\rab;

use presentkim\rab\listener\BlockEventListener;
use presentkim\rab\listener\EntityEventListener;
use presentkim\rab\listener\PlayerEventListener;
use presentkim\rab\task\GameSendTask;
use presentkim\rab\task\RefreshSignTask;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as TE;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\player\PlayerLoginEvent;
use onebone\economyapi\EconomyAPI;

class RunnersAndBeast extends PluginBase{

    private static $instance = null;

    public static $prefix = TE::GRAY . "[" . TE::GREEN . TE::BOLD . "몰" . TE::AQUA . " 라 " . TE::RED . "" . TE::RESET . TE::GRAY . "]";

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

        $this->getServer()->getPluginManager()->registerEvents(new BlockEventListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityEventListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener(), $this);
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


    public function onLog(PlayerLoginEvent $event) : void{
        $player = $event->getPlayer();
        if (in_array($player->getLevel()->getFolderName(), $this->arenas)) {
            $player->getInventory()->clearAll();
            $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
            $this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
            $player->teleport($spawn, 0, 0);
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
                            $player->addTitle(self::$prefix . "§a5");
                        }
                    } else {
                        $level = $player->getLevel()->getFolderName();
                        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                        if ($config->get($level . "StartTime") != null) {
                            $config->set($level . "StartTime", 5);
                            $config->save();
                            $player->addTitle(self::$prefix . "§a5");
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
