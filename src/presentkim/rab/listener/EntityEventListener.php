<?php

namespace presentkim\rab\listener;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\utils\Config;
use presentkim\rab\RunnersAndBeast as Plugin;

class EntityEventListener implements Listener{

    /** @var Plugin */
    private $owner = null;

    public function __construct(){
        $this->owner = Plugin::getInstance();
    }

    public function onEntityLevelChangeEvent(EntityLevelChangeEvent $event) : void{
        $pl = $event->getEntity();
        if ($pl instanceof Player) {
            $lev = $event->getOrigin();
            if ($lev instanceof Level && in_array($lev->getFolderName(), $this->owner->arenas)) {
                $level = $lev->getFolderName();
                $slots = new Config($this->owner->getDataFolder() . "/slots.yml", Config::YAML);
                for ($i = 1; $i < 21; ++$i) {
                    if ($slots->get($key = "slot{$i}{$level}") === $pl->getName()) {
                        $slots->set("$key", 0);
                    }
                }
                $slots->save();
            }
        }
    }

    public function onEntityDamageEvent(EntityDamageEvent $event) : void{
        if ($event instanceof EntityDamageByEntityEvent) {
            $player = $event->getEntity();
            $level = $player->getLevel()->getFolderName();
            if (in_array($level, $this->owner->arenas)) {
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
}