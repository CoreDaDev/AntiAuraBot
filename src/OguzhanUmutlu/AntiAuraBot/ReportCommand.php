<?php

namespace OguzhanUmutlu\AntiAuraBot;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;

class ReportCommand extends Command {
    public function __construct() {
        parent::__construct(AntiAuraBot::$config->getNested("report-command.name"), AntiAuraBot::$config->getNested("report-command.description"), null, []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!isset($args[0])) {
            $sender->sendMessage("§c> Usage: /".$this->getName()." <player".">");
            return;
        }
        $player = Server::getInstance()->getPlayerExact($args[0]);
        if(!$player instanceof Player) {
            $sender->sendMessage("§c> Player not found!");
            return;
        }
        if($player->getName() == $sender->getName()) {
            $sender->sendMessage("§c> You cannot report yourself!");
            return;
        }
        if(isset(AntiAuraBot::$reports[$sender->getName()])) {
            $sender->sendMessage("§c> You already reported someone!");
            return;
        }
        if(!in_array($player->getName(), AntiAuraBot::$reports))
            AntiAuraBot::$reports[$sender->getName()] = $player->getName();
        foreach(Server::getInstance()->getOnlinePlayers() as $p)
            if($p->hasPermission("anti"."bot.staff"))
                $p->sendMessage("§e> Player " .$sender->getName()." reported ".$player->getName()."!");
        $sender->sendMessage("§a> Player reported!");
    }
}