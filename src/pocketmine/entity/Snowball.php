<?php

declare(strict_types=1);

namespace pocketmine\entity;

use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\level\particle\SnowballPoofParticle;

class Snowball extends Throwable{

	public const NETWORK_ID = self::SNOWBALL;

	protected function onHit(ProjectileHitEvent $event) : void{
		for($i = 0; $i < 6; ++$i){
			$this->level->addParticle(new SnowballPoofParticle($this));
		}
	}
}
