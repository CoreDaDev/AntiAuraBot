<?php

namespace OguzhanUmutlu\AntiBot;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

class BotEntity extends Human {
    /*** @var Player|null */
    public $hacker;
    public $hackerName = "";
    public $teleportTick = 0;
    public $fullTicks = 0;
    public function onUpdate(int $currentTick): bool {
        $this->fullTicks++;
        if(!$this->hacker instanceof Player || $this->hacker->isClosed() || !$this->level || !$this->hacker->boundingBox || $this->fullTicks >= AntiBot::$config->getNested("de"."spawn-seconds")*20) {
            $this->flagForDespawn();
            return false;
        }
        switch(AntiBot::$config->getNested("teleport.type")) {
            case "turn":
                $this->teleport(
                    $this->hacker->add(
                        $this->getCustomVector()->multiply(
                            -AntiBot::$config->getNested("teleport.back-multiplier")[array_rand(AntiBot::$config->getNested("teleport.back-multiplier"))]
                        )
                    )->add(
                        0, AntiBot::$config->getNested("teleport.turn-y")[array_rand(AntiBot::$config->getNested("teleport.turn-y"))]
                    ),
                    $this->yaw,
                    $this->pitch
                );
                $this->yaw+=AntiBot::$config->getNested("teleport.turn-speed")[array_rand(AntiBot::$config->getNested("teleport.turn-speed"))];
                if($this->yaw > 360)
                    $this->yaw-=360;
                break;
            case "fast-teleport":
            case "fastTeleport":
                if($this->teleportTick >= AntiBot::$config->getNested("teleport.teleport-speed")) {
                    $expand = AntiBot::$config->getNested("teleport.teleport-range");
                    $expand = $expand[array_rand($expand)];
                    $airs = array_filter($this->getNearBlocks($this->hacker, $expand), function($block){
                        $similarity = abs($this->calculateLookHitBox($this->hacker, $block)-(($this->hacker->yaw+$this->hacker->pitch)/2));
                        return $block->getId() == 0 && $similarity > 80;
                    });
                    if(empty($airs)) break;
                    $this->teleportTick = 0;
                    $this->teleport($airs[array_rand($airs)]);
                    $this->lookAt($this->hacker);
                } else $this->teleportTick++;
                break;
            case "back-side":
            case "backside":
            case "backSide":
                $this->lookAt($this->hacker);
                $this->teleport($this->hacker->add($this->getCustomHackerVector()->multiply(-AntiBot::$config->getNested("teleport.back-multiplier")[array_rand(AntiBot::$config->getNested("teleport.back-multiplier"))])));
                break;
        }
        return true;
    }

    public function getCustomVector(): Vector3 {
        $y = -sin(deg2rad(0));
        $xz = cos(deg2rad(0));
        $x = -$xz * sin(deg2rad($this->yaw));
        $z = $xz * cos(deg2rad($this->yaw));
        return $this->temporalVector->setComponents($x, $y, $z)->normalize();
    }

    public function getCustomHackerVector(): Vector3 {
        $y = -sin(deg2rad(0));
        $xz = cos(deg2rad(0));
        $x = -$xz * sin(deg2rad($this->hacker->yaw));
        $z = $xz * cos(deg2rad($this->hacker->yaw));
        return $this->temporalVector->setComponents($x, $y, $z)->normalize();
    }

    public function calculateLookHitBox(Player $player, Vector3 $target) {
        $horizontal = sqrt(($target->x - $player->x) ** 2 + ($target->z - $player->z) ** 2);
        $vertical = $target->y - $player->y;
        $pitch = -atan2($vertical, $horizontal) / M_PI * 180;
        $xDist = $target->x - $player->x;
        $zDist = $target->z - $player->z;
        $yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
        if($yaw < 0)
            $yaw += 360.0;
        return ($yaw+$pitch)/2;
    }

    public function getNearBlocks(Player $player, int $length) : array{
        $blocks = [];
        for($x=$player->x-$length;$x<=$player->x+$length;$x++)
            for($y=$player->y-$length;$y<=$player->y+$length;$y++)
                for($z=$player->z-$length;$z<=$player->z+$length;$z++)
                    $blocks[] = $player->level->getBlock(new Vector3($x, $y, $z));
        return $blocks;
    }
    public $warns = 0;
    public function attack(EntityDamageEvent $source): void {
        if($source instanceof EntityDamageByEntityEvent && $source->getDamager()->id == $this->hacker->id)
            $this->warns++;
        if($this->hacker instanceof Player && $this->hacker->isOnline() && $this->warns >= AntiBot::$config->getNested("warns"))
            switch(AntiBot::$config->getNested("punishment")) {
                case "kick":
                    $this->hacker->kick(AntiBot::$config->getNested("reason"), false);
                    break;
                case "ban":
                    $this->server->getNameBans()->addBan($this->getName(), AntiBot::$config->getNested("reason"), null, "AntiBot AC");
                    $this->hacker->kick(AntiBot::$config->getNested("reason"), false);
                    break;
                default:
                    $cmd = AntiBot::$config->getNested("punishment");
                    if(is_array($cmd))
                        foreach($cmd as $c) {
                            Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $c);
                        }
                    else Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $cmd);
                    break;
            }
        parent::attack($source);
    }
    public function kill(): void {$this->setHealth(20);}

    public function flagForDespawn(): void {
        unset(EventListener::$report[$this->hackerName]);
        parent::flagForDespawn();
    }
}