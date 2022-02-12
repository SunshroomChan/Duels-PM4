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

use SQLite3;
use vixikhd\duels\arena\Arena;
use vixikhd\duels\arena\MapReset;

/**
 * Class SQLiteDataProvider
 * @package duels\provider
 */
class SQLiteDataProvider extends DataProvider
{

    /** @var SQLite3 $dataBase */
    private $dataBase;

    public function init()
    {
        $this->dataBase = new SQLite3($this->getDataFolder() . DIRECTORY_SEPARATOR . "arenas.db");
        $this->dataBase->query("CREATE TABLE IF NOT EXISTS arenas ('id' VARCHAR PRIMARY KEY, 'data' VARCHAR)");
    }

    public function loadArenas()
    {
        $result = $this->dataBase->query("SELECT * FROM 'arenas'");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->plugin->arenas[$row["id"]] = new Arena($this->plugin, unserialize($row["data"]));
        }
        parent::loadArenas();
    }

    public function saveArenas()
    {
        $this->dataBase->query("DROP TABLE 'arenas'");
        $this->dataBase->query("CREATE TABLE arenas ('id' VARCHAR PRIMARY KEY , 'data' VARCHAR)");

        foreach ($this->plugin->arenas as $index => $arena) {
            if ($arena->mapReset instanceof MapReset)
                $arena->mapReset->loadMap($arena->level->getFolderName());
            $data = serialize($arena->data);
            $this->dataBase->query("INSERT INTO arenas ('id', 'data') VALUES ('" . $index . "', '" . $data . "')");
        }
    }
}