<?php

declare(strict_types=1);

namespace vixikhd\duels\entity\types;


use pocketmine\entity\Human;
use vixikhd\duels\entity\EntityBase;

class JoinEntity extends Human implements EntityBase
{
	/** @var int */
	public $entityId;

    /** @var int */
    private static $entityCount = 1;

	/**
	 * @return int
	 */
	public static function nextRuntimeId(): int
	{
		return self::$entityCount++;
	}

    public function getEntityID(): int
    {
        return self::$entityCount++;
    }

    /**
	 * @return string
	 */
	public function getName(): string
	{
		return '';
	}
}