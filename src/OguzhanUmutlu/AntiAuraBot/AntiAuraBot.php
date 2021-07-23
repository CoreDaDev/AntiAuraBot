<?php

namespace OguzhanUmutlu\AntiAuraBot;

use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class AntiAuraBot extends PluginBase {
    /*** @var Config */
    public static $config;
    public static $reports = [];
    public function onEnable() {
        $this->saveDefaultConfig();
        self::$config = $this->getConfig();
        Entity::registerEntity(BotEntity::class, true, ["AntiAuraBotEntity"]);
        if(self::$config->getNested("report-command.enabled"))
            $this->getServer()->getCommandMap()->register($this->getName(), new ReportCommand());
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}