<?php

declare(strict_types=1);

namespace pocketmine\network\bedrock\protocol\types;

final class MaterialReducerRecipeOutput{

	public $itemId;
    public $count;

	public function __construct(int $itemId, int $count){
		$this->itemId = $itemId;
		$this->count = $count;
	}

	public function getItemId() : int{ return $this->itemId; }

	public function getCount() : int{ return $this->count; }
}
