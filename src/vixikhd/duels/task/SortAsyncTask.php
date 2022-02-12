<?php

declare(strict_types=1);

namespace vixikhd\duels\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use vixikhd\duels\Stats;

/**
 * Class SortAsyncTask
 * @package vixikhd\duels\task
 */
class SortAsyncTask extends AsyncTask
{

    /** @var string $toSort */
    public $toSort;

    /**
     * SortAsyncTask constructor.
     * @param array $toSort
     */
    public function __construct(array $toSort)
    {
        $this->toSort = serialize($toSort);
    }

    public function onRun() : void
    {
        $toSort = unserialize($this->toSort);
        $wins = [];
        $kills = [];
        foreach ($toSort as $player => [$kill, $win]) {
            $wins[$player] = $win;
            $kills[$player] = $kill;
        }

        arsort($wins);
        arsort($kills);
        $this->setResult([$kills, $wins]);
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server) : void
    {
        Stats::updateLeaderBoard($this, $this->getResult());
    }
}