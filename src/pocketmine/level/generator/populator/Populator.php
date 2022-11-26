<?php

declare(strict_types=1);

/**
 * All the Object populator classes
 */
namespace pocketmine\level\generator\populator;

use pocketmine\level\ChunkManager;
use pocketmine\utils\Random;

abstract class Populator{

	/**
	 * @param ChunkManager $level
	 * @param int $chunkX
	 * @param int $chunkZ
	 * @param Random $random
	 *
	 * @return mixed
	 */
	abstract public function populate(ChunkManager $level, int $chunkX, int $chunkZ, Random $random);
}