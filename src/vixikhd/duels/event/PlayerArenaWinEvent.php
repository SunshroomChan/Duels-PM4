<?php

declare(strict_types=1);

namespace vixikhd\duels\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\player\Player;
use vixikhd\duels\arena\Arena;
use vixikhd\duels\Duels;

/**
 * Class PlayerArenaWinEvent
 * @package duels\event
 */
class PlayerArenaWinEvent extends PluginEvent
{

    /** @var null $handlerList */
    public static $handlerList = null;

    /** @var Player $player */
    protected $player;

    /** @var Arena $arena */
    protected $arena;

    /**
     * PlayerArenaWinEvent constructor.
     * @param Duels $plugin
     * @param Player $player
     * @param Arena $arena
     */
    public function __construct(Duels $plugin, Player $player, Arena $arena)
    {
        $this->player = $player;
        $this->arena = $arena;
        parent::__construct($plugin);
    }

    /**
     * @return Player $arena
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return Arena $arena
     */
    public function getArena(): Arena
    {
        return $this->arena;
    }
}