<?php

declare(strict_types=1);

namespace vixikhd\duels\task;

use pocketmine\scheduler\Task;
use vixikhd\duels\Duels;

class UpdateTask extends Task
{

    /**
     * @var Duels $plugin
     */
    public $plugin;

    public function __construct(Duels $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onRun() : void
    {
    }

}
