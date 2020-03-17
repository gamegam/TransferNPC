<?php


namespace TransferNPC\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use TransferNPC\TransferNPC;

class NPCCommand extends Command
{
	
	protected $plugin = null;
	
	
	public function __construct (TransferNPC $plugin)
	{
		$this->plugin = $plugin;
		parent::__construct ("이동기", "이동기 명령어 입니다. || 아바스", "/이동기", [
			"transfernpc",
			"npc"
		]);
	}
	
	public function execute (CommandSender $player, string $label, array $args): bool
	{
		if (!$player->isOp ()) {
			TransferNPC::message ($player, "당신은 이 명령어를 사용할 권한이 없습니다.");
			return true;
		}
		switch ($args [0] ?? "x") {
			case "add":
				// /npc add (name) (ip) (port)
				if (!isset ($args [1]) or !isset ($args [2]) or !isset ($args [3]) or !is_numeric ($args [3])) {
					TransferNPC::message ($player, "/npc add (name) (ip) (port)");
					return true;
				}
				if (isset ($this->plugin->db ["data"] [$args [1]])) {
					TransferNPC::message ($player, "이미 존재하는 서버 입니다.");
					return true;
				}
				$this->plugin->addNPC ($player, $args [1], $args [2], $args [3]);
				TransferNPC::message ($player, "[ {$args [1]} ] 서버 데이터를 추가했습니다.");
				break;
			case "delete":
				if (!isset ($args [1])) {
					TransferNPC::message ($player, "/npc delete (name)");
					return true;
				}
				if (!isset ($this->plugin->db ["data"] [$args [1]])) {
					TransferNPC::message ($player, "해당 서버는 존재하지 않습니다.");
					return true;
				}
				$this->plugin->deleteNPC ($args [1]);
				TransferNPC::message ($player, "[ {$args [1]} ] 서버 데이터를 제거했습니다.");
				break;
			case "spawn":
				if (!isset ($args [1])) {
					TransferNPC::message ($player, "/npc spawn (name)");
					return true;
				}
				if (!isset ($this->plugin->db ["data"] [$args [1]])) {
					TransferNPC::message ($player, "해당 서버는 존재하지 않습니다.");
					return true;
				}
				$this->plugin->spawnNPC ($args [1], $this->plugin->db ["data"] [$args [1]]);
				break;
			default:
				foreach ([
					[ "/npc add (name) (ip) (port)", "서버 데이터를 추가합니다." ],
					[ "/npc delete (name)", "서버 데이터를 제거합니다." ],
					[ "/npc spawn (name)", "엔피시를 소환합니다." ]
				] as $param) {
					TransferNPC::message ($player, $param [0] . " - " . $param [1]);
				}
				break;
		}
		return true;
	}
	
}