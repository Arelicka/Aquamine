<?php

declare(strict_types=1);

namespace pocketmine\level\generator\populator;

use pocketmine\block\Water;
use pocketmine\level\ChunkManager;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class Pond extends Populator{
	private $waterOdd = 4;
	private $lavaOdd = 4;
	private $lavaSurfaceOdd = 4;

	public function populate(ChunkManager $level, int $chunkX, int $chunkZ, Random $random){
		if($random->nextRange(0, $this->waterOdd) === 0){
			$x = $random->nextRange($chunkX << 4, ($chunkX << 4) + 16);
			$y = $random->nextBoundedInt(128);
			$z = $random->nextRange($chunkZ << 4, ($chunkZ << 4) + 16);
			$pond = new \pocketmine\level\generator\object\Pond($random, new Water());
			if($pond->canPlaceObject($level, $v = new Vector3($x, $y, $z))){
				$pond->placeObject($level, $v);
			}
		}
	}

	public function setWaterOdd(int $waterOdd){
		$this->waterOdd = $waterOdd;
	}

	public function setLavaOdd(int $lavaOdd){
		$this->lavaOdd = $lavaOdd;
	}

	public function setLavaSurfaceOdd(int $lavaSurfaceOdd){
		$this->lavaSurfaceOdd = $lavaSurfaceOdd;
	}
}