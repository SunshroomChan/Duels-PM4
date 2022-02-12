<?php

namespace vixikhd\duels\arena;

use pocketmine\player\Player;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\item\Item;

class PlayerSnapshot
{

    /** @var EffectInstance[] */
    private $effects = [];

    /** @var float */
    private $health;

    /** @var int */
    private $maxHealth;

    /** @var float */
    private $food;

    /** @var float */
    private $saturation;

    /** @var Item[] */
    private $armor = [];

    /** @var Item[] */
    private $inventory = [];

    public function __construct(Player $player, bool $clear_inv = true, bool $clear_effects = true)
    {
        foreach ($player->getEffects() as $effect) {
            $this->effects[] = clone $effect;
        }

        $this->health = $player->getHealth();
        $this->maxHealth = $player->getMaxHealth();
        $this->food = $player->getHungerManager()->getFood();
        $this->saturation = $player->getHungerManager()->getSaturation();
        $this->inventory = $player->getInventory()->getContents();
        $this->armor = $player->getArmorInventory()->getContents();

        if ($clear_inv) {
            $player->getInventory()->clearAll();
            $player->getArmorInventory()->clearAll();
            $player->getCursorInventory()->clearAll();
            $player->getCraftingGrid()->clearAll();
        }

        if ($clear_effects) {
            $player->getEffects()->clear();
        }
    }

    public function injectInto(Player $player, bool $override = true): void
    {
        if ($override) {
            $player->getEffects()->clear();
            $player->getCursorInventory()->clearAll();
            $player->getCraftingGrid()->clearAll();
        }

        foreach ($this->effects as $effect) {
            $player->getEffects()->add($effect);
        }

        $player->getArmorInventory()->setContents($this->armor);
        $player->getInventory()->setContents($this->inventory);
        $player->setMaxHealth($this->maxHealth);
        $player->setHealth($this->health);
        $player->getHungerManager()->setFood($this->food);
        $player->getHungerManager()->setSaturation($this->saturation);
    }
}
