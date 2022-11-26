<?php

declare(strict_types=1);

namespace pocketmine\level\generator\object;

use pocketmine\block\Block;

class OreType{

	/** @var Block */
	public $material;
	/** @var int */
	public $clusterCount;
	/** @var int */
	public $clusterSize;
	/** @var int */
	public $maxHeight;
	/** @var int */
	public $minHeight;

	public function __construct(Block $material, int $clusterCount, int $clusterSize, int $minHeight, int $maxHeight){
		$this->material = $material;
		$this->clusterCount = $clusterCount;
		$this->clusterSize = $clusterSize;
		$this->maxHeight = $maxHeight;
		$this->minHeight = $minHeight;
	}
}