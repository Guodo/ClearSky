<?php
namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>


class LoginPacket extends DataPacket{
	const NETWORK_ID = Info::LOGIN_PACKET;

	public $username;
	public $protocol;

	public $clientUUID;
	public $clientId;
	public $identityPublicKey;
	public $serverAddress;
	public $additionalChar;

	public $skinID;
	public $skin = null;

	public function decode(){
		$addCharNumber = $this->getByte();
		if($addCharNumber > 0) {
			$this->additionalChar = chr($addCharNumber);
		}
		if($addCharNumber == 0xfe) {			
			$this->protocol = $this->getInt();			
			$bodyLength = $this->getInt();
			$body =  \zlib_decode($this->get($bodyLength));
			$this->chainsDataLength = Binary::readLInt($this->getFromString($body, 4));
			$this->chains = json_decode($this->getFromString($body, $this->chainsDataLength), true);
			
			
			$this->playerDataLength = Binary::readLInt($this->getFromString($body, 4));
			$this->playerData = $this->getFromString($body, $this->playerDataLength);
			$this->chains['data'] = array();
			$index = 0;			
			foreach ($this->chains['chain'] as $key => $jwt) {
				$data = self::load($jwt);
				if(isset($data['extraData'])) {
					$dataIndex = $index;
				}
				$this->chains['data'][$index] = $data;
				$index++;
			}
		
			$this->playerData = self::load($this->playerData);
			$this->username = $this->chains['data'][$dataIndex]['extraData']['displayName'];
			$this->clientId = $this->chains['data'][$dataIndex]['extraData']['identity'];
			if(isset($this->chains['data'][$dataIndex]['extraData']['XUID'])) {
				$this->clientUUID = UUID::fromBinary($this->chains['data'][$dataIndex]['extraData']['XUID']);
			}else{
				$this->clientUUID = UUID::fromBinary(substr($this->playerData['ClientRandomId'], 0, 16));
			}
			$this->identityPublicKey = $this->chains['data'][$dataIndex]['identityPublicKey'];
			
			$this->serverAddress = $this->playerData['ServerAddress'];
			$this->skinID = $this->playerData['SkinId'];
			$this->skin = base64_decode($this->playerData['SkinData']);
		}else{
			$this->username = $this->getString();
			$this->protocol = $this->getInt();
			$this->idk = $this->getInt();

			$this->clientId = $this->getLong();
			$this->clientUUID = $this->getUUID();
			$this->serverAddress = $this->getString();
			$this->clientSecret = $this->getString();

			$this->skinId = $this->getString();
			$this->skin = $this->getString();
			/*
			$skinToken = $this->decodeToken($this->get($this->getLInt()));
			if(isset($skinToken["ClientRandomId"])){
				$this->clientId = $skinToken["ClientRandomId"];
			}
			if(isset($skinToken["ServerAddress"])){
				$this->serverAddress = $skinToken["ServerAddress"];
			}
			if(isset($skinToken["SkinData"])){
				$this->skin = base64_decode($skinToken["SkinData"]);
			}
			if(isset($skinToken["SkinId"])){
				$this->skinId = $skinToken["SkinId"];
			}
			*/
		}
		var_dump($protocol);
	}

	public function encode(){

	}

	public function decodeToken($token){
		$tokens = explode(".", $token);
		list($headB64, $payloadB64, $sigB64) = $tokens;

		return json_decode(base64_decode($payloadB64), true);
	}
}
