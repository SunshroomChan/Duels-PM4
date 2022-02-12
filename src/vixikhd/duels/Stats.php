<?php

declare(strict_types=1);

namespace vixikhd\duels;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use vixikhd\duels\command\DuelsBaseCommand;
use vixikhd\duels\task\SortAsyncTask;

/**
 * Class Stats
 * @package vixikhd\skywars
 */
class Stats
{

    public const KILL = 0;
    public const WIN = 1;

    /** @var array $players */
    protected static $players = [];

    /** @var array $leaderboard */
    protected static $leaderBoard = [
        self::KILL => [],
        self::WIN => []
    ];

    public static function init()
    {
        self::$players = Duels::getInstance()->dataProvider->getStats();

        if (isset(Duels::getInstance()->dataProvider->config["scoreboards"]) && Duels::getInstance()->dataProvider->config["scoreboards"]["enabled"]) {
            Duels::getInstance()->getScheduler()->scheduleRepeatingTask(new class extends Task {
                public function onRun(int $currentTick) : void
                {
                    Server::getInstance()->getAsyncPool()->submitTask(new SortAsyncTask(Stats::getAll()));
                }
            }, 20 * 60 * 5);
        }
    }

    /**
     * @return array
     */
    public static function getAll()
    {
        return self::$players;
    }

    public static function save()
    {
        Duels::getInstance()->dataProvider->saveStats(self::$players);
    }

    /**
     * @param Player $player
     */
    public static function addKill(Player $player)
    {
        if (!isset(self::$players[$player->getName()])) {
            self::$players[$player->getName()] = [
                self::KILL => 0,
                self::WIN => 0
            ];
        }

        self::$players[$player->getName()][self::KILL] += 1;
    }

    /**
     * @param Player $player
     */
    public static function addWin(Player $player)
    {
        if (!isset(self::$players[$player->getName()])) {
            self::$players[$player->getName()] = [
                self::KILL => 0,
                self::WIN => 0
            ];
        }

        self::$players[$player->getName()][self::WIN] += 1;
    }

    /**
     * @param int $count
     * @param int $sort
     * @return array
     */
    public static function getTopPlayers(int $count, int $sort = self::KILL)
    {
        $topPlayers = [];
        $leaderBoard = self::$leaderBoard[$sort];
        $names = array_keys($leaderBoard);
        for ($x = 0; $x < $count; $x++) {
            $name = array_shift($names);
            $score = array_shift($leaderBoard);
            $topPlayers[$name] = $score;
        }
        return $topPlayers;
    }
}