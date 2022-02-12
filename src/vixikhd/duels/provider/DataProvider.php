<?php

declare(strict_types=1);

namespace vixikhd\duels\provider;

use pocketmine\utils\Config;
use vixikhd\duels\provider\lang\Lang;
use vixikhd\duels\Duels;

/**
 * Class DataProvider
 * @package duels\provider
 */
abstract class DataProvider
{

    /** @var Duels $plugin */
    public $plugin;

    /** @var array $config */
    public $config;

    /** @var string[] $loadedArenas */
    public $loadedArenas = [];

    /** @var Lang $lang */
    private $lang;

    /**
     * DataProvider constructor.
     * @param Duels $plugin
     */
    public function __construct(Duels $plugin)
    {
        $this->plugin = $plugin;
        if (!is_dir($this->getDataFolder())) {
            @mkdir($this->getDataFolder());
        }
        if (!is_dir($this->getDataFolder() . "saves")) {
            @mkdir($this->getDataFolder() . "saves");
        }

        saveResources:
        if (!is_file($this->getDataFolder() . DIRECTORY_SEPARATOR . "config.yml")) {
            $this->plugin->saveResource($this->getDataFolder() . DIRECTORY_SEPARATOR . "config.yml");
        }

        $this->config = $cfg = $this->plugin->getConfig()->getAll(false);
        if (!isset($cfg["configVersion"])) {
            $plugin->getLogger()->notice("Your old Duels config were outdated. We've renamed it to config.old.yml.");
            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config.old.yml");
            goto saveResources;
        }

        if (!file_exists($this->getDataFolder() . "/messages.yml")) {
            $this->plugin->saveResource("/messages.yml");
        }

        loadLangAgain:
        $messages = yaml_parse_file($this->getDataFolder() . "/messages.yml");
        if ($this->checkForLanguageUpdates($messages)) {
            Duels::getInstance()->getLogger()->notice("Old language configuration found. Updating...");
            goto loadLangAgain;
        }

        $this->lang = new Lang($messages);

        $this->init();
        $this->loadArenas();
    }

    /**
     * @return string $dataFolder
     */
    public function getDataFolder(): string
    {
        return $this->plugin->getDataFolder();
    }

    private function checkForLanguageUpdates(array $messages)
    {
        if (!isset($messages["arena.death.killed"])) {
            $this->plugin->saveResource("/messages.yml", true);
            $newMessages = yaml_parse_file($this->getDataFolder() . "/messages.yml");
            $ignored = ["arena.death"];

            foreach ($messages as $index => $message) {
                if (!in_array($message, $ignored)) {
                    $newMessages[$index] = $message;
                }
            }

            yaml_emit_file($this->getDataFolder() . "/messages.yml", $newMessages);
            return true;
        }

        return false;
    }

    abstract public function init();

    public function loadArenas()
    {
        $this->plugin->getLogger()->info((string)count($this->loadedArenas) . " arenas loaded!");
    }

    /**
     * @return array|mixed
     */
    public function getStats()
    {
        return yaml_parse_file($this->getDataFolder() . "/stats.yml") ?? [];
    }

    /**
     * @param array $stats
     */
    public function saveStats(array $stats)
    {
        yaml_emit_file($this->getDataFolder() . "/stats.yml", $stats);
    }

    public function save()
    {
        $this->saveArenas();
    }

    abstract public function saveArenas();
}
