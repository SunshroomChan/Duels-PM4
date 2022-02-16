<?php

declare(strict_types=1);

namespace vixikhd\duels;

use libs\xenialdan\apibossbar\BossBar;
use pocketmine\command\Command;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\world\World;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\utils\Config;
use slapper\events\SlapperCreationEvent;
use vixikhd\duels\arena\Arena;
use libs\xenialdan\apibossbar\DiverseBossBar;
use vixikhd\duels\arena\object\EmptyArenaChooser;
use vixikhd\duels\commands\DuelsCommand;
use vixikhd\duels\event\listener\EventListener;
use vixikhd\duels\math\Vector3;
use vixikhd\duels\provider\DataProvider;
use vixikhd\duels\provider\JsonDataProvider;
use vixikhd\duels\provider\MySQLDataProvider;
use vixikhd\duels\provider\SQLiteDataProvider;
use vixikhd\duels\provider\YamlDataProvider;
use vixikhd\duels\task\EntityJoinTask;
use vixikhd\duels\task\UpdateTask;
use vixikhd\duels\utils\ServerManager;

/**
 * Class Duels
 *
 * @package duels
 *
 * @version 1.0.0
 * @author VixikCZ gamak.mcpe@gmail.com
 * @copyright 2017-2020 (c)
 */
class Duels extends PluginBase implements Listener
{

    /** @var Duels $instance */
    private static $instance;

    /** @var DataProvider $dataProvider */
    public $dataProvider = null;

    /** @var EmptyArenaChooser $emptyArenaChooser */
    public $emptyArenaChooser = null;

    /** @var EventListener $eventListener */
    public $eventListener;

    /** @var Command[] $commands */
    public $commands = [];

    /** @var Arena[] $arenas */
    public $arenas = [];

    /** @var Arena[]|Arena[][] $setters */
    public $setters = [];

    /** @var int[] $setupData */
    public $setupData = [];

    /**
     * @return Duels
     */
    public static function getInstance(): Duels
    {
        return self::$instance;
    }

    public function onEnable() : void
    {
        $restart = (bool)(self::$instance instanceof $this);
        if (!$restart) {
            self::$instance = $this;
        } else {
            $this->getLogger()->notice("We'd recommend to restart server insteadof reloading. Reload can cause bugs.");
        }

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "lb");
        $this->saveResource("winlb.yml");
        if (is_file($file = $this->getDataFolder() . DIRECTORY_SEPARATOR . "config.yml")) {
            $config = new Config($file, Config::YAML);
            switch (strtolower($config->get("dataProvider"))) {
                case "json":
                    $this->dataProvider = new JsonDataProvider($this);
                    break;
                case "sqlite":
                    $this->dataProvider = new SQLiteDataProvider($this);
                    break;
                case "mysql":
                    $this->dataProvider = new MySQLDataProvider($this);
                    break;
                default:
                    $this->dataProvider = new YamlDataProvider($this);
                    break;
            }
        } else {
            $this->dataProvider = new YamlDataProvider($this);
        }
        Stats::init();

        $this->emptyArenaChooser = new EmptyArenaChooser($this);
        $this->eventListener = new EventListener($this);
        $this->bossbar = new BossBar();

        $this->getServer()->getCommandMap()->register("duels", $this->commands[] = new DuelsCommand($this));

        $this->getScheduler()->scheduleRepeatingTask(new UpdateTask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new EntityJoinTask($this), 20);
    }

    public function onDisable() : void
    {
        $this->dataProvider->save();
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        if ($this->dataProvider->config["waterdog"]["enabled"]) {
            $event->setJoinMessage("");
            $player = $event->getPlayer();

            $arena = $this->emptyArenaChooser->getRandomArena();
            if ($arena === null) {
                kick:
                ServerManager::transferPlayer($player, $this->dataProvider->config["waterdog"]["lobbyServer"]);
                return;
            }

            $joined = $arena->joinToArena($player);
            if ($joined === false) {
                goto kick;
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event)
    {
        if ($event->getAction() === $event::RIGHT_CLICK_BLOCK && $event->getItem() instanceof Armor) {
            switch (true) {
                case in_array($event->getItem()->getId(), [ItemIds::LEATHER_HELMET, ItemIds::IRON_HELMET, ItemIds::GOLD_HELMET, ItemIds::DIAMOND_HELMET, ItemIds::CHAIN_HELMET]):
                    $tempItem = $event->getItem();
                    $armorItem = $event->getPlayer()->getArmorInventory()->getHelmet();

                    $event->getPlayer()->getArmorInventory()->setHelmet($tempItem);
                    $event->getPlayer()->getInventory()->setItemInHand($armorItem);
                    break;
                case in_array($event->getItem()->getId(), [ItemIds::LEATHER_CHESTPLATE, ItemIds::IRON_CHESTPLATE, ItemIds::GOLD_CHESTPLATE, ItemIds::DIAMOND_CHESTPLATE, ItemIds::CHAIN_CHESTPLATE]):
                    $tempItem = $event->getItem();
                    $armorItem = $event->getPlayer()->getArmorInventory()->getChestplate();

                    $event->getPlayer()->getArmorInventory()->setChestplate($tempItem);
                    $event->getPlayer()->getInventory()->setItemInHand($armorItem);
                    break;
                case in_array($event->getItem()->getId(), [ItemIds::LEATHER_LEGGINGS, ItemIds::IRON_LEGGINGS, ItemIds::GOLD_LEGGINGS, ItemIds::DIAMOND_LEGGINGS, ItemIds::CHAIN_LEGGINGS]):
                    $tempItem = $event->getItem();
                    $armorItem = $event->getPlayer()->getArmorInventory()->getLeggings();

                    $event->getPlayer()->getArmorInventory()->setLeggings($tempItem);
                    $event->getPlayer()->getInventory()->setItemInHand($armorItem);
                    break;
                case in_array($event->getItem()->getId(), [ItemIds::LEATHER_BOOTS, ItemIds::IRON_BOOTS, ItemIds::GOLD_BOOTS, ItemIds::DIAMOND_BOOTS, ItemIds::CHAIN_BOOTS]):
                    $tempItem = $event->getItem();
                    $armorItem = $event->getPlayer()->getArmorInventory()->getBoots();

                    $event->getPlayer()->getArmorInventory()->setBoots($tempItem);
                    $event->getPlayer()->getInventory()->setItemInHand($armorItem);
                    break;
            }
        }
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();

        if (!isset($this->setters[$player->getName()])) {
            return;
        }

        $event->cancel();
        $args = explode(" ", $event->getMessage());

        /** @var Arena $arena */
        $arena = $this->setters[$player->getName()];
        /** @var Arena[] $arenas */
        $arenas = is_array($this->setters[$player->getName()]) ? $this->setters[$player->getName()] : [$this->setters[$player->getName()]];

        switch ($args[0]) {
            case "help":
                if (!isset($args[1]) || $args[1] == "1") {
                    $player->sendMessage("§a> Duels setup help (1/3):\n" .
                        "§7help : Displays list of available setup commands\n" .
                        "§7level : Set arena level\n" .
                        "§7spawn : Set arena spawns\n" .
                        "§7lobby : Set arena lobby\n" .
                        "§7joinsign : Set arena joinsign\n" .
                        "§7leavepos : Sets position to leave arena\n".
                        "§7enable : Enable to arena\n".
                        "§7done : Leave setup mode\n");
                }
                break;
            case "level":
                if (is_array($arena)) {
                    $player->sendMessage("§c> Level must be different for each arena.");
                    break;
                }
                if (!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7level <levelName>");
                    break;
                }
                if (!$this->getServer()->getWorldManager()->isWorldGenerated($args[1])) {
                    $player->sendMessage("§c> Level $args[1] does not found!");
                    break;
                }
                $player->sendMessage("§a> Arena level updated to $args[1]!");

                foreach ($arenas as $arena) {
                    $arena->data["level"] = $args[1];
                    $arena->data["slots"] = 2;
                    $arena->data["startTime"] = 40;
                    if ($arena->setup) $arena->scheduler->startTime = 40;
                    $arena->data["gameTime"] = 1200;
                    if ($arena->setup) $arena->scheduler->gameTime = 1200;
                    $arena->data["restartTime"] = 20;
                    if ($arena->setup) $arena->scheduler->restartTime = 20;
                    $arena->data["pts"] = 2;
                }
                break;
            case "spawn":
                if (is_array($arena)) {
                    $player->sendMessage("§c> Spawns are different for each arena.");
                    break;
                }

                if (!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7setspawn <int: spawn>");
                    break;
                }

                if (!is_numeric($args[1])) {
                    $player->sendMessage("§cType number!");
                    break;
                }

                if ((int)$args[1] > $arena->data["slots"]) {
                    $player->sendMessage("§cThere are only {$arena->data["slots"]} slots!");
                    break;
                }

                $arena->data["spawns"]["spawn-{$args[1]}"] = (new Vector3((int)$player->getPosition()->getX(), (int)$player->getPosition()->getY(), (int)$player->getPosition()->getZ()))->__toString();
                $player->sendMessage("§a> Spawn $args[1] set to X: " . (string)round($player->getPosition()->getX()) . " Y: " . (string)round($player->getPosition()->getY()) . " Z: " . (string)round($player->getPosition()->getZ()));

                break;
            case "joinsign":
                if (is_array($arena)) {
                    $player->sendMessage("§c> Join signs should be different for each arena.");
                    break;
                }

                $player->sendMessage("§a> Break block to set join sign!");
                $this->setupData[$player->getName()] = [
                    0 => 0
                ];

                break;
            case "leavepos":
                foreach ($arenas as $arena) {
                    $arena->data["leavePos"] = [(new Vector3((int)$player->getPosition()->getX(), (int)$player->getPosition()->getY(), (int)$player->getPosition()->getZ()))->__toString(), $player->getWorld()->getFolderName()];
                }

                $player->sendMessage("§a> Leave position updated.");
                break;
            case "enable":
                if (is_array($arena)) {
                    $player->sendMessage("§c> You cannot enable arena in mode multi-setup mode.");
                    break;
                }

                if (!$arena->setup) {
                    $player->sendMessage("§6> Arena is already enabled!");
                    break;
                }

                if (!$arena->enable()) {
                    $player->sendMessage("§c> Could not load arena, there are missing information!");
                    break;
                }

                foreach ($arenas as $arena)
                    $arena->mapReset->saveMap($arena->world);

                $player->sendMessage("§a> Arena enabled!");
                break;
            case "done":
                $player->sendMessage("§a> You have successfully left setup mode!");
                unset($this->setters[$player->getName()]);
                if (isset($this->setupData[$player->getName()])) {
                    unset($this->setupData[$player->getName()]);
                }
                break;
            case "savelevel":
                foreach ($arenas as $arena) {
                    if ($arena->data["level"] === null) {
                        $player->sendMessage("§c> Level not found!");
                        break;
                    }

                    if (!$arena->world instanceof World) {
                        $player->sendMessage("§c> Invalid level type: enable arena first.");
                        break;
                    }

                    $player->sendMessage("§a> Level saved.");
                    $arena->mapReset->saveMap($arena->world);
                }
                break;
            case "lobby":
                foreach ($arenas as $arena)
                    $arena->data["lobby"] = [(new Vector3((int)$player->getPosition()->getX(), (int)$player->getPosition()->getY(), (int)$player->getPosition()->getZ()))->__toString(), $player->getWorld()->getFolderName()];
                $player->sendMessage("§a> Game lobby updated!");
                break;
            default:
                $player->sendMessage("§6> You are in setup mode.\n" .
                    "§7- use §lhelp §r§7to display available commands\n" .
                    "§7- or §ldone §r§7to leave setup mode");
                break;
        }
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if (isset($this->setupData[$player->getName()]) && isset($this->setupData[$player->getName()][0])) {
            switch ($this->setupData[$player->getName()][0]) {
                case 0:
                    $this->setters[$player->getName()]->data["joinsign"] = [(new Vector3($block->getPosition()->getX(), $block->getPosition()->getY(), $block->getPosition()->getZ()))->__toString(), $block->getPosition()->getWorld()->getFolderName()];
                    $player->sendMessage("§a> Join sign updated!");
                    unset($this->setupData[$player->getName()]);
                    $event->cancel();
                    break;
                case 1:
                    $spawn = $this->setupData[$player->getName()][1];
                    $this->setters[$player->getName()]->data["spawns"]["spawn-$spawn"] = (new Vector3((int)$block->getPosition()->getX(), (int)($block->getPosition()->getY() + 1), (int)$block->getPosition()->getZ()))->__toString();
                    $player->sendMessage("§a> Spawn $spawn set to X: " . (string)round($block->getPosition()->getX()) . " Y: " . (string)round($block->getPosition()->getY()) . " Z: " . (string)round($block->getPosition()->getZ()));

                    $event->cancel();


                    $slots = $this->setters[$player->getName()]->data["slots"];
                    if ($spawn + 1 > $slots) {
                        $player->sendMessage("§a> Spawns updated.");
                        unset($this->setupData[$player->getName()]);
                        break;
                    }

                    $player->sendMessage("§a> Break block to set " . (string)(++$spawn) . " spawn.");
                    $this->setupData[$player->getName()][1]++;
            }
        }
    }
}