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

use pocketmine\Server;
use pocketmine\world\World;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use vixikhd\duels\Duels;
use ZipArchive;

/**
 * Class MapReset
 * @package skywars\arena
 */
class MapReset
{

    /** @var Arena $plugin */
    public $plugin;

    /**
     * MapReset constructor.
     * @param Arena $plugin
     */
    public function __construct(Arena $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param World $world
     */
    public function saveMap(World $world)
    {
        if (!file_exists($this->plugin->plugin->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $world->getFolderName())) {
            return;
        }
        $world->save(true);
        $levelPath = $this->plugin->plugin->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $world->getFolderName();
        $zipPath = $this->plugin->plugin->getDataFolder() . "saves" . DIRECTORY_SEPARATOR . $world->getFolderName() . ".zip";
        $zip = new ZipArchive();
        if (is_file($zipPath)) {
            unlink($zipPath);
        }
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($levelPath)), RecursiveIteratorIterator::LEAVES_ONLY);
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isFile()) {
                $filePath = $file->getPath() . DIRECTORY_SEPARATOR . $file->getBasename();
                $localPath = substr($filePath, strlen($this->plugin->plugin->getServer()->getDataPath() . "worlds"));
                $zip->addFile($filePath, $localPath);
            }
        }
        $zip->close();
    }

    /**
     * @param string $folderName
     * @return World $world
     */
    public function loadMap(string $folderName): ?World
    {
        if (!file_exists($this->plugin->plugin->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $folderName)) {
            return null;
        }
        if (!$this->plugin->plugin->getServer()->getWorldManager()->isWorldGenerated($folderName)) {
            return null;
        }

        if ($this->plugin->plugin->getServer()->getWorldManager()->isWorldLoaded($folderName)) {
            $this->plugin->plugin->getServer()->getWorldManager()->unloadWorld($folderName);
        }

        $zipPath = $this->plugin->plugin->getDataFolder() . "saves" . DIRECTORY_SEPARATOR . $folderName . ".zip";
        if (!file_exists($zipPath)) {
            Duels::getInstance()->getLogger()->critical("Couldn't reload level {$folderName} (map archive was not found).");
            return null;
        }

        $zipArchive = new ZipArchive();
        $zipArchive->open($zipPath);
        $zipArchive->extractTo($this->plugin->plugin->getServer()->getDataPath() . "worlds");
        $zipArchive->close();

        $this->plugin->plugin->getServer()->getWorldManager()->loadWorld($folderName);
        return $this->plugin->plugin->getServer()->getWorldManager()->getWorldByName($folderName);
    }
}