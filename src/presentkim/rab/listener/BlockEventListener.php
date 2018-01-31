<?php

namespace presentkim\rab\listener;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use presentkim\rab\RunnersAndBeast as Plugin;

class BlockEventListener implements Listener{

    /** @var Plugin */
    private $owner = null;

    public function __construct(){
        $this->owner = Plugin::getInstance();
    }

    public function onBlockBreakEvent(BlockBreakEvent $event) : void{
        $player = $event->getPlayer();
        $level = $player->getLevel()->getFolderName();
        if (in_array($level, $this->owner->arenas)) {
            $event->setCancelled();
        }
    }

    public function onBlockPlaceEvent(BlockPlaceEvent $event) : void{
        $player = $event->getPlayer();
        $level = $player->getLevel()->getFolderName();
        if (in_array($level, $this->owner->arenas)) {
            $event->setCancelled();
        }
    }
}