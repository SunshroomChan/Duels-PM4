<?php

/**
 * Copyright 2018 GamakCZ
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace vixikhd\duels\arena;

use pocketmine\block\utils\SignText;
use pocketmine\color\Color;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\world\World;
use pocketmine\world\particle\{DustParticle, FlameParticle};
use pocketmine\world\Position;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\block\tile\Sign;
use Scoreboards\Scoreboards;
use vixikhd\duels\math\Time;
use vixikhd\duels\math\Vector3;
use vixikhd\duels\provider\lang\Lang;

/**
 * Class ArenaScheduler
 * @package duels\arena
 */
class ArenaScheduler extends Task
{

    /** @var int $startTime */
    public $startTime = 40;
    /** @var float|int $gameTime */
    public $gameTime = 20 * 60;
    /** @var int $restartTime */
    public $restartTime = 20;
    /** @var bool $forceStart */
    public $forceStart = false;
    /** @var bool $teleportPlayers */
    public $teleportPlayers = false;
    /** @var Arena $plugin */
    protected $plugin;
    /** @var array $signSettings */
    protected $signSettings;

    /**
     * ArenaScheduler constructor.
     * @param Arena $plugin
     */
    public function __construct(Arena $plugin)
    {
        $this->plugin = $plugin;
        $this->signSettings = $this->plugin->plugin->getConfig()->getAll()["joinsign"];
    }

    public function onRun() : void
    {
        $this->reloadSign();

        if ($this->plugin->setup) return;

        switch ($this->plugin->phase) {
            case Arena::PHASE_LOBBY:
                if (count($this->plugin->players) >= $this->plugin->data["pts"] || $this->forceStart) {
                    $this->startTime--;

                    if ($this->startTime == 10 && $this->teleportPlayers) {
                        $players = [];
                        foreach ($this->plugin->players as $player) {
                            $players[] = $player;
                        }

                        $this->plugin->players = [];

                        foreach ($players as $index => $player) {
                            $player->teleport(Position::fromObject(Vector3::fromString($this->plugin->data["spawns"]["spawn-" . (string)($index + 1)]), $this->plugin->world));
                            $player->getInventory()->removeItem(ItemFactory::getInstance()->get(ItemIds::PAPER));
                            $player->getCursorInventory()->removeItem(ItemFactory::getInstance()->get(ItemIds::PAPER));

                            $this->plugin->players["spawn-" . (string)($index + 1)] = $player;
                        }
                    }

                    foreach ($this->plugin->players as $player) {
                        if ($player instanceof Player) {
                            $time = $this->startTime;
                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Starting in §a: " . $time);
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");
                        }
                    }

                    if ($this->startTime == 10) {
                        if ($player instanceof Player) {
                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Starting in §a10");
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");
                        }
                    }

                    if ($this->startTime == 9) {
                        foreach ($this->plugin->players as $player) {
                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Starting in §a9");
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");
                        }
                    }

                    if ($this->startTime == 8) {
                        foreach ($this->plugin->players as $player) {
                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Starting in §a8");
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");
                        }
                    }

                    if ($this->startTime == 7) {
                        foreach ($this->plugin->players as $player) {
                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Starting in §a7");
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");
                        }
                    }

                    if ($this->startTime == 6) {
                        foreach ($this->plugin->players as $player) {
                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Starting in §a6");
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");
                        }
                    }

                    if ($this->startTime == 5) {
                        foreach ($this->plugin->players as $player) {
                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Starting in §a5");
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");

                            $player->sendTitle("§c5");
                        }
                    }

                    if ($this->startTime == 4) {
                        foreach ($this->plugin->players as $player) {
                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Starting in §a4");
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");

                            $player->sendTitle("§c4");
                        }
                    }

                    if ($this->startTime == 3) {
                        foreach ($this->plugin->players as $player) {
                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Starting in §a3");
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");

                            $player->sendTitle("§c3");
                        }
                    }

                    if ($this->startTime == 2) {
                        foreach ($this->plugin->players as $player) {
                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Starting in §a2");
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");

                            $player->sendTitle("§c2");
                        }
                    }

                    if ($this->startTime == 1) {
                        foreach ($this->plugin->players as $player) {
                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Starting in §a1");
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");

                            $player->sendTitle("§c1");
                        }
                    }

                    if ($this->startTime == 0) {
                        foreach ($this->plugin->players as $player) {
                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Game started");
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");
                        }
                    }

                    if ($this->startTime == 0) {
                        $this->plugin->startGame();
                    } else {
                    }
                } else {
                    if ($this->teleportPlayers && $this->startTime < $this->plugin->data["startTime"]) {
                        foreach ($this->plugin->players as $player) {
                            $player->teleport(Position::fromObject(Vector3::fromString($this->plugin->data["lobby"][0]), $this->plugin->plugin->getServer()->getWorldManager()->getWorldByName($this->plugin->data["lobby"][1])));

                            $api = Scoreboards::getInstance();
                            $api->new($player, "ObjectiveName", "§l§eDUELS");
                            $api->setLine($player, 1, "§7" . date("d/m/Y"));
                            $api->setLine($player, 2, "  ");
                            $api->setLine($player, 3, "Map: §a" . $this->plugin->world->getFolderName());
                            $api->setLine($player, 4, "   ");
                            $api->setLine($player, 5, "Players: §a" . count($this->plugin->players) . "/2");
                            $api->setLine($player, 6, "      ");
                            $api->setLine($player, 7, "Waiting for players");
                            $api->setLine($player, 8, "          ");
                            $api->setLine($player, 9, "§ewww.servername.com");
                        }
                    }

                    $this->startTime = $this->plugin->data["startTime"];
                }
                break;
            case Arena::PHASE_GAME:
                foreach ($this->plugin->players as $player) {
                    foreach ($player->getWorld()->getPlayers() as $opponents) {
                        $opponentsname = $opponents->getDisplayName();
                        $opponentshealt = $opponents->getHealth();
                        $time = Time::calculateTime($this->gameTime);
                        $api = Scoreboards::getInstance();
                        $api->new($player, "ObjectiveName", "§l§eDUELS");
                        $api->setLine($player, 1, "§7" . date("d/m/Y"));
                        $api->setLine($player, 2, " ");
                        $api->setLine($player, 3, "Time left: §a" . $time);
                        $api->setLine($player, 4, "   ");
                        $api->setLine($player, 5, "Map: §a" . $this->plugin->world->getFolderName());
                        $api->setLine($player, 6, "    ");
                        $api->setLine($player, 7, "Opponents: §a");
                        $api->setLine($player, 8, $opponentsname . " " . $opponentshealt);
                        $api->setLine($player, 9, "       ");
                        $api->setLine($player, 10, "§ewww.servername.com");
                    }
                }
                if ($this->plugin->checkEnd()) $this->plugin->startRestart();
                $this->gameTime--;
                break;
            case Arena::PHASE_RESTART:
                foreach (array_merge($this->plugin->players, $this->plugin->spectators) as $player) {
                    $time = Time::calculateTime($this->restartTime);
                    $api = Scoreboards::getInstance();
                    $api->new($player, "ObjectiveName", "§l§eDUELS");
                    $api->setLine($player, 1, "§7" . date("d/m/Y"));
                    $api->setLine($player, 2, " ");
                    $api->setLine($player, 3, "Time left: §a" . $time);
                    $api->setLine($player, 4, "   ");
                    $api->setLine($player, 5, "Map: §a" . $this->plugin->world->getFolderName());
                    $api->setLine($player, 6, "    ");
                    $api->setLine($player, 7, "Opponents: §a");
                    $api->setLine($player, 8, "Opponents not found");
                    $api->setLine($player, 9, "       ");
                    $api->setLine($player, 10, "§ewww.servername.com");
                }

                if($this->restartTime == 0) {
                    $api = Scoreboards::getInstance();
                    $api->remove($player);
                }

                foreach ($this->plugin->players as $player) {
                    #PARTICLES
                    $x = $player->getPosition()->getX();
                    $y = $player->getPosition()->getY();
                    $z = $player->getPosition()->getZ();
                    $red = new DustParticle(new Color(252, 17, 17));
                    $green = new DustParticle(new Color(102, 153, 102));
                    $flame = new FlameParticle();
                    $world = $player->getWorld();

                    foreach ([$red, $green, $flame] as $particle) {
                        $world->addParticle($particle);
                        $pos = $player->getPosition();
                        $red = new DustParticle(new Color(252, 17, 17));
                        $orange = new DustParticle(new Color(252, 135, 17));
                        $yellow = new DustParticle(new Color(252, 252, 17));
                        $green = new DustParticle(new Color(17, 252, 17));
                        $lblue = new DustParticle(new Color(94, 94, 252));
                        $dblue = new DustParticle(new Color(17, 17, 252));
                        foreach ([$red, $orange, $yellow, $green, $lblue, $dblue] as $particle) {
                            $pos->getWorld()->addParticle($particle);
                        }
                    }
                }
                switch ($this->restartTime) {
                    case 0:
                        foreach ($this->plugin->players as $player) {
                            $player->getEffects()->clear();
                            $this->plugin->disconnectPlayer($player, "", false, false, true);
                            $player->setAllowFlight(false);
                            $player->getServer()->dispatchCommand($player, "specter quit DuelsBot");
                        }
                        foreach ($this->plugin->spectators as $player) {
                            $player->removeAllEffects();
                            $this->plugin->disconnectPlayer($player, "", false, false, true);
                        }


                        break;
                    case -1:
                        $this->plugin->world = $this->plugin->mapReset->loadMap($this->plugin->world->getFolderName());
                        break;
                    case -6:
                        $this->plugin->loadArena(true);
                        $this->reloadTimer();
                        $this->plugin->phase = Arena::PHASE_LOBBY;
                        break;
                }
                $this->restartTime--;
                break;
        }
    }

    public function reloadSign()
    {
        if (!is_array($this->plugin->data["joinsign"]) || empty($this->plugin->data["joinsign"])) return;

        $signPos = Position::fromObject(Vector3::fromString($this->plugin->data["joinsign"][0]), $this->plugin->plugin->getServer()->getWorldManager()->getWorldByName($this->plugin->data["joinsign"][1]));

        if (!$signPos->getWorld() instanceof World) return;

        if ($signPos->getWorld()->getTile($signPos) === null) return;

        if (!$this->signSettings["custom"]) {
            $signText = new SignText([
                "§e§lDuels",
                "§9[ §b? / ? §9]",
                "§6Setup",
                "§6Wait few sec..."
            ]);


            if ($this->plugin->setup) {
                /** @var Sign $sign */
                $sign = $signPos->getWorld()->getTile($signPos);
                $sign->setText($signText);
                return;
            }

            $signText[1] = "§9[ §b" . count($this->plugin->players) . " / " . $this->plugin->data["slots"] . " §9]";

            switch ($this->plugin->phase) {
                case Arena::PHASE_LOBBY:
                    if (count($this->plugin->players) >= $this->plugin->data["slots"]) {
                        $signText[2] = "§6Full";
                        $signText[3] = "§8Map: §7{$this->plugin->world->getFolderName()}";
                    } else {
                        $signText[2] = "§aJoin";
                        $signText[3] = "§8Map: §7{$this->plugin->world->getFolderName()}";
                    }
                    break;
                case Arena::PHASE_GAME:
                    $signText[2] = "§5InGame";
                    $signText[3] = "§8Map: §7{$this->plugin->world->getFolderName()}";
                    break;
                case Arena::PHASE_RESTART:
                    $signText[2] = "§cRestarting...";
                    $signText[3] = "§8Map: §7{$this->plugin->world->getFolderName()}";
                    break;
            }

            /** @var Sign $sign */
            $sign = $signPos->getWorld()->getTile($signPos);
            $sign->setText($text);
        } else {
            $fix = function (string $text): string {
                $phase = $this->plugin->phase === 0 ? "Lobby" : ($this->plugin->phase === 1 ? "InGame" : "Restarting...");
                $map = ($this->plugin->world instanceof World) ? $this->plugin->world->getFolderName() : "---";
                $text = str_replace("%phase", $phase, $text);
                $text = str_replace("%ingame", count($this->plugin->players), $text);
                $text = str_replace("%max", $this->plugin->data["slots"], $text);
                $text = str_replace("%map", $map, $text);
                return $text;
            };

            $signText = [
                $fix($this->signSettings["format"]["line-1"]),
                $fix($this->signSettings["format"]["line-2"]),
                $fix($this->signSettings["format"]["line-3"]),
                $fix($this->signSettings["format"]["line-4"])
            ];

            /** @var Sign $sign */
            $sign = $signPos->getWorld()->getTile($signPos);
            $sign->setText($signText[0], $signText[1], $signText[2], $signText[3]);
        }
    }

    public function getCalculatedTimeByStart(): string
    {
        $time = 0;
        switch ($this->plugin->phase) {
            case 0:
                $time = $this->startTime - 10;
                break;
        }
        return Time::calculateTime($time);
    }

    public function reloadTimer()
    {
        $this->startTime = $this->plugin->data["startTime"];
        $this->gameTime = $this->plugin->data["gameTime"];
        $this->restartTime = $this->plugin->data["restartTime"];
        $this->forceStart = false;
    }

    /**
     * @return string
     */
    public function getCalculatedTimeByPhase(): string
    {
        $time = 0;
        switch ($this->plugin->phase) {
            case 0:
                $time = $this->startTime;
                break;
            case 1:
                $time = $this->gameTime;
                break;
            case 2:
                $time = $this->restartTime;
                break;
        }
        return Time::calculateTime($time);
    }
}