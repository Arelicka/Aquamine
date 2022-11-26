<?php

declare(strict_types=1);

namespace pocketmine\level\generator\object;

use pocketmine\block\Block;
use pocketmine\level\ChunkManager;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class Pond{
	private $random;
	public $type;

	public function __construct(Random $random, Block $type){
		$this->type = $type;
		$this->random = $random;
	}

	public function canPlaceObject(ChunkManager $level, Vector3 $pos) : bool{
		return false;
	}

	public function placeObject(ChunkManager $level, Vector3 $pos){

	}

}