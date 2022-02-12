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

use mysqli;
use vixikhd\duels\arena\Arena;
use vixikhd\duels\arena\MapReset;

/**
 * Class MySQLDataProvider
 * @package duels\provider
 */
class MySQLDataProvider extends DataProvider
{

    /** @var mysqli $dataBase */
    private $dataBase;

    /** @var array $mysqlLogin */
    private $mysqlLogin = [];

    public function init()
    {
        $this->mysqlLogin = $this->config["mysqlLogin"];

        if (!isset($this->mysqlLogin["host"]) || !isset($this->mysqlLogin["username"]) || !isset($this->mysqlLogin["password"])) {
            $this->plugin->getLogger()->critical("Could not load MySQL data provider, fill mysqlLogin in config.yml!");
            $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
            return;
        }

        $this->dataBase = new mysqli($this->mysqlLogin["host"], $this->mysqlLogin["username"], $this->mysqlLogin["password"]);

        if ($this->dataBase->connect_error) {
            $this->plugin->getLogger()->critical("Could not connect to MySQL ({$this->dataBase->connect_error})!");
            $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
            return;
        }

        // Arenas data

        $this->dataBase->query("CREATE DATABASE IF NOT EXISTS Duels");

        if ($this->dataBase->error) {
            $this->plugin->getLogger()->critical("Could not query to MySQL ({$this->dataBase->error})!");
            $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
            return;
        }

        $this->dataBase->select_db("Duels");
        $this->dataBase->query("CREATE TABLE IF NOT EXISTS arenas (id VARCHAR(99) PRIMARY KEY, args TEXT)");

    }

    public function getKits(): array
    {
        $result = $this->dataBase->query("SELECT * FROM kits");

        if ($this->dataBase->error || is_bool($result)) {
            $this->plugin->getLogger()->critical("Could not query to MySQL ({$this->dataBase->error})!");
            $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
            return [];
        }

        $kits = [];

        while ($row = $result->fetch_assoc()) {
            $kits[$row["player"]] = explode(",", $row["kits"]);
        }

        return $kits;
    }

    public function saveKits(array $kits)
    {
        $this->dataBase->query("DROP TABLE IF EXISTS kits");
        $this->dataBase->query("CREATE TABLE kits (player VARCHAR(99) PRIMARY KEY, kits TEXT)");

        foreach ($kits as $player => $kitArg) {
            $this->dataBase->query("INSERT INTO kits (player, kits) VALUES ('" . $player . "', '" . implode(",", $kitArg) . "')");
        }
    }

    public function loadArenas()
    {
        $result = $this->dataBase->query("SELECT * FROM arenas");


        if ($this->dataBase->error || is_bool($result)) {
            $this->plugin->getLogger()->critical("Could not query to MySQL ({$this->dataBase->error})!");
            $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $this->plugin->arenas[$row["id"]] = new Arena($this->plugin, unserialize($row["args"]));
        }
        parent::loadArenas();
    }

    public function saveArenas()
    {
        $this->dataBase->query("DROP TABLE arenas");
        $this->dataBase->query("CREATE TABLE arenas (id VARCHAR(99) PRIMARY KEY, args TEXT)");

        foreach ($this->plugin->arenas as $index => $arena) {
            if ($arena->mapReset instanceof MapReset)
                $arena->mapReset->loadMap($arena->level->getFolderName());
            $data = serialize($arena->data);
            $this->dataBase->query("INSERT INTO arenas (id, args) VALUES ('" . $index . "', '" . $data . "')");
        }
    }
}