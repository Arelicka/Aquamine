<?php

declare(strict_types=1);

namespace pocketmine\network\bedrock\protocol;

#include <rules/DataPacket.h>

use pocketmine\math\Vector3;
use pocketmine\network\NetworkSession;

class AddPaintingPacket extends DataPacket{

	public const NETWORK_ID = ProtocolInfo::ADD_PAINTING_PACKET;

	/** @var int|null */
	public $actorUniqueId = null;
	/** @var int */
	public $actorRuntimeId;
	/** @var Vector3 */
	public $position;
	/** @var int */
	public $direction;
	/** @var string */
	public $title;

	public function decodePayload(){
		$this->actorUniqueId = $this->getActorUniqueId();
		$this->actorRuntimeId = $this->getActorRuntimeId();
		$this->position = $this->getVector3();
		$this->title = $this->getString();
	}

	public function encodePayload(){
		$this->putActorUniqueId($this->actorUniqueId ?? $this->actorRuntimeId);
		$this->putActorRuntimeId($this->actorRuntimeId);
		$this->putVector3($this->position);
		$this->putString($this->title);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddPainting($this);
	}
}
