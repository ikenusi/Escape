<?php

namespace Escape;

use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\Listener;

use pocketmine\plugin\PluginBase;

class Escape extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function Precommand(PlayerCommandPreProcessEvent $event){
		$m = $event->getMessage();
		if(substr($m,0,1) == "/"){
			if($this->run(substr($m,1), $event->getPlayer())) $event->setCancelled();
		}
	}

	public function onChat(PlayerChatEvent $event){
		$m = $event->getMessage();
		if(preg_match_all('#@([@A-Za-z_]{1,})#', $m, $matches, PREG_OFFSET_CAPTURE) > 0){
			$offsetshift = 0;
			foreach($matches[1] as $selector){
				if($selector[0]{0} === "@"){ //Escape!
					$m = substr_replace($m, $selector[0], $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1);
					--$offsetshift;
					continue;
				}
				switch(strtolower($selector[0])){
					case "p":
					case "player":
					case "u":
					case "username":
						foreach($this->getServer()->getOnlinePlayers() as $p){
							$p->sendMessage($this->getServer()->getLanguage()->translateString($event->getFormat(), array($event->getPlayer()->getDisplayName(), substr_replace($m, $p->getName(), $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1))));
						}
						$this->getServer()->getLogger()->info($this->getServer()->getLanguage()->translateString($event->getFormat(), array($event->getPlayer()->getDisplayName(), substr_replace($m, $event->getPlayer()->getName(), $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1))));
						$event->setCancelled();
						return;
					case "r":
					case "random":
						$l = array();
						foreach($this->getServer()->getOnlinePlayers() as $p){
							if($p !== $event->getPlayer()){
								$l[] = $p;
							}
						}
						if(count($l) === 0){
							return;
						}
						$p = $l[mt_rand(0, count($l) - 1)]->getName();
						$event->setMessage(substr_replace($m, $p, $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1));
						break;
					case "n":
						$event->setMessage(substr_replace($m, "\n", $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1));
						break;
					case "w":
					case "world":
						$p = $event->getPlayer()->getLevel()->getName();
						$event->setMessage(substr_replace($m, $p, $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1));
						break;
				}
			}
		}
	}

	public function ServerCommand(ServerCommandEvent $event){
		if($this->run($event->getCommand(), $event->getSender())) $event->setCancelled();
	}

	public function run($line, $issuer){
		if($line != ""){
			$output = "";
			$end = strpos($line, " ");
			if($end === false){
				$end = strlen($line);
			}
			$cmd = strtolower(substr($line, 0, $end));
			$params = (string) substr($line, $end + 1);
			if(preg_match_all('#@([@a-z]{1,})#', $params, $matches, PREG_OFFSET_CAPTURE) > 0){
				$offsetshift = 0;
				foreach($matches[1] as $selector){
					if($selector[0]{0} === "@"){ //Escape!
						$params = substr_replace($params, $selector[0], $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1);
						--$offsetshift;
						continue;
					}
					switch(strtolower($selector[0])){
						case "u":
						case "player":
						case "p":
						case "username":
							$p = $issuer->getName();
							$this->getServer()->dispatchCommand($issuer, $cmd . " ". substr_replace($params, $p, $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1));
							return true;
						case "w":
						case "world":
							$p = ($issuer instanceof Player) ? $issuer->getLevel()->getName() : $this->getServer()->getDefaultLevel()->getName();
							$this->getServer()->dispatchCommand($issuer, $cmd . " ". substr_replace($params, $p, $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1));
							return true;
						case "a":
						case "all":
							if($issuer instanceof Player){
								if($issuer->isOp()){
									$output = "";
									foreach($this->getServer()->getOnlinePlayers() as $p){
										$this->getServer()->dispatchCommand($issuer, $cmd . " ". substr_replace($params, $p->getName(), $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1));
									}
								}else{
									$issuer->sendMessage("§cあなたには権限がありません！");
								}
							}else{
								foreach($this->getServer()->getOnlinePlayers() as $p){
									$this->getServer()->dispatchCommand($issuer, $cmd . " ". substr_replace($params, $p->getName(), $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1));
								}
							}
							return true;
						case "r":
						case "random":
							$l = array();
							foreach($this->getServer()->getOnlinePlayers() as $p){
								if($p !== $issuer){
									$l[] = $p;
								}
							}
							if(count($l) === 0){
								return true;
							}

							$p = $l[mt_rand(0, count($l) - 1)]->getName();
							$this->getServer()->dispatchCommand($issuer, $cmd . " ". substr_replace($params, $p, $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1));
							return true;
					}
				}
			}
			$this->getServer()->dispatchCommand($issuer, $line);
			return true;
		}
	}

}