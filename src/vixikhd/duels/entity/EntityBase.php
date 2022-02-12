<?php

declare(strict_types=1);

namespace vixikhd\duels\entity;

interface EntityBase

	public function getName(): string;

	public function getEntityID(): int;
}