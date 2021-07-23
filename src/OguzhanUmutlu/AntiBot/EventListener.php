<?php

namespace OguzhanUmutlu\AntiBot;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

class EventListener implements Listener {
    /*** @var int[][] */
    public $aps = [];
    /*** @var string[] */
    public static $report = [];
    public function onDamage(EntityDamageEvent $event) {
        $player = $event->getEntity();
        if(!$player instanceof Player || !$event instanceof EntityDamageByEntityEvent) return;
        $damage = $event->getDamager();
        if(!$damage instanceof Player) return;
        if(!isset($this->aps[$damage->getName()]) || $this->aps[$damage->getName()][1] < time())
            $this->aps[$damage->getName()] = [0, time()];
        $this->aps[$damage->getName()][0]++;
        if(isset(self::$report[$damage->getName()])) return;
        if((in_array($damage->getName(), AntiBot::$reports) && $this->aps[$damage->getName()][0] > 4) || (AntiBot::$config->getNested("auto-report.enabled") && $this->aps[$damage->getName()][0] > AntiBot::$config->getNested("auto-report.warns"))) {
            self::$report[$damage->getName()] = true;
            if(in_array($damage->getName(), AntiBot::$reports))
                unset(AntiBot::$reports[array_search($damage->getName(), AntiBot::$reports)]);
            $nbt = Entity::createBaseNBT($damage->add($damage->getDirectionVector()->multiply(-1)));
            $nbt->setTag(new CompoundTag("Skin", [
                new StringTag("Name", $player->getSkin()->getSkinId()),
                new ByteArrayTag("Data", $player->getSkin()->getSkinData()),
                new StringTag("GeometryName", $player->getSkin()->getGeometryName()),
                new ByteArrayTag("GeometryData", $player->getSkin()->getGeometryData())
            ]));
            $bot = Entity::createEntity("AntiBotEntity", $damage->level, $nbt);
            if(!$bot instanceof BotEntity) return;
            $bot->hacker = $damage;
            $bot->hackerName = $damage->getName();
            $bot->setNameTag($player->getNameTag());
            $bot->setNameTagVisible();
            $bot->setNameTagAlwaysVisible();
            $bot->setScale($player->getScale());
            $bot->setHealth($player->getHealth());
            $bot->setMaxHealth($player->getMaxHealth());
            $bot->setScoreTag($player->getScoreTag() ?? "");
            $bot->lookAt($damage);
            $bot->spawnTo($damage);
        }
    }
}