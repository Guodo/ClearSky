<?php
namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>


class ContainerSetDataPacket extends DataPacket{
	const NETWORK_ID = Info::CONTAINER_SET_DATA_PACKET;

	public $windowid;
	public $property;
	public $value;

	public function decode(){
		$this->windowid = $this->getByte();
		$this->property = $this->getVarInt();
		$this->value = $this->getVarInt();
	}

	public function encode(){
		$this->reset();
		$this->putByte($this->windowid);
		$this->putVarInt($this->property);
		$this->putVarInt($this->value);
	}

}
