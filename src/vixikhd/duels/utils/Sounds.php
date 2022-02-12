<?php

declare(strict_types=1);

namespace vixikhd\duels\utils;

use pocketmine\world\sound\AnvilUseSound;
use pocketmine\world\sound\ClickSound;

/**
 * Class Sounds
 * @package duels\utils
 */
class Sounds
{

    /**
     * @param string $name
     *
     * @return string $class
     */
    public static function getSound(string $name): string
    {
        switch (strtolower($name)) {
            case "click":
            case "clicksound":
                return ClickSound::class;
            case "anvil":
            case "anviluse":
            case "anvilusesound":
                return AnvilUseSound::class;
        }
    }
}