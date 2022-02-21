<?php

declare(strict_types=1);

namespace vixikhd\duels\arena;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\TNT;
use pocketmine\command\{Command, CommandSender};
use pocketmine\console\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\world\sound\Sound;
use pocketmine\world\sound\XpLevelUpSound;
use pocketmine\world\World;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\Player;
use pocketmine\player\GameMode;
use pocketmine\Server;
use pocketmine\block\tile\Tile;
use pocketmine\utils\Config;
use Scoreboards\Scoreboards;
use vixikhd\duels\API;
use vixikhd\duels\event\PlayerArenaWinEvent;
use vixikhd\duels\form\CustomForm;
use vixikhd\duels\form\SimpleForm;
use vixikhd\duels\math\Vector3;
use vixikhd\duels\provider\lang\Lang;
use vixikhd\duels\Duels;
use vixikhd\duels\arena\PlayerSnapshot;
use vixikhd\duels\utils\ServerManager;

/**
 * Class Arena
 * @package duels\arena
 */
class Arena implements Listener
{

    public const MSG_MESSAGE = 0;
    public const MSG_TIP = 1;
    public const MSG_POPUP = 2;
    public const MSG_TITLE = 3;

    public const PHASE_LOBBY = 0;
    public const PHASE_GAME = 1;
    public const PHASE_RESTART = 2;

    public const FILLING_BLOCK = 0;
    public const FILLING_ITEM = 1;
    public const FILLING_FOOD = 2;
    public const FILLING_POTION = 3;
    public const FILLING_MATERIAL = 4;
    public const FILLING_ARMOUR = 5;

    // from config
    public const FILLING_CUSTOM = -1;

    /** @var Duels $plugin */
    public $plugin;

    /** @var ArenaScheduler $scheduler */
    public $scheduler;

    /** @var MapReset $mapReset */
    public $mapReset;

    /** @var int $phase */
    public $phase = 0;
    /** @var array $data */
    public $data = [];
    /** @var bool $setting */
    public $setup = false;
    /** @var Player[] $players */
    public $players = [];
    /** @var Player[] $spectators */
    public $spectators = [];
    /** @var array $kills */
    public $kills = [];
    /** @var Player[] $toRespawn */
    public $toRespawn = [];
    /** @var array $rewards */
    public $rewards = [];
    /** @var World $world */
    public $world = null;
    /** @var PlayerSnapshot */
    private $playerSnapshots = [];
    /** @var array $wantLeft */
    private $wantLeft = [];

    /**
     * Arena constructor.
     * @param Duels $plugin
     * @param array $arenaFileData
     */
    public function __construct(Duels $plugin, array $arenaFileData)
    {
        $this->plugin = $plugin;
        $this->data = $arenaFileData;
        $this->setup = !$this->enable(false);

        $this->plugin->getScheduler()->scheduleRepeatingTask($this->scheduler = new ArenaScheduler($this), 20);

        if ($this->setup) {
            if (empty($this->data)) {
                $this->createBasicData();
                $this->plugin->getLogger()->error("Could not load arena {$this->data["level"]}");
            } else {
                $this->plugin->getLogger()->error("Could not load arena {$this->data["level"]}, complete setup.");
            }
        } else {
            $this->loadArena();
        }
    }

    public function bossbarText($player) {
        $name = "§l§eDUELS";
        $this->plugin->bossbar->setTitle($name);
        $this->plugin->bossbar->setPercentage(100);
        $this->plugin->bossbar->addPlayer($player);
    }

    public function removeBossBar($player) {
        $this->plugin->bossbar->removePlayer($player);
    }

    /**
     * @param bool $loadArena
     * @return bool $isEnabled
     */
    public function enable(bool $loadArena = true): bool
    {
        if (empty($this->data)) {
            return false;
        }
        if ($this->data["level"] == null) {
            return false;
        }
        if (!$this->plugin->getServer()->getWorldManager()->isWorldGenerated($this->data["level"])) {
            return false;
        }
        if (!is_int($this->data["slots"])) {
            return false;
        }
        if (!is_array($this->data["spawns"])) {
            return false;
        }
        if (count($this->data["spawns"]) != $this->data["slots"]) {
            return false;
        }
        if (!isset($this->data["pts"]) || !is_int($this->data["pts"])) {
            return false;
        }
        if (!isset($this->data["leavePos"]) || $this->data["leavePos"] === null) {
            return false;
        }
        $this->data["enabled"] = true;
        $this->setup = false;
        if ($loadArena) $this->loadArena();
        return true;
    }

    /**
     * @param bool $restart
     */
    public function loadArena(bool $restart = false)
    {
        if (!$this->data["enabled"]) {
            $this->plugin->getLogger()->error("Can not load arena: Arena is not enabled!");
            return;
        }

        if (!$this->mapReset instanceof MapReset) {
            $this->mapReset = new MapReset($this);
        }

        if (!$restart) {
            $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);

            if (!$this->plugin->getServer()->getWorldManager()->isWorldLoaded($this->data["level"])) {
                $this->plugin->getServer()->getWorldManager()->loadWorld($this->data["level"]);
            }

            $this->world = $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data["level"]);
        } else {
            if (is_null($this->world)) {
                $this->setup = true;
                $this->plugin->getLogger()->error("Disabling arena {$this->data["level"]}: level not found!");
                $this->data["level"] = null;
                return;
            }

            $this->kills = [];
        }

        if (!$this->plugin->getServer()->getWorldManager()->isWorldGenerated($this->data["level"])) {
            $this->plugin->getServer()->getWorldManager()->loadWorld($this->data["level"]);
        }

        if (!$this->world instanceof World) {
            $this->world = $this->mapReset->loadMap($this->data["level"]);
        }

        if (!$this->world instanceof World) {
            $this->plugin->getLogger()->error("Disabling arena {$this->data["level"]}: level not found!");
            $this->data["level"] = null;
            return;
        }


        if (is_null($this->world)) {
            $this->setup = true;
        }

        $this->phase = 0;
        $this->players = [];
        $this->spectators = [];
    }

    private function createBasicData()
    {
        $this->data = [
            "level" => null,
            "slots" => 2,
            "spawns" => [],
            "enabled" => false,
            "joinsign" => [],
            "startTime" => 40,
            "gameTime" => 1200,
            "restartTime" => 10,
            "leaveGameMode" => 2,
            "spectatorMode" => true,
            "pts" => 2,
            "lobby" => null
        ];
    }

    public function startGame()
    {
        $players = [];
        /**$sounds = $this->plugin->dataProvider->config["sounds"]["enabled"];*/
        foreach ($this->players as $player) {
            $players[$player->getName()] = $player;
            $player->setGamemode(GameMode::SURVIVAL());
            $player->getInventory()->clearAll(true);
            $player->setImmobile(false);
            $player->getEffects()->clear();
            $this->kit1($player);
        }

        $this->players = $players;
        $this->phase = 1;

        $this->broadcastMessage(Lang::getMsg("arena.start"), self::MSG_TITLE);
    }

    public function kit1(Player $player) {
        $helmet = ItemFactory::getInstance()->get(310, 0, 1);
        $chestplate = ItemFactory::getInstance()->get(311, 0, 1);
        $leggings = ItemFactory::getInstance()->get(312, 0, 1);
        $boots = ItemFactory::getInstance()->get(313, 0, 1);
        $player->getInventory()->setItem(0, ItemFactory::getInstance()->get(276, 0, 1));
        $player->getInventory()->setItem(1, ItemFactory::getInstance()->get(322, 0, 1));
        $player->getInventory()->setItem(2, ItemFactory::getInstance()->get(261, 0, 1));
        $player->getInventory()->setItem(8, ItemFactory::getInstance()->get(262, 0, 1));
        $player->getArmorInventory()->clearAll();
        $player->getArmorInventory()->setHelmet($helmet);
        $player->getArmorInventory()->setChestplate($chestplate);
        $player->getArmorInventory()->setLeggings($leggings);
        $player->getArmorInventory()->setBoots($boots);
    }

    /**
     * @param string $message
     * @param int $id
     * @param string $subMessage
     * @param bool $addSpectators
     */
    public function broadcastMessage(string $message, int $id = 0, string $subMessage = "", bool $addSpectators = true)
    {
        $players = $this->players;
        if ($addSpectators) {
            foreach ($this->spectators as $index => $spectator) {
                $players[$index] = $spectator;
            }
        }
        foreach ($players as $player) {
            switch ($id) {
                case self::MSG_MESSAGE:
                    $player->sendMessage($message);
                    break;
                case self::MSG_TIP:
                    $player->sendTip($message);
                    break;
                case self::MSG_POPUP:
                    $player->sendPopup($message);
                    break;
                case self::MSG_TITLE:
                    $player->sendTitle($message, $subMessage);
                    break;
            }
        }
    }

    public function startRestart()
    {
        $player = null;
        foreach ($this->players as $p) {
            $player = $p;
        }

        $this->phase = self::PHASE_RESTART;
        if ($player === null || (!$player instanceof Player) || (!$player->isOnline())) {
            return;
        }

        $volume = mt_rand();
        $player->getWorld()->addSound($player->getPosition()->asVector3(), new XpLevelUpSound(1));
        $player->sendTitle("§6§lVICTORY!", "§7!");
        $player->setAllowFlight(true);

        $this->plugin->getServer(new PlayerArenaWinEvent($this->plugin, $player, $this));
        $this->plugin->getServer()->broadcastMessage(Lang::getMsg("arena.win.message", [$player->getName(), $this->world->getFolderName()]));
        API::handleWin($player, $this);
    }

    /**
     * @return bool $end
     */
    public function checkEnd(): bool
    {
        return count($this->players) <= 1 || $this->scheduler->gameTime <= 0;
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        if ($this->inGame($player)) {
            $event->cancel();
        }
    }
    /**
     * @param BlockPlaceEvent $event
     */
    public function onPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->inGame($player)) {

            return;
        }

        $block = $event->getBlock();
        if ($block instanceof TNT) {
            $block->ignite(50);
            $event->cancel();
            $player->getInventory()->removeItem(ItemFactory::getInstance()->get($block->getId()));
        }
    }

    /**
     * @param Player $player
     * @param bool $addSpectators
     * @return bool
     */
    public function inGame(Player $player, bool $addSpectators = false): bool
    {
        if ($addSpectators && isset($this->spectators[$player->getName()])) return true;
        switch ($this->phase) {
            case self::PHASE_LOBBY:
                $inGame = false;
                foreach ($this->players as $players) {
                    if ($players->getId() == $player->getId()) {
                        $inGame = true;
                    }
                }
                return $inGame;
            default:
                return isset($this->players[$player->getName()]);
        }
    }

    /**
     * @param PlayerMoveEvent $event
     */
    public function onMove(PlayerMoveEvent $event)
    {
        if ($this->phase != self::PHASE_LOBBY) return;
        $player = $event->getPlayer();
        if ($this->inGame($player)) {
            if ((!$this->scheduler->teleportPlayers) || $this->scheduler->startTime <= 10) {
                $index = null;
                foreach ($this->players as $i => $p) {
                    if ($p->getId() == $player->getId()) {
                        $index = $i;
                    }
                }

                if ($event->getPlayer()->getPosition()->asVector3()->distance(Vector3::fromString($this->data["spawns"][$index])->add(0.5, 0, 0.5)) > 1) {
                    // $event->setCancelled() will not work
                    $player->teleport(Vector3::fromString($this->data["spawns"][$index])->add(0.5, 0, 0.5));
                }
            }
        }
    }

    public function onVoid(PlayerMoveEvent $event)
    {
        if ($this->phase != self::PHASE_GAME) return;
        $entity = $event->getPlayer();
        if ($this->inGame($entity)) {
            $name = $entity->getName();
            if ($entity->getPosition()->getY() < -1) {
                foreach ($entity->getInventory()->getContents() as $item) {
                    $entity->getWorld()->dropItem($entity->getPosition()->asVector3(), $item);
                }
                foreach ($entity->getArmorInventory()->getContents() as $item) {
                    $entity->getWorld()->dropItem($entity->getPosition()->asVector3(), $item);
                }
                foreach ($entity->getCursorInventory()->getContents() as $item) {
                    $entity->getWorld()->dropItem($entity->getPosition()->asVector3(), $item);
                }

                unset($this->players[$entity->getName()]);
                $this->spectators[$entity->getName()] = $entity;

                $entity->getEffects()->clear();
                $entity->getInventory()->clearAll();
                $entity->getArmorInventory()->clearAll();
                $entity->getCursorInventory()->clearAll();

                $entity->setGamemode(GameMode::SPECTATOR());
                $entity->setFlying(true);

                $entity->sendTitle("§c§lYOU DIED!", "§eYou didn't won this time!");

                $entity->teleport(new Position($entity->getPosition()->getX(), Vector3::fromString($this->data["spawns"]["spawn-1"])->getY(), $entity->getPosition()->getZ(), $this->world));
                $entity->getInventory()->setItem(8, ItemFactory::getInstance()->get(ItemIds::BED)->setCustomName("§r§eLeave the game\n§7[Use]"));
                $entity->getInventory()->setItem(4, ItemFactory::getInstance()->get(ItemIds::COMPASS)->setCustomName("§r§eSpectator Player\n§7[Use]"));

            }
        }
    }

    /**
     * @param PlayerExhaustEvent $event
     */
    public function onExhaust(PlayerExhaustEvent $event)
    {
        $player = $event->getPlayer();

        if (!$player instanceof Player) return;

        if ($this->inGame($player) && $this->phase == self::PHASE_LOBBY) {
            $player->getHungerManager()->setFood(20);
            $event->cancel();
        }
    }

    /**
     * @param PlayerItemHeldEvent $event
     */

    public function onCompass(PlayerItemHeldEvent $event)
    {
        $player = $event->getPlayer();
        if (isset($this->spectators[$player->getName()]) && $event->getItem()->getId() == ItemIds::COMPASS) {
            $this->SpectatorsForm($player);
        }
    }

    public function SpectatorsForm(Player $player)
    {

        $list = [];
        foreach ($this->players as $p) {
            $list[] = $p->getName();
        }

        $this->playerList[$player->getName()] = $list;

        $form = new CustomForm(function (Player $player, array $data = null) {

            if ($data == null) {
                return true;
            }

            $index = $data[1];
            $playerName = $this->playerList[$player->getName()][$index];
            $target = Server::getInstance()->getPlayerExact($playerName);
            if ($target instanceof Player) {
                $player->teleport($target->getPosition()->asVector3());
            }
           return true;
        }
        );
        if (empty($this->players)) {
            $player->sendMessage("§cWait for another opponent....");
            $player->sendTitle("§cWaiting for player....");
            return true;
        }
        $form->setTitle("§l§eSpectators Player");
        $form->addLabel("§bSelect Players Here:");
        $form->addDropdown("Select Players:", $this->playerList[$player->getName()]);
        $form->sendToPlayer($player);
        return $form;
    }

    public function onHeld(PlayerItemHeldEvent $event)
    {
        $player = $event->getPlayer();
        if (isset($this->spectators[$player->getName()]) && $event->getItem()->getId() == ItemIds::BED) {
            if (isset($this->wantLeft[$player->getName()])) {
                $this->disconnectPlayer($player, "§7§lDuels>§r§a You have successfully left the game.", false, true, true);
                unset($this->wantLeft[$player->getName()]);
            } else {
                $player->sendMessage("§7§lDuels>§r§6 Do you want really left the game?");
                $this->wantLeft[$player->getName()] = true;
                $event->cancel();
            }
        }
    }

    /**
     * @param Player $player
     * @param string $quitMsg
     * @param bool $death
     * @param bool $spectator
     * @param bool $transfer
     */
    public function disconnectPlayer(Player $player, string $quitMsg = "", bool $death = false, bool $spectator = false, bool $transfer = false)
    {
        if (!$this->inGame($player, true)) {
            return;
        }

        if ($spectator || isset($this->spectators[$player->getName()])) {
            unset($this->spectators[$player->getName()]);
        }

        switch ($this->phase) {
            case Arena::PHASE_LOBBY:
                $index = "";
                foreach ($this->players as $i => $p) {
                    if ($p->getId() == $player->getId()) {
                        $index = $i;
                    }
                }
                if ($index !== "" && isset($this->players[$index])) {
                    unset($this->players[$index]);
                }
                break;
            default:
                unset($this->players[$player->getName()]);
                break;
        }

        if ($player->isOnline()) {
            $player->getEffects()->clear();

            $player->setGamemode(GameMode::ADVENTURE());

            $player->setHealth(20);
            $player->getHungerManager()->setFood(20);

            $playerSnapshot = $this->playerSnapshots[$player->getId()];
            unset($this->playerSnapshots[$player->getId()]);
            $playerSnapshot->injectInto($player);

            $player->setImmobile(false);

        }

        $config = new Config($this->plugin->getDataFolder() . "kills.yml", Config::YAML);
        $config->getAll();
        $config->set($player->getName(), $config->remove($player->getName(), "  "));
        $config->set($player->getName(), $config->remove($player->getName(), "0"));
        $config->save();

        API::handleQuit($player, $this);
        Scoreboards::getInstance()->remove($player);

        if ($death && $this->data["spectatorMode"]) $this->spectators[$player->getName()] = $player;

        if (!$this->data["spectatorMode"] || $transfer) {
            if ($this->plugin->dataProvider->config["waterdog"]["enabled"]) {
                ServerManager::transferPlayer($player, $this->plugin->dataProvider->config["waterdog"]["lobbyServer"]);
            }
            $player->teleport(Position::fromObject(Vector3::fromString($this->data["leavePos"][0]), $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data["leavePos"][1])));
        }

        /*if(!$death && $this->phase !== 2) {
            $player->sendMessage("§7§lDuels>§r§a You have successfully left the arena.");
        }*/

        if ($quitMsg != "") {
            $player->sendMessage($quitMsg);
        }
    }

    /**
     * @param PlayerDropItemEvent $event
     */
    public function onDrop(PlayerDropItemEvent $event)
    {
        $player = $event->getPlayer();
        if ($this->inGame($player) && $this->phase === 0) {
            $event->cancel();
        }
    }

    /**
     * @param PlayerInteractEvent $event
     */

    public function onInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if ($this->inGame($player, true) && $event->getAction() === $event::RIGHT_CLICK_BLOCK) {
            switch ($event->getPlayer()->getInventory()->getItemInHand()->getId()) {
                case ItemIds::BED:
                    $this->disconnectPlayer($player, Lang::getMsg("arena.quit.player"), false, false, true);
                    break;
                case ItemIds::PAPER:
                    $form = new SimpleForm("§8§lAvailable maps", "§r§fSelect map to join.");
                    foreach ($this->plugin->arenas as $index => $arena) {
                        if ($arena->phase == 0 && count($arena->players) < $arena->data["slots"] && $arena !== $this) {
                            $form->addButton("§a{$arena->data["level"]} - " . (string)count($arena->players) . " Players\n§7§oClick to join.");
                            $data = $form->getCustomData();
                            $data[] = $index;
                            $form->setCustomData($data);
                        }
                    }
                    $form->setAdvancedCallable([$this, "handleMapChange"]);
                    if (!is_array($form->getCustomData()) || count($form->getCustomData()) === 0) {
                        $player->sendMessage("§cAll the other arenas are full.");
                        break;
                    }
                    $player->sendForm($form);
                    break;
            }
            return;
        }

        if (!empty($this->data["joinsign"])) {
            if (!$block->getPosition()->getWorld()->getTile($block->getPosition()->asVector3()) instanceof Tile) {
                return;
            }

            $signPos = Position::fromObject(Vector3::fromString($this->data["joinsign"][0]), $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data["joinsign"][1]));

            if ((!$signPos->equals($block->getPosition()->asVector3())) || $signPos->getWorld()->getId() != $block->getPosition()->getWorld()->getId()) {
                return;
            }

            if ($this->phase == self::PHASE_GAME) {
                $player->sendMessage(Lang::getMsg("arena.join.ingame"));
                return;
            }
            if ($this->phase == self::PHASE_RESTART) {
                $player->sendMessage(Lang::getMsg("arena.join.restart"));
                return;
            }

            if ($this->setup) {
                return;
            }

            $this->joinToArena($player);
        }
    }

    /**
     * @param Player $player
     * @param bool $force
     */
    public function joinToArena(Player $player, bool $force = false)
    {
        if (!$this->data["enabled"]) {
            $player->sendMessage("§7§lDuels>§r§c Arena is under setup!");
            return;
        }

        if ($this->phase !== 0) {
            $player->sendMessage("§7§lDuels>§r§c Arena is already in game!");
            return;
        }

        if (count($this->players) >= $this->data["slots"]) {
            $player->sendMessage(Lang::getMsg("arena.join.full"));
            return;
        }

        if ($this->inGame($player)) {
            $player->sendMessage(Lang::getMsg("arena.join.player.ingame"));
            return;
        }

        if ($this->scheduler->startTime <= 10) {
            $player->sendMessage("§c> Arena is starting...");
            return;
        }

        if (!API::handleJoin($player, $this, $force)) {
            return;
        }

        $this->scheduler->teleportPlayers = isset($this->data["lobby"]) || $this->data["lobby"] !== null;

        if (!$this->scheduler->teleportPlayers) {
            $selected = false;
            for ($lS = 1; $lS <= $this->data["slots"]; $lS++) {
                if (!$selected) {
                    if (!isset($this->players[$index = "spawn-{$lS}"])) {
                        $player->teleport(Position::fromObject(Vector3::fromString($this->data["spawns"][$index])->add(0.5, 0, 0.5), $this->world));
                        $this->players[$index] = $player;
                        $selected = true;
                    }
                }
            }
        } else {
            if (!$this->plugin->getServer()->getWorldManager()->isWorldLoaded($this->data["lobby"][1])) {
                $this->plugin->getServer()->getWorldManager()->loadWorld($this->data["lobby"][1]);
            }
            $this->players[] = $player;
            $player->teleport(Position::fromObject(Vector3::fromString($this->data["lobby"][0]), $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data["lobby"][1])));
        }

        $this->playerSnapshots[$player->getId()] = new PlayerSnapshot($player, true, true);

        $player->setGamemode(GameMode::ADVENTURE());
        $player->setHealth(20);
        $player->getHungerManager()->setFood(20);

        $player->getEffects()->clear();

        //$player->setImmobile(true);

        $this->kills[$player->getName()] = 0;

        $inv = $player->getInventory();
        $inv->setItem(0, ItemFactory::getInstance()->get(ItemIds::PAPER)->setCustomName("§r§eChange map\n§7[Use]"));
        $inv->setItem(8, ItemFactory::getInstance()->get(ItemIds::BED)->setCustomName("§r§eLeave game\n§7[Use]"));

        $this->bossbarText($player);

        $this->broadcastMessage(Lang::getMsg("arena.join", [$player->getName(), count($this->players), $this->data["slots"]]));
        //Your code
    }

    public function onDamage(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Player) return;

        if ($this->inGame($entity) && $this->phase === 0) {
            $event->cancel();
            if ($event->getCause() === $event::CAUSE_VOID) {
                if (isset($this->data["lobby"]) && $this->data["lobby"] != null) {
                    $entity->teleport(Position::fromObject(Vector3::fromString($this->data["lobby"][0]), $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data["lobby"][1])));
                }
            }
        }

        if (($this->inGame($entity) && $this->phase === 1 && $event->getCause() == EntityDamageEvent::CAUSE_FALL && ($this->scheduler->gameTime > ($this->data["gameTime"] - 3)))) {
            $event->cancel();
        }

        if ($this->inGame($entity) && $this->phase === 2) {
            $event->cancel();
        }

        // fake kill
        if (!$this->inGame($entity)) {
            return;
        }

        if ($this->phase !== 1) {
            return;
        }

        if ($event->getCause() === $event::CAUSE_VOID) {
            $event->setBaseDamage(20.0); // hack: easy check for last damage
        }

        if ($entity->getHealth() - $event->getFinalDamage() <= 0) {
            $event->cancel();
            API::handleDeath($entity, $this, $event);

            switch ($event->getCause()) {
                case $event::CAUSE_CONTACT:
                case $event::CAUSE_ENTITY_ATTACK:
                    if ($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        if ($damager instanceof Player) {
                            $config = new Config($this->plugin->getDataFolder() . "kills.yml", Config::YAML);
                            $config->getAll();
                            $config->set($damager->getName(), $config->remove($damager->getName()) + 1);
                            $config->save();
                            API::handleKill($damager, $this, $event);
                            $this->kills[$damager->getName()]++;
                            $this->broadcastMessage(Lang::getMsg("arena.death.killed", [$entity->getName(), $damager->getName(), (string)(count($this->players) - 1), (string)$this->data['slots']]));
                            break;
                        }
                    }
                    $this->broadcastMessage(Lang::getMsg("arena.death.killed", [$entity->getName(), "Player", (string)(count($this->players) - 1), (string)$this->data['slots']]));
                    break;
                case $event::CAUSE_PROJECTILE:
                    if ($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        if ($damager instanceof Player) {
                            $config = new Config($this->plugin->getDataFolder() . "kills.yml", Config::YAML);
                            $config->getAll();
                            $config->set($damager->getName(), $config->remove($damager->getName()) + 1);
                            $config->save();
                            API::handleKill($damager, $this, $event);
                            $this->kills[$damager->getName()]++;
                            $this->broadcastMessage(Lang::getMsg("arena.death.killed", [$entity->getName(), $damager->getName(), (string)(count($this->players) - 1), (string)$this->data['slots']]));
                            break;
                        }
                    }
                    $this->broadcastMessage(Lang::getMsg("arena.death.killed", [$entity->getName(), "Player", (string)(count($this->players) - 1), (string)$this->data['slots']]));
                case $event::CAUSE_BLOCK_EXPLOSION:
                    $this->broadcastMessage(Lang::getMsg("arena.death.exploded", [$entity->getName(), (string)(count($this->players) - 1), (string)$this->data['slots']]));
                    break;
                case $event::CAUSE_FALL:
                    $this->broadcastMessage(Lang::getMsg("arena.death.fell", [$entity->getName(), (string)(count($this->players) - 1), (string)$this->data['slots']]));
                    break;
                case $event::CAUSE_VOID:
                    $lastDmg = $entity->getLastDamageCause();
                    if ($lastDmg instanceof EntityDamageByEntityEvent) {
                        $damager = $lastDmg->getDamager();
                        if ($damager instanceof Player && $this->inGame($damager)) {
                            $this->broadcastMessage(Lang::getMsg("arena.death.void.player", [$entity->getName(), $damager->getName(), (string)(count($this->players) - 1), (string)$this->data['slots']]));
                            break;
                        }
                    }
                    $this->broadcastMessage(Lang::getMsg("arena.death.void", [$entity->getName(), (string)(count($this->players) - 1), (string)$this->data['slots']]));
                    break;
                default:
                    $this->broadcastMessage(Lang::getMsg("arena.death", [$entity->getName(), (string)(count($this->players) - 1), (string)$this->data['slots']]));
            }

            foreach ($entity->getWorld()->getEntities() as $pearl) {
                if ($pearl->getOwningEntityId() === $entity->getId()) {
                    $pearl->kill(); // TODO - cancel teleporting with pearls
                }
            }

            foreach ($entity->getInventory()->getContents() as $item) {
                $entity->getWorld()->dropItem($entity->getPosition()->asVector3(), $item);
            }
            foreach ($entity->getArmorInventory()->getContents() as $item) {
                $entity->getWorld()->dropItem($entity->getPosition()->asVector3(), $item);
            }
            foreach ($entity->getCursorInventory()->getContents() as $item) {
                $entity->getWorld()->dropItem($entity->getPosition()->asVector3(), $item);
            }

            unset($this->players[$entity->getName()]);
            $this->spectators[$entity->getName()] = $entity;

            $entity->getEffects()->clear();
            $entity->getInventory()->clearAll();
            $entity->getArmorInventory()->clearAll();
            $entity->getCursorInventory()->clearAll();

            $entity->setGamemode(GameMode::SPECTATOR());
            $entity->setFlying(true);

            $entity->sendTitle("§c§lYOU DIED!", "§eYou didn't won this time!");

            $entity->teleport(new Position($entity->getPosition()->getX(), Vector3::fromString($this->data["spawns"]["spawn-1"])->getY(), $entity->getPosition()->getZ(), $this->world));
            $entity->getInventory()->setItem(8, ItemFactory::getInstance()->get(ItemIds::BED)->setCustomName("§r§eLeave the game\n§7[Use]"));
            $entity->getInventory()->setItem(4, ItemFactory::getInstance()->get(ItemIds::COMPASS)->setCustomName("§r§eSpectator Player\n§7[Use]"));

        }
    }

    public function onProjectile(EntityDamageEvent $event)
    {
        if ($event instanceof EntityDamageByChildEntityEvent) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            $damager->sendMessage(Lang::getMsg("arena.projectile", [$entity->getName(), $entity->getHealth()]));
        }
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event)
    {
        if ($this->inGame($event->getPlayer(), true)) {
            $this->disconnectPlayer($event->getPlayer(), "", false, $event->getPlayer()->getGamemode() == GameMode::SPECTATOR() || isset($this->spectators[$event->getPlayer()->getName()]));
        }
        $event->setQuitMessage("");
    }

    /**
     * @param EntityTeleportEvent $event
     */
    public function onWorldChange(EntityTeleportEvent $event)
    {
        $player = $event->getEntity();
        $this->removeBossBar($player);
        if (!$player instanceof Player) return;
        if ($this->inGame($player, true)) {
            if (class_exists(SpectatingApi::class) && SpectatingApi::isSpectating($player)) {
                return;
            }
            $isLobbyExists = (isset($this->data["lobby"]) && $this->data["lobby"] !== null);
            if ($isLobbyExists) {
                $isFromLobbyWorld = $event->getEntity()->getId() == $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data["lobby"][1])->getId();
                if ($isFromLobbyWorld && $this->world instanceof World && $event->getEntity()->getTargetEntity() !== null && $event->getEntity()->getTargetEntity()->getId() !== $this->world->getId()) {
                    $this->disconnectPlayer($player, "§7§lDuels> §r§aYou have successfully left the arena!", false, $player->getGamemode() == GameMode::SPECTATOR() || isset($this->spectators[$player->getName()]));
                }
            } else {
                $this->disconnectPlayer($player, "§7§lDuels> §r§aYou have successfully left the arena!", false, $player->getGamemode() == GameMode::SPECTATOR() || isset($this->spectators[$player->getName()]));
            }
        }
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        if (isset($this->plugin->dataProvider->config["chat"]["custom"]) && $this->plugin->dataProvider->config["chat"]["custom"] && $this->inGame($player, true)) {
            $this->broadcastMessage(str_replace(["%player", "%message"], [$player->getName(), $event->getMessage()], $this->plugin->dataProvider->config["chat"]["format"]));
            $event->cancel();
        }
    }

    /**
     * @param Player $player
     * @param $data
     * @param SimpleForm $form
     */
    public function handleMapChange(Player $player, $data, SimpleForm $form)
    {
        if ($data === null) return;

        $arena = $this->plugin->arenas[$form->getCustomData()[$data]];
        if ($arena->phase !== 0) {
            $player->sendMessage("§7§lDuels> §r§cArena is in game.");
            return;
        }

        if ($arena->data["slots"] <= count($arena->players)) {
            $player->sendMessage("§7§lDuels> §r§cArena is full");
            return;
        }

        if ($arena === $this) {
            $player->sendMessage("§cYou are already in this arena!");
            return;
        }

        $this->disconnectPlayer($player, "");
        $arena->joinToArena($player);
    }

    public function __destruct()
    {
        unset($this->scheduler);
    }
}