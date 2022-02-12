<?php

declare(strict_types=1);

namespace vixikhd\duels\task;

use pocketmine\scheduler\Task;
use vixikhd\duels\arena\Arena;

class TOMBClearTask extends Task
{

    /** @var TombHuman $entity */
    private $entity;

    public function __construct(Arena $entity)
    {
        $this->entity = $entity;
    }

    public function onRun(int $tick) : void
    {
        $this->entity->flagForDespawn();
    }
}