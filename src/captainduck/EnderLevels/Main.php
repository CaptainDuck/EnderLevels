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
            $sender->sendMessage(C::ITALIC. C::GRAY. "----------- ". C::WHITE. "Your Stats: ". C::GRAY. "-----------");
            $sender->sendMessage(C::ITALIC. "Level: ". $this->getLevel($sender). " ");
            $sender->sendMessage(C::ITALIC. "Exp: ". $this->getExp($sender)."/".$this->getExpNeededTLU($sender). " ");
            $sender->sendMessage(C::ITALIC. "Kills: ". $this->getKills($sender). " ");
            $sender->sendMessage(C::ITALIC. "Deaths: ". $this->getDeaths($sender). " ");
            $sender->sendMessage(C::ITALIC. C::GRAY. "---------------------------------");
            break;

            case "levelup":
            $this->initializeLevel($sender);
            break;

            case "addexp":
            if(isset($args[0]) && isset($args[1]) && is_numeric($args[1])){
                $this->addExp($args[0], $args[1]);
                return true;
                break;
            }

            case "reduceexp":
            if(isset($args[0]) && is_numeric($args[0]) && isset($args[1])){
                $this->reduceExp($args[0], $args[1]);
                return true;
                break;
            }

            case "enchantshop":
            if($this->getLevel($sender) >= 20){
                $sender->teleport($sender->getServer()->getLevelByName("EnchantShop")->getSpawnLocation());
                $sender->sendMessage(C::ITALIC. "Teleported to the Enchant Shop!");
                return true;
            }else{
                $sender->sendMessage(C::ITALIC. C::RED. "You must be at least level 20 to go to the Enchant Shop!");
                break;
            }
        }
        return true;
    }

    ###########################################################################
    ########################### IMPORTANT API #################################
    ###########################################################################

    public function initializeLevel($player){
        $exp = $this->getExp($player);
        $expn = $this->getExpNeededTLU($player);
        if($this->getLevel($player) == 30){
            $player->sendMessage(C::ITALIC. C::RED. "You have already reached the max level, silly!");
        }
        if($exp >= $expn){
            $this->levelUp($player);
            $this->reduceExp($player, $expn);
            $this->setNamedTag($player);
            $this->addExpNeededTLU($player, $expn * 1);
            $player->sendMessage(C::ITALIC. "Successfully leveled up to ". $this->getLevel($player). "!");
        }else{
            $player->sendMessage(C::ITALIC. C::RED. "You don't have enough experience to level up!");
        }
    }

    public function levelUp($player){
        $this->stats->setNested(strtolower($player->getName()).".lvl", $this->stats->getAll()[strtolower($player->getName())]["lvl"] + 1);
        $this->stats->save();
        $this->setNamedTag($player);
        $this->getServer()->broadcastMessage(C::ITALIC. $player->getName(). " is now level ". $this->getLevel($player). "!");
    }

    public function setNamedTag($player){
        $player->setDisplayName(C::ITALIC. C::DARK_GRAY. "[". C::GREEN. "Lvl". C::WHITE. "" . $this->getLevel($player) . C::DARK_GRAY. "] ". C::WHITE . $player->getName());
        $player->save();
    }

    public function reduceExp($player, $exp){
        $this->stats->setNested(strtolower($player->getName()).".exp", $this->stats->getAll()[strtolower($player->getName())]["exp"] - $exp);
        $this->stats->save();
    }

    ###########################################################################
    ########################### ADD STATS API #################################
    ###########################################################################

    public function addPlayer($player){
        $this->stats->setNested(strtolower($player->getName()).".lvl", "1");
        $this->stats->setNested(strtolower($player->getName()).".exp", "0");
        $this->stats->setNested(strtolower($player->getName()).".expneededtlu", "250");
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

    public function addExp($player, $exp){
        $this->stats->setNested(strtolower($player).".exp", $this->stats->getAll()[strtolower($player)]["exp"] + $exp);
        $this->stats->save();
    }

    public function addExpNeededTLU($player, $exp){
        $this->stats->setNested(strtolower($player->getName()).".expneededtlu", $this->stats->getAll()[strtolower($player->getName())]["expneededtlu"] + $exp);
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
    public function getExp($player){
        return $this->stats->getAll()[strtolower($player->getName())]["exp"];
    }
    public function getLevel($player){
        return $this->stats->getAll()[strtolower($player->getName())]["lvl"];
    }
    public function getExpNeededTLU($player){
        return $this->stats->getAll()[strtolower($player->getName())]["expneededtlu"];
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

    public function addExpBreak(BlockBreakEvent $e){
        $pn = $e->getPlayer()->getName();
        $this->addExp($pn, 5);
    }

    public function addExpPlace(BlockPlaceEvent $e){
        $pn = $e->getPlayer()->getName();
        $this->addExp($pn, 5);
    }
}
