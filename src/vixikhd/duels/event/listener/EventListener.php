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

namespace vixikhd\duels\event\listener;

use pocketmine\block\BlockLegacyIds;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use vixikhd\duels\event\PlayerArenaWinEvent;
use vixikhd\duels\Duels;

/**
 * Class EventListener
 * @package duels\event\listener
 */
class EventListener implements Listener
{

    /** @var Duels $plugin */
    public $plugin;

    /**
     * EventListener constructor.
     * @param Duels $plugin
     */
    public function __construct(Duels $plugin)
    {
        $this->plugin = $plugin;
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }

    /**
     * @param PlayerMoveEvent $event
     */
    public function onMove(PlayerMoveEvent $event)
    {
        $endPortal = (bool)$this->plugin->dataProvider->config["portals"]["ender"]["enabled"];
        $netherPortal = (bool)$this->plugin->dataProvider->config["portals"]["nether"]["enabled"];

        $player = $event->getPlayer();

        if ($endPortal) {
            if ($player->getWorld()->getBlock($player->getPosition()->asVector3())->getId() === BlockLegacyIds::END_PORTAL && in_array($player->getWorld()->getFolderName(), $this->plugin->dataProvider->config["portals"]["ender"]["worlds"])) {
                $chooser = $this->plugin->emptyArenaChooser;
                $inGame = false;
                foreach ($this->plugin->arenas as $arena) {
                    $inGame = $arena->inGame($player, true);
                }
                if ($inGame) return;
                $player->sendMessage("§6> Searching for empty arena...");
                if (($arena = $chooser->getRandomArena()) !== null) {
                    $arena->joinToArena($player);
                } else {
                    $player->sendMessage("§c> All arenas are in game.");
                }
            }
        }
        if ($netherPortal) {
            if ($player->getWorld()->getBlock($player->getPosition()->asVector3())->getId() === BlockLegacyIds::PORTAL && in_array($player->getWorld()->getFolderName(), $this->plugin->dataProvider->config["portals"]["nether"]["worlds"])) {
                $chooser = $this->plugin->emptyArenaChooser;
                $inGame = false;
                $ar = null;
                foreach ($this->plugin->arenas as $arena) {
                    $inGame = $arena->inGame($player, true);
                    $ar = $arena;
                }
                if ($inGame) $ar->disconnectPlayer($player);
                $player->sendMessage("§6> Searching for empty arena...");
                if (($arena = $chooser->getRandomArena()) !== null) {
                    $arena->joinToArena($player);
                } else {
                    $player->sendMessage("§c> All arenas are in game.");
                }
            }
        }
    }

    public function onBreak(BlockBreakEvent $event)
    {
        $item = $event->getPlayer()->getInventory()->getItemInHand();
        if ($item->hasEnchantment(VanillaEnchantments::SILK_TOUCH())) {
            $event->setDrops([ItemFactory::getInstance()->get($event->getBlock()->getId(), $event->getBlock()->getDamage(), 1)]);
        }
    }

    /**
     * @param PlayerArenaWinEvent $event
     */
    public function onWin(PlayerArenaWinEvent $event)
    {
        $player = $event->getPlayer();
    }

    /**
     * @param PlayerCommandPreprocessEvent $event
     */
    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event)
    {
        $msg = $event->getMessage();
        $player = $event->getPlayer();

        $inGame = false;
        foreach ($this->plugin->arenas as $arena) {
            $inGame = $arena->inGame($player) || $inGame;
        }

        if (!$inGame) return;

        $cmd = explode(" ", $msg)[0];

        if (in_array($cmd, $this->plugin->dataProvider->config["banned-commands"])) {
            $player->sendMessage("§c> This command is banned in Duels game!");
            $event->cancel();
        }
    }
}