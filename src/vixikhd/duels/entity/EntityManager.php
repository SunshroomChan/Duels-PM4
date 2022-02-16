<?php

declare(strict_types=1);

namespace vixikhd\duels\entity;

use pocketmine\player\Player;
use vixikhd\duels\entity\types\JoinEntity;
use vixikhd\duels\math\Vector3;
use vixikhd\duels\entity\types\BaseNBT;
use pocketmine\Server;

class EntityManager
{
    /**
     * @param Player $player
     */
    public static function setJoinEntity(Player $player): void
    {
        $skinNBT = Server::getInstance()->getOfflinePlayerData($player->getName());
        $nbt = BaseNBT::createBaseNBT(new Vector3((float)$player->getPosition()->getX(), (float)$player->getPosition()->getY(), (float)$player->getPosition()->getZ()));
        $nbt->setTag('Skin', $skinNBT->getCompoundTag('Skin'));
        $human = new JoinEntity($player->getWorld(), $nbt);
        $human->setNameTag('');
        $human->setSkin($player->getSkin());
        $human->setNameTagVisible(true);
        $human->setScale(1.1);
        $human->setNameTagAlwaysVisible(true);
        $human->spawnToAll();
    }

}