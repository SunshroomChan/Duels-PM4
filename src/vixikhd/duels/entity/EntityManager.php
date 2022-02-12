<?php

declare(strict_types=1);

namespace vixikhd\skywars\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\Player;
use vixikhd\skywars\entity\types\JoinEntity;
use vixikhd\skywars\math\Vector3;
use pocketmine\Server;

class EntityManager
{
	/**
	 * @param Player $player
	 */
	public static function setJoinEntity(Player $player): void
	{
        $skinNBT = Server::getInstance()->getOfflinePlayerData($player->getName());
		$nbt = Entity::createBaseNBT(new Vector3((float)$player->getX(), (float)$player->getY(), (float)$player->getZ()));
		$nbt->setTag($skinNBT->getCompoundTag('Skin'));
		$human = new JoinEntity($player->getLevel(), $nbt);
		$human->setNameTag('');
		$human->setSkin($player->getSkin());
		$human->setNameTagVisible(true);
		$human->setScale(1.1);
		$human->setNameTagAlwaysVisible(true);
		$human->spawnToAll();
	}
}