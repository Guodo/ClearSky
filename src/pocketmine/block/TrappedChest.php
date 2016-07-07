<?php
namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Chest as TileChest;
use pocketmine\tile\Tile;

class TrappedChest extends Transparent implements Redstone, RedstoneSwitch, RedstoneSource{

	protected $id = self::TRAPPED_CHEST;

	protected $redstoneSignalIsActive = false;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}
	
	public function isRedstoneSource(){
		return true;
	}

	public function isRedstoneSwitch(){
		return true;
	}

	public function canBeActivated(){
		return true;
	}

	public function getHardness(){
		return 2.5;
	}

	public function getName(){
		return "Trapped Chest";
	}

	public function getToolType(){
		return Tool::TYPE_AXE;
	}
	
	//TODO::checkIf_REDSTONEDELAY_shouldBeActuallyUsed
	public function BroadcastRedstoneUpdate($type,$power){
		for($side = 0; $side <= 5; $side++){
			$around=$this->getSide($side);
			$this->getLevel()->setRedstoneUpdate($around,Block::REDSTONEDELAY,$type,$power);
		}
	}

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_SCHEDULED){
			echo("BlockUpdate");
			if($this->redstoneSignalIsActive){
				echo("Deactivating");
				$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_BREAK,Block::REDSTONESOURCEPOWER);
			}
		}
	}
	
	protected function recalculateBoundingBox(){
		return new AxisAlignedBB(
			$this->x + 0.0625,
			$this->y,
			$this->z + 0.0625,
			$this->x + 0.9375,
			$this->y + 0.9475,
			$this->z + 0.9375
		);
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$faces = [
			0 => 4,
			1 => 2,
			2 => 5,
			3 => 3,
		];

		$chest = null;
		$this->meta = $faces[$player instanceof Player ? $player->getDirection() : 0];

		for($side = 2; $side <= 5; ++$side){
			if(($this->meta === 4 or $this->meta === 5) and ($side === 4 or $side === 5)){
				continue;
			}elseif(($this->meta === 3 or $this->meta === 2) and ($side === 2 or $side === 3)){
				continue;
			}
			$c = $this->getSide($side);
			if($c instanceof Chest and $c->getDamage() === $this->meta){
				$tile = $this->getLevel()->getTile($c);
				if($tile instanceof TileChest and !$tile->isPaired()){
					$chest = $tile;
					break;
				}
			}
		}

		$this->getLevel()->setBlock($block, $this, true, true);
		$nbt = new CompoundTag("", [
			new ListTag("Items", []),
				new StringTag("id", Tile::CHEST),
			new IntTag("x", $this->x),
			new IntTag("y", $this->y),
			new IntTag("z", $this->z)
		]);
		$nbt->Items->setTagType(NBT::TAG_Compound);

		if($item->hasCustomName()){
			$nbt->CustomName = new StringTag("CustomName", $item->getCustomName());
		}

		if($item->hasCustomBlockData()){
			foreach($item->getCustomBlockData() as $key => $v){
				$nbt->{$key} = $v;
			}
		}

		$tile = Tile::createTile("Chest", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);

		if($chest instanceof TileChest and $tile instanceof TileChest){
			$chest->pairWith($tile);
			$tile->pairWith($chest);
		}

		return true;
	}

	public function onBreak(Item $item){
		$t = $this->getLevel()->getTile($this);
		if($t instanceof TileChest){
			$t->unpair();
		}
		$this->getLevel()->setBlock($this, new Air(), true, true);

		return true;
	}

	public function onActivate(Item $item, Player $player = null){
		if($player instanceof Player){
			$top = $this->getSide(1);
			if($top->isTransparent() !== true){
				return true;
			}
			echo("ActivatingRedstone");
			$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_PLACE,Block::REDSTONESOURCEPOWER);
			$this->getLevel()->scheduleUpdate($this, 2); #TODO:changeThisToSthBetter (Redstone should manage scheuldedRedstoneUpdates [Especially for repeaters])
			$this->redstoneSignalIsActive = true;
			$t = $this->getLevel()->getTile($this);
			$chest = null;
			if($t instanceof TileChest){
				$chest = $t;
			}else{
				$nbt = new CompoundTag("", [
					new ListTag("Items", []),
						new StringTag("id", Tile::CHEST),
					new IntTag("x", $this->x),
					new IntTag("y", $this->y),
					new IntTag("z", $this->z)
				]);
				$nbt->Items->setTagType(NBT::TAG_Compound);
				$chest = Tile::createTile("Chest", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);
			}

			if(isset($chest->namedtag->Lock) and $chest->namedtag->Lock instanceof StringTag){
				if($chest->namedtag->Lock->getValue() !== $item->getCustomName()){
					return true;
				}
			}
			
				$player->addWindow($chest->getInventory());
			}

		return true;
	}

	public function getDrops(Item $item){
		return [
			[$this->id, 0, 1],
		];
	}
}
