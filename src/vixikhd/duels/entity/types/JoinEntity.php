<?php

declare(strict_types=1);

namespace vixikhd\duels\entity\types;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use vixikhd\duels\entity\EntityBase;

class JoinEntity extends Human implements EntityBase
{
	/** @var int */
	public $entityId;

	/**
	 * @return int
	 */
	public function getEntityID(): int
	{
		$this->entityId = Entity::$entityCount++;
		return $this->entityId;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return '';
	}
}