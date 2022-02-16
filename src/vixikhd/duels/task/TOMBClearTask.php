<?php

declare(strict_types=1);

namespace vixikhd\duels\task;

use pocketmine\scheduler\Task;
use vixikhd\duels\arena\Arena;
use vixikhd\duels\entity\TOMBHuman;

class TOMBClearTask extends Task
{

    /** @var TOMBHuman $entity */
    private $entity;

    public function __construct(Arena $entity)
    {
        $this->entity = $entity;
    }

    public function onRun() : void
    {
        $this->entity->flagForDespawn();
    }
}