<?php

declare(strict_types=1);

namespace vixikhd\duels\event;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\plugin\PluginEvent;
use vixikhd\duels\arena\Arena;
use vixikhd\duels\Duels;

/**
 * Class PlayerArenaWinEvent
 * @package duels\event
 */
class PlayerArenaKillEvent extends PluginEvent
{

    /** @var null $handlerList */
    public static $handlerList = null;

    /** @var PlayerDeathEvent $player */
    protected $parent;

    /** @var Arena $arena */
    protected $arena;

    /**
     * PlayerArenaDeathEvent constructor.
     * @param Duels $plugin
     * @param PlayerDeathEvent $parent
     * @param Arena $arena
     */
    public function __construct(Duels $plugin, PlayerDeathEvent $parent, Arena $arena)
    {
        $this->parent = $parent;
        $this->arena = $arena;
        parent::__construct($plugin);
    }

    /**
     * @return PlayerDeathEvent $event
     */
    public function getEvent(): PlayerDeathEvent
    {
        return $this->parent;
    }

    /**
     * @return Arena $arena
     */
    public function getArena(): Arena
    {
        return $this->arena;
    }
}