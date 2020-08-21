<?php

namespace captainduck\EnderLevels;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\utils\TextFormat as C;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class Main extends PluginBase implements Listener{

    ###########################################################################
    ########################### IMPORTANT THINGS #################################
    ###########################################################################

    public function onEnable(){
        $this->getLogger()->info("EnderLevels by CaptainDuck now enabled!");
        $this->stats = new Config($this->getDataFolder() . "stats.yml", Config::YAML, array());
        if(!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args) : bool{
        switch (strtolower($command->getName())) {
            case "stats":
            $sender->sendMessage(C::ITALIC. C::GRAY. "------------". C::GOLD. "Your Statistics". C::GRAY. "-----------");
            $sender->sendMessage(C::ITALIC. "Level: ". $this->getLevel($sender). " ");
            $sender->sendMessage(C::ITALIC. "XP: ". $this->getxp($sender)."/".$this->getxpNeededTLU($sender). " ");
            $sender->sendMessage(C::ITALIC. "Kills: ". $this->getKills($sender). " ");
            $sender->sendMessage(C::ITALIC. "Deaths: ". $this->getDeaths($sender). " ");
            $sender->sendMessage(C::ITALIC. C::GRAY. "---------------------------------");
            break;

            case "levelup":
            $this->initializeLevel($sender);
            break;

            case "addxp":
            if(isset($args[0]) && isset($args[1]) && is_numeric($args[1])){
                $this->addxp($args[0], $args[1]);
                return true;
                break;
            }

            case "reducexp":
            if(isset($args[0]) && is_numeric($args[0]) && isset($args[1])){
                $this->reducexp($args[0], $args[1]);
                return true;
                break;
            }

    ###########################################################################
    ########################### IMPORTANT API #################################
    ###########################################################################

    public function initializeLevel($player){
        $exp = $this->getxp($player);
        $expn = $this->getxpNeededTLU($player);
        if($this->getLevel($player) == 100){
            $player->sendMessage(C::ITALIC. C::RED. "You have already reached the maximum level!");
        }
        if($xp >= $xpn){
            $this->levelUp($player);
            $this->reducexp($player, $xpn);
            $this->setNamedTag($player);
            $this->addExpNeededTLU($player, $xpn * 1);
            $player->sendMessage(C::ITALIC. "Successfully leveled up to ". $this->getLevel($player). "!");
        }else{
            $player->sendMessage(C::ITALIC. C::RED. "You don't have enough experience to level up!");
        }
    }

    public function levelUp($player){
        $this->stats->setNested(strtolower($player->getName()).".lvl", $this->stats->getAll()[strtolower($player->getName())]["lvl"] + 1);
        $this->stats->save();
        $this->setNamedTag($player);
        $this->getServer()->broadcastMessage(C::BOLD. C::GREEN. $player->getName(). " is now level ". $this->getLevel($player). "!");
    }

    public function setNamedTag($player){
        $player->setDisplayName(C::GREEN. $this->getLevel($player) . C::WHITE. $player->getName());
        $player->save();
    }

    public function reducexp($player, $xp){
        $this->stats->setNested(strtolower($player->getName()).".xp", $this->stats->getAll()[strtolower($player->getName())]["xp"] - $xp);
        $this->stats->save();
    }

    ###########################################################################
    ########################### ADD STATS API #################################
    ###########################################################################

    public function addPlayer($player){
        $this->stats->setNested(strtolower($player->getName()).".lvl", "1");
        $this->stats->setNested(strtolower($player->getName()).".xp", "0");
        $this->stats->setNested(strtolower($player->getName()).".xpneededtlu", "250");
        $this->stats->setNested(strtolower($player->getName()).".kills", "0");
        $this->stats->setNested(strtolower($player->getName()).".deaths", "0");
        $this->stats->save();
    }

    public function addDeath($player){
         $this->stats->setNested(strtolower($player->getName()).".deaths", $this->stats->getAll()[strtolower($player->getName())]["deaths"] + 1);
         $this->stats->save();
    }

    public function addKill($player){
         $this->stats->setNested(strtolower($player->getName()).".kills", $this->stats->getAll()[strtolower($player->getName())]["kills"] + 1);
         $this->stats->save();
    }

    public function addxp($player, $xp){
        $this->stats->setNested(strtolower($player).".xp", $this->stats->getAll()[strtolower($player)]["xp"] + $xp);
        $this->stats->save();
    }

    public function addxpNeededTLU($player, $xp){
        $this->stats->setNested(strtolower($player->getName()).".xpneededtlu", $this->stats->getAll()[strtolower($player->getName())]["xpneededtlu"] + $xp);
        $this->stats->save();
    }

    ###########################################################################
    ########################### GET STATS API #################################
    ###########################################################################

    public function getDeaths($player){
        return $this->stats->getAll()[strtolower($player->getName())]["deaths"];
    }
    public function getKills($player){
        return $this->stats->getAll()[strtolower($player->getName())]["kills"];
    }
    public function getxp($player){
        return $this->stats->getAll()[strtolower($player->getName())]["xp"];
    }
    public function getLevel($player){
        return $this->stats->getAll()[strtolower($player->getName())]["lvl"];
    }
    public function getxpNeededTLU($player){
        return $this->stats->getAll()[strtolower($player->getName())]["xpneededtlu"];
    }

    ###########################################################################
    ############################## EVENTS #####################################
    ###########################################################################

    public function onJoin(PlayerJoinEvent $e){
        $p = $e->getPlayer();
        if(!$this->stats->exists(strtolower($p->getName()))){
            $this->addPlayer($p);
        }
        $this->setNamedTag($p);
    }

    public function onKillDeath(PlayerDeathEvent $event) {
        $this->addDeath($event->getEntity());
        if($event->getEntity()->getLastDamageCause() instanceof EntityDamageByEntityEvent) {
            $killer = $event->getEntity()->getLastDamageCause()->getDamager();
            if($killer instanceof Player) {
                $this->addKill($killer);
            }
        }
    }

    public function addxpBreak(BlockBreakEvent $e){
        $pn = $e->getPlayer()->getName();
        $this->addxp($pn, 5);
    }

    public function addxpPlace(BlockPlaceEvent $e){
        $pn = $e->getPlayer()->getName();
        $this->addxp($pn, 5);
    }
}
