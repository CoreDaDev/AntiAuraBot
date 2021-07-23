<?php

namespace OguzhanUmutlu\AntiBot;

use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class AntiBot extends PluginBase {
    /*** @var Config */
    public static $config;
    public static $reports = [];
    public function onEnable() {
        $this->saveDefaultConfig();
        self::$config = $this->getConfig();
        Entity::registerEntity(BotEntity::class, true, ["AntiBotEntity"]);
        if(self::$config->getNested("report-command.enabled"))
            $this->getServer()->getCommandMap()->register($this->getName(), new ReportCommand());
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}