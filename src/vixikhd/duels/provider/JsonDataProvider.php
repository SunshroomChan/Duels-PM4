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
 * Class JsonDataProvider
 * @package skywars\provider
 */
class JsonDataProvider extends DataProvider
{

    public function init()
    {
        if (!is_dir($this->getDataFolder() . "arenas")) {
            @mkdir($this->getDataFolder() . "arenas");
        }
    }

    /**
     * @return array
     */
    public function getKits(): array
    {
        if (!is_file($this->getDataFolder() . "data" . DIRECTORY_SEPARATOR . "kits.json")) {
            return [];
        }
        return (array)json_decode(file_get_contents($this->getDataFolder() . "kits" . DIRECTORY_SEPARATOR . "kits.json"));
    }

    /**
     * @param array $kits
     */
    public function saveKits(array $kits)
    {
        if (!is_dir($this->getDataFolder() . "data")) {
            @mkdir($this->getDataFolder() . "data");
        }
        file_put_contents($this->getDataFolder() . "data" . DIRECTORY_SEPARATOR . "kits.json", json_encode($kits));
    }

    public function loadArenas()
    {
        foreach (glob($this->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . "*.json") as $arenaFile) {
            $config = new Config($arenaFile, Config::JSON);
            $this->plugin->arenas[basename($arenaFile, ".json")] = new Arena($this->plugin, $config->getAll(false));
            parent::loadArenas();
        }
    }

    public function saveArenas()
    {
        foreach ($this->plugin->arenas as $fileName => $arena) {
            if ($arena->mapReset instanceof MapReset)
                $arena->mapReset->loadMap($arena->level->getFolderName());
            $config = new Config($this->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . $fileName . ".json", Config::JSON);
            $config->setAll($arena->data);
            $config->save();
        }
    }
}
