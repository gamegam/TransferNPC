<?php

namespace TransferNPC\listener;

use pocketmine\event\Listener;

use pocketmine\event\entity\{
	EntityDamageEvent,
	EntityDamageByEntityEvent
};

use pocketmine\Player;

use pocketmine\scheduler\Task;

use TransferNPC\query\ServerQuery;
use TransferNPC\entity\CustomNPC;
use TransferNPC\TransferNPC;

class EventListener implements Listener
{
	
	protected $plugin;
	
	
	public function __construct (TransferNPC $plugin)
	{
		$this->plugin = $plugin;
	}
	
	public function onAttack (EntityDamageEvent $event): void
	{
		if (($entity = $event->getEntity ()) instanceof CustomNPC) {
			$event->setCancelled ();
			if ($event instanceof EntityDamageByEntityEvent) {
				if (($player = $event->getDamager ()) instanceof Player) {
					if (isset ($this->plugin->npc [$entity->getId ()])) {
					   $name = $this->plugin->npc [$entity->getId ()];
						if (isset ($this->plugin->db ["data"] [$name])) {
						   $data = $this->plugin->db ["data"] [$name];
							$query = new ServerQuery ($data ["ip"], $data ["port"]);
							if ($query->check ()) {
								$player->addTitle ("§f( §a{$name} 서버로 이동 ! §f)", "§b*§f 즐겨운 여행이 되길 빕니다. §b*");
								$this->task ($player, $name, $data);
							} else {
								$entity->kill ();
							}
						} else {
							$entity->kill ();
						}
					} else {
						$entity->kill ();
					}
				}
			}
		}
	}
	
	public function task (Player $player, string $name, array $data): void
	{
		$this->plugin->getScheduler ()->scheduleDelayedTask (new class ($player, $name, $data) extends Task{
			protected $player;
			protected $name;
			protected $data;
			
			public function __construct (Player $player, string $name, array $data)
			{
				$this->player = $player;
				$this->name = $name;
				$this->data = $data;
			}
			
			public function onRun (int $currentTick)
			{
				if ($this->player->isOnline ()) {
					$this->player->transfer ($this->data ["ip"], $this->data ["port"], "");
				}
			}
		}, 25 * 4);
	}
}