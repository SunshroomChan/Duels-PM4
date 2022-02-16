<?php

declare(strict_types=1);

namespace vixikhd\duels\entity;

use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\SetActorDataPacket as SetEntityDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use vixikhd\duels\arena\Arena;
use vixikhd\duels\Duels;

class TOMBHuman extends Human{

    /** @var TOMBHuman */
    protected $tombhuman;

    public function __construct(Location $location, TOMBHuman $tombhuman) {
        $this->tombhuman = $tombhuman;
        parent::__construct($location, $tombhuman->getSkin());
        $this->getDataPropertyManager()->setFloat(EntityMetadataProperties::SCALE, 2);
        $this->setScale(2);
        $this->setNameTagVisible(false);
        $this->setNameTagAlwaysVisible(false);
    }
}