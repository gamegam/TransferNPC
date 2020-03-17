<?php


namespace TransferNPC;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\Player;

use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ByteArrayTag;

use TransferNPC\command\NPCCommand;
use TransferNPC\listener\EventListener;
use TransferNPC\entity\CustomNPC;
use TransferNPC\query\ServerQuery;

class TransferNPC extends PluginBase
{
	
	private static $instance = null;
	
	public static $prefix = "§b• ";
	
	public $config, $db;
	
	public $npc = [];
	
	
	public static function runFunction (): TransferNPC
	{
		return self::$instance;
	}
	
	public function onLoad (): void
	{
		if (self::$instance === null) {
			self::$instance = $this;
		}
		if (!file_exists ($this->getDataFolder ())) {
			@mkdir ($this->getDataFolder ());
		}
		$this->config = new Config ($this->getDataFolder () . "config.yml", Config::YAML, [
			"data" => []
		]);
		$this->db = $this->config->getAll ();
	}
	
	public function onEnable (): void
	{
		Entity::registerEntity (CustomNPC::class, true);
		$this->getServer ()->getPluginManager ()->registerEvents (new EventListener ($this), $this);
		$this->getServer ()->getCommandMap ()->register ("avas", new NPCCommand ($this));
  $this->getScheduler ()->scheduleDelayedTask (new class ($this) extends \pocketmine\scheduler\Task{
   protected $plugin;

   public function __construct (TransferNPC $plugin)
   {
      $this->plugin = $plugin;
   }

   public function onRun (int $currentTick)
   {
     foreach ($this->plugin->db ["data"] as $name => $arr) {
			     $this->plugin->spawnNPC ($name, $arr);
        $this->plugin->getLogger ()->notice ("{$name}  엔피시 소환 완료 !");
	   	}
   }
  }, 25 * 5);
	}
	
	public function onDisable (): void
	{
		foreach ($this->getServer ()->getLevels () as $level) {
			foreach ($level->getEntities () as $entity) {
				if ($entity instanceof CustomNPC) {
					$entity->close();
				}
			}
		}
		if ($this->config instanceof Config) {
			$this->config->setAll ($this->db);
			$this->config->save ();
		}
	}
	
	public function spawnNPC (string $name, array $arr): void
	{
		$query = new ServerQuery ($arr ["ip"], $arr ["port"]);
		[ $x, $y, $z, $level ] = explode (":", $arr ["pos"]);
		
		$this->getServer ()->loadLevel ($level);
		$pos = new Position (floatval ($x) + 0.5, floatval ($y), floatval ($z) + 0.5, $this->getServer ()->getLevelByName ($level));
		$nbt = Entity::createBaseNBT ($pos, null, $arr ["yaw"], $arr ["pitch"]);
		$nbt->setTag (new CompoundTag ("Skin", [
			new StringTag ("Name", base64_decode ($arr ["skin"] ["skinId"])),
			new ByteArrayTag ("Data", base64_decode ($arr ["skin"] ["skinData"]))
		]));
		
		$entity = Entity::createEntity ("CustomNPC", $pos->level, $nbt);
		
		if ($entity instanceof Entity and $entity instanceof CustomNPC) {
			$entity->spawnToAll ();
		$nameTag = "§f( §a{$name}§f 서버로 이동 ! §f)";
		if ($query->check ()) {
			$nameTag .= "\n§f서버 동접 : §a{$query->getNumPlayer ()}명§f  (" . $this->getNumFormat ($query->getNumPlayer (), $query->getMaxPlayer ()) . "§r§f)";
		} else {
			$nameTag .= "\n§c>> 현재 점검중인 서버 입니다. <<";
		}
		$entity->setNameTag ($nameTag);
		
		$entity->setScale (2);
		$this->getScheduler ()->scheduleRepeatingTask (new class ($entity, $query, $name) extends \pocketmine\scheduler\Task{
			protected $entity;
			protected $query;
			protected $name;
			
			public function __construct (Entity $entity, ServerQuery $query, string $name)
			{
				$this->entity = $entity;
				$this->query = $query;
				$this->name = $name;
			}
			
			public function onRun (int $currentTick)
			{
				if ($this->entity->isAlive ()) {
					$plugin = TransferNPC::runFunction ();
					
					$nameTag = "§f( §a{$this->name}§f 서버로 이동 ! §f)";
					if ($this->query->check ()) {
						$nameTag .= "\n§f서버 동접 : §a{$this->query->getNumPlayer ()}명§f  (" . $plugin->getNumFormat ($this->query->getNumPlayer (), $this->query->getMaxPlayer ()) . "§r§f)";
					} else {
						$nameTag .= "\n§c>> 현재 점검중인 서버 입니다. <<";
					}
					$this->entity->setNameTag ($nameTag);
				} else {
					$this->getHandler ()->cancel ();
				}
			}
		}, 1200*5);
		
		$this->npc [$entity->getId ()] = $name;
		}
	}
	
	public function removeNPC (int $id): bool
	{
		if (isset ($this->npc [$id])) {
			unset ($this->npc [$id]);
			return true;
		}
		return false;
	}
	
	public function addNPC (Player $player, string $name, $ip, int $port = 19132): void
	{
		$this->db ["data"] [$name] = [
			"pos" => (int) $player->x . ":" . (int) $player->y . ":" . (int) $player->z . ":" . $player->level->getFolderName (),
   "yaw" => $player->yaw,
			"pitch" => $player->pitch,
			"ip" => $ip,
			"port" => $port,
			"skin" => [
				"skinId" => base64_encode ($player->getSkin ()->getSkinId ()),
				"skinData" => base64_encode ($player->getSkin ()->getSkinData ())
			]
		];
	}
	
	public function deleteNPC (string $name): void
	{
		foreach ($this->getServer ()->getLevels () as $level) {
			foreach ($level->getEntities () as $entity) {
				if ($entity instanceof CustomNPC) {
					if (isset ($this->npc [$entity->getId ()])) {
						if ($this->npc [$entity->getId ()] === $name) {
							$entity->close ();
						}
					}
				}
			}
		}
		unset ($this->db ["data"] [$name]);
	}
	
	public static function message ($player, string $msg): void
	{
		$player->sendMessage (self::$prefix . $msg);
	}
	
	public function getNumFormat (int $min, int $max): string
	{
		if ($min === $max) {
			return "§c꽉참";
		}
		if ($min === 0) {
			return "§a쾌적함";
		}
		if ($min >= 1 and $min <= 20) {
			return "§a원활함";
		} else if ($min >= 21 and $min <= 75) {
			return "§d렉이 조금 걸림";
		} else if ($min >= 76 and $min <= 120) {
			return "§b핑이 끈킴";
		} else {
			return "§e렉이 심함";
		}
	}
}