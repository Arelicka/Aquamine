<?php

declare(strict_types=1);

namespace pocketmine\network\bedrock;

use pocketmine\network\bedrock\protocol\LoginPacket;
use pocketmine\Player;

class VerifyLoginTask extends \pocketmine\network\mcpe\VerifyLoginTask{

	public function __construct(Player $player, LoginPacket $packet){
		$this->chainJwts = igbinary_serialize($packet->chainData["chain"]);
		$this->clientDataJwt = $packet->clientDataJwt;
		$this->storeLocal([$player, $packet]);
	}
}