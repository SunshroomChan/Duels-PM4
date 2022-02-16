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

namespace vixikhd\duels\provider;

use pocketmine\utils\Config;
use vixikhd\duels\arena\Arena;
use vixikhd\duels\arena\MapReset;

/**
 * Class YamlDataProvider
 * @package duels\provider
 */
class YamlDataProvider extends DataProvider
{

    /** @var string[] $loaded */
    private $loaded = [];

    public function init()
    {
        if (!is_dir($this->getDataFolder() . "arenas")) {
            @mkdir($this->getDataFolder() . "arenas");
        }
    }

    /**
     * @return array
     */
    public function getStats(): array
    {
        if (!is_file($this->getDataFolder() . "data" . DIRECTORY_SEPARATOR . "stats.yml")) {
            return [];
        }
        return (array)yaml_parse_file($this->getDataFolder() . "data" . DIRECTORY_SEPARATOR . "stats.yml");
    }

    /**
     * @param array $stats
     */
    public function saveStats(array $stats)
    {
        if (!is_dir($this->getDataFolder() . "data")) {
            @mkdir($this->getDataFolder() . "data");
        }
        $config = new Config($this->getDataFolder() . "data" . DIRECTORY_SEPARATOR . "stats.yml");
        $config->setAll($stats);
        $config->save();
    }

    public function loadArenas()
    {
        foreach (glob($this->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . "*.yml") as $arenaFile) {
            $config = new Config($arenaFile, Config::YAML);
            $this->plugin->arenas[basename($arenaFile, ".yml")] = new Arena($this->plugin, $config->getAll(false));
        }
        parent::loadArenas();
    }

    public function saveArenas()
    {
        foreach ($this->plugin->arenas as $fileName => $arena) {
            if ($arena->mapReset instanceof MapReset)
                $arena->mapReset->loadMap($arena->world->getFolderName());
            $config = new Config($this->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . $fileName . ".yml", Config::YAML);
            $config->setAll($arena->data);
            $config->save();
        }
    }
}
