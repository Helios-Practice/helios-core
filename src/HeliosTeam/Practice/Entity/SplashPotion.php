<?php

namespace HeliosTeam\Practice\Entity;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\Potion;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\Color;
use pocketmine\utils\Random;
use function count;
use function round;
use function sqrt;

class SplashPotion extends Throwable {

    public const NETWORK_ID = self::SPLASH_POTION;

    protected $gravity = 0.05;
    protected $drag = 0.01;


    public function __construct(Level $level, CompoundTag $nbt, ?Entity $owner=null){
        parent::__construct($level, $nbt, $owner);
        if($owner===null) return;
        $this->setPosition($this->add(0, $owner->getEyeHeight()));
        //$this->handleMotion($this->motion->x, $this->motion->y, $this->motion->z, -0.45, 0);
        $this->handleMotion($this->motion->x, $this->motion->y, $this->motion->z, -0.47, 0);
    }

    protected function initEntity() : void{
        parent::initEntity();

        $this->setPotionId($this->namedtag->getShort("PotionId", 0));
    }

    public function saveNBT() : void{
        parent::saveNBT();
        $this->namedtag->setShort("PotionId", $this->getPotionId());
    }

    public function getResultDamage() : int{
        return -1; //no damage
    }

    protected function onHit(ProjectileHitEvent $event) : void{
        $effects = $this->getPotionEffects();
        $hasEffects = true;

        if(count($effects)===0){
            $colors=[new Color(0x38, 0x5d, 0xc6)];
            $hasEffects=false;
        }else{
            //$colors=[new Color(0xf8, 0x24, 0x23)]; DEFAULT RED
            $colors=[new Color(0, 0, 255)];
            $hasEffects=true;
        }

        $this->level->broadcastLevelEvent($this, LevelEventPacket::EVENT_SOUND_FIZZ, Color::mix(...$colors)->toARGB());
        $this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_BUBBLE_UP);

        if($hasEffects){
            if(!$this->willLinger()){
                foreach($this->level->getNearbyEntities($this->boundingBox->expandedCopy(4.125, 2.125, 4.125), $this) as $entity){
                    if($entity instanceof Living and $entity->isAlive()){
                        $distanceSquared = $entity->add(0, $entity->getEyeHeight(), 0)->distanceSquared($this);
                        if($distanceSquared > 16){ //4 blocks
                            continue;
                        }

                        $distanceMultiplier = 1 - (sqrt($distanceSquared) / 4);
                        if($event instanceof ProjectileHitEntityEvent and $entity === $event->getEntityHit()){
                            $distanceMultiplier = 1.0;
                        }

                        foreach($this->getPotionEffects() as $effect){
                            //getPotionEffects() is used to get COPIES to avoid accidentally modifying the same effect instance already applied to another entity

                            if(!$effect->getType()->isInstantEffect()){
                                $newDuration = (int) round($effect->getDuration() * 0.75 * $distanceMultiplier);
                                if($newDuration < 20){
                                    continue;
                                }
                                $effect->setDuration($newDuration);
                                $entity->addEffect($effect);
                            }else{
                                $effect->getType()->applyEffect($entity, $effect, $distanceMultiplier, $this, $this->getOwningEntity());
                            }
                        }
                    }
                }
            }else{
                //TODO: lingering potions
            }
        }elseif($event instanceof ProjectileHitBlockEvent and $this->getPotionId() === Potion::WATER){
            $blockIn = $event->getBlockHit()->getSide($event->getRayTraceResult()->getHitFace());

            if($blockIn->getId() === Block::FIRE){
                $this->level->setBlock($blockIn, BlockFactory::get(Block::AIR));
            }
            foreach($blockIn->getHorizontalSides() as $horizontalSide){
                if($horizontalSide->getId() === Block::FIRE){
                    $this->level->setBlock($horizontalSide, BlockFactory::get(Block::AIR));
                }
            }
        }
    }

    /**
     * Returns the meta value of the potion item that this splash potion corresponds to. This decides what effects will be applied to the entity when it collides with its target.
     */
    public function getPotionId() : int{
        return $this->propertyManager->getShort(self::DATA_POTION_AUX_VALUE) ?? 0;
    }

    public function setPotionId(int $id) : void{
        $this->propertyManager->setShort(self::DATA_POTION_AUX_VALUE, $id);
    }

    /**
     * Returns whether this splash potion will create an area-effect cloud when it lands.
     */
    public function willLinger() : bool{
        return $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_LINGER);
    }

    /**
     * Sets whether this splash potion will create an area-effect-cloud when it lands.
     */
    public function setLinger(bool $value = true) : void{
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_LINGER, $value);
    }

    public function handleMotion(float $x, float $y, float $z, float $f1, float $f2)
    {
        $rand=new Random();
        $f=sqrt($x * $x + $y * $y + $z * $z);
        $x=$x / (float)$f;
        $y=$y / (float)$f;
        $z=$z / (float)$f;
        $x=$x + $rand->nextSignedFloat() * 0.007499999832361937 * (float)$f2;
        $y=$y + $rand->nextSignedFloat() * 0.008599999832361937 * (float)$f2;
        $z=$z + $rand->nextSignedFloat() * 0.007499999832361937 * (float)$f2;
        $x=$x * (float)$f1;
        $y=$y * (float)$f1;
        $z=$z * (float)$f1;
        $this->motion->x += $x;
        $this->motion->y += $y;
        $this->motion->z += $z;
    }

    /**
     * @return EffectInstance[]
     */
    public function getPotionEffects() : array{
        return Potion::getPotionEffectsById($this->getPotionId());
    }
}