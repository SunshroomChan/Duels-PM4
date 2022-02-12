<?php

declare(strict_types=1);

namespace vixikhd\duels\utils;

use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\player\Player;

/**
 * Class ServerManager
 * @package vixikhd\duels\utils
 */
class ServerManager
{

    /**
     * @param Player $player
     * @param string $server
     */
    public static function transferPlayer(Player $player, string $server)
    {
        $player->getEffects()->clear();
        $transferpk = new TransferPacket();
        $transferpk->address = $server;

        $player->getNetworkSession()->sendDataPacket($transferpk);
    }

}