<?php

declare(strict_types=1);

namespace vixikhd\duels\task;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use vixikhd\duels\arena\Arena;
use vixikhd\duels\entity\types\JoinEntity;
use vixikhd\duels\Duels;

class EntityJoinTask extends Task
{

	/**
	 * @inheritDoc
	 */
	public function onRun(): void
	{
		foreach (Server::getInstance()->getWorldManager()->getDefaultWorld()->getEntities() as $entity) {
			if ($entity instanceof JoinEntity){
				$entity->setNameTagAlwaysVisible(true);
				$entity->setNameTagVisible(true);
				$entity->setNameTag($this->getNametag());
			}
		}
	}

	public function getNametag(): string
	{
		return '§l§eDUELS SOLO' . "\n" . "§a" . Arena::getPlayersOnline() . " §dPlayers\n\n";
	}
}