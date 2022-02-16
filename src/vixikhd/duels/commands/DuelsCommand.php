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

namespace vixikhd\duels\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use pocketmine\item\WrittenBook;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;
use vixikhd\duels\arena\Arena;
use vixikhd\duels\Duels;

/**
 * Class DuelsCommand
 * @package duels\commands
 */
class DuelsCommand extends Command implements PluginOwned
{

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */

    public function __construct()
    {
        parent::__construct("dl", "Duels Command", "§cUse /duels help or /dl help to see list of commands!", ["dl"]);
        $this->setPermission("duels.cmd");
    }

    /** var Duels $plugin */
     public $plugin;

    public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
        if(!$sender instanceof Player) {
            $sender->sendMessage("You can run this command as a player!");
            return;
        }

        switch ($args[0] ?? "") {
            case "debug":
                foreach ($this->plugin->arenas as $arena) {
                    $sender->sendMessage("{$arena->data["level"]} - " . implode(", ", array_keys($arena->players)));
                }
                break;
            case "help":
                if (!$sender->hasPermission("duels.cmd.help")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                $sender->sendMessage("§a> Duels commands list (1/3):\n" .
                    "§7/duels help : Displays list of Duels commands\n" .
                    "§7/duels create : Create Duels arena\n" .
                    "§7/duels remove : Remove Duels arena\n" .
                    "§7/duels set : Set Duels arena\n" .
                    "§7/duels arenas : Displays list of arenas\n" .
                    "§7/duels start : Force start game\n" .
                    "§7/duels join : Join to arena\n" .
                    "§7/duels random : Joins player to random arena\n" .
                    "§7/duels leave : Leave the arena");
                break;
            case "create":
                if (!$sender->hasPermission("duels.cmd.create")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                if (!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7/sw create <arenaName>");
                    break;
                }
                if (isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§c> Arena $args[1] already exists!");
                    break;
                }
                $this->plugin->arenas[$args[1]] = new Arena($this->plugin, []);
                $sender->sendMessage("§a> Arena $args[1] created!");
                break;
            case "remove":
                if (!$sender->hasPermission("duels.cmd.remove")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                if (!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7/duels remove <arenaName>");
                    break;
                }
                if (!isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§c> Arena $args[1] was not found!");
                    break;
                }

                /** @var Arena $arena */
                $arena = $this->plugin->arenas[$args[1]];

                foreach ($arena->players as $player) {
                    $player->teleport($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
                }

                if (is_file($file = $this->plugin->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . $args[1] . ".yml")) unlink($file);
                unset($this->plugin->arenas[$args[1]]);

                $sender->sendMessage("§a> Arena removed!");
                break;
            case "set":
                if (!$sender->hasPermission("duels.cmd.set")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                if (!$sender instanceof Player) {
                    $sender->sendMessage("§c> This command can be used only in-game!");
                    break;
                }
                if (!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7/duels set <arenaName|all> OR §7/duels set <arenaName1,arenaName2,...>");
                    break;
                }
                if (isset($this->plugin->setters[$sender->getName()])) {
                    $sender->sendMessage("§c> You are already in setup mode!");
                    break;
                }

                /** @var Arena[] $arenas */
                $arenas = [];

                if (isset($this->plugin->arenas[$args[1]])) {
                    $arenas[] = $this->plugin->arenas[$args[1]];
                }

                if (count($targetArenas = explode(",", $args[1])) > 1) {
                    foreach ($targetArenas as $arena) {
                        if (isset($this->plugin->arenas[$arena])) {
                            $arenas[] = $this->plugin->arenas[$arena];
                        }
                    }
                }

                if ($args[1] == "all") {
                    $arenas = array_values($this->plugin->arenas);
                }

                if (count($arenas) === 0) {
                    $sender->sendMessage("§c> Arena wasn't found.");
                    break;
                }

                $target = count($arenas) > 1 ? $arenas : $arenas[0];

                $sender->sendMessage("§a> You've joined setup mode.\n" .
                    "§7- use §lhelp §r§7to display available commands\n" .
                    "§7- or §ldone §r§7to leave setup mode");

                $this->plugin->setters[$sender->getName()] = $target;
                break;
            case "start":
                if (!$sender->hasPermission("duels.cmd.start")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                /** @var Arena $arena */
                $arena = null;

                if (isset($args[1])) {
                    if (!isset($this->plugin->arenas[$args[1]])) {
                        $sender->sendMessage("§c> Arena $args[1] was not found!");
                        break;
                    }
                    $arena = $this->plugin->arenas[$args[1]];
                }

                if ($arena == null && $sender instanceof Player) {
                    foreach ($this->plugin->arenas as $arenas) {
                        if ($arenas->inGame($sender)) {
                            $arena = $arenas;
                        }
                    }
                } else {
                    $sender->sendMessage("§cUsage: §7/duels start <arena>");
                    break;
                }

                $arena->scheduler->forceStart = true;
                $arena->scheduler->startTime = 20;

                $sender->sendMessage("§a> Arena starts in 10 sec!");
                break;
            case "win":
                if (!$sender->hasPermission("duels.cmd.win")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                if (!$sender instanceof Player) {
                    $sender->sendMessage("§cThis command can be used only in-game!");
                    break;
                }
                $sender->getServer()->dispatchCommand($sender, "slapper spawn human duelswin");
                $sender->sendMessage(C::GREEN . "LeaderBoards spawn coordinates set in your location, please re-login...");
                break;
            case "test":
                if (!$sender->hasPermission("duels.cmd.test")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                if (!$sender instanceof Player) {
                    $sender->sendMessage("§cThis command can be used only in-game!");
                    break;
                }
                $sender->getServer()->dispatchCommand($sender, "specter spawn ZAlphaGanz");
                $sender->getServer()->dispatchCommand($sender, "specter chat ZAlphaGanz /duels random");
                $sender->getServer()->dispatchCommand($sender, "duels random");
                break;
            case "arenas":
                if (!$sender->hasPermission("duels.cmd.arenas")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                if (count($this->plugin->arenas) === 0) {
                    $sender->sendMessage("§6> There are 0 arenas.");
                    break;
                }
                $list = "§7> Arenas:\n";
                foreach ($this->plugin->arenas as $name => $arena) {
                    if ($arena->setup) {
                        $list .= "§7- $name : §cdisabled\n";
                    } else {
                        $list .= "§7- $name : §aenabled\n";
                    }
                }
                $sender->sendMessage($list);
                break;
            case "leave":
                if (!$sender->hasPermission("duels.cmd.leave")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }

                if (!$sender instanceof Player) {
                    $sender->sendMessage("§c> This command can be used only in-game!");
                    break;
                }

                $arena = null;
                foreach ($this->plugin->arenas as $arenas) {
                    if ($arenas->inGame($sender)) {
                        $arena = $arenas;
                    }
                }

                if (is_null($arena)) {
                    $sender->sendMessage("§cArena not found.");
                    break;
                }

                $arena->disconnectPlayer($sender, "§a> You have successfully left the arena.", false, false, true);
                break;
            case "join":
                if (!$sender->hasPermission("duels.cmd.join")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }

                if (!$sender instanceof Player) {
                    $sender->sendMessage("§c> This command can be used only in-game!");
                    break;
                }

                if (!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7/duels join <arenaName>");
                    break;
                }

                if (!isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§cArena {$args[1]} not found.");
                    break;
                }

                $this->plugin->arenas[$args[1]]->joinToArena($sender);
                break;
            case "random":
                if (!$sender->hasPermission("duels.cmd.random")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }

                if (!$sender instanceof Player) {
                    $sender->sendMessage("§c> This command can be used only in-game!");
                    break;
                }

                $arena = $this->plugin->emptyArenaChooser->getRandomArena();

                if ($arena === null) {
                    $sender->sendMessage("§a> All the arenas are full!");
                    break;
                }
                $arena->joinToArena($sender);
                break;
            default:
                if ($sender->hasPermission("duels.cmd")) {
                    $sender->sendMessage("§cUsage: §7/duels help");
                    break;
                }
                $sender->sendMessage("§cYou have not permissions to use this command!");
        }

    }

    /**
     * @return Duels|Plugin $duels
     */
    public function getPlugin(): Plugin
    {
        return Duels::getInstance();
    }

    public function getOwningPlugin(): Plugin {
        return Duels::getInstance();
    }

}
