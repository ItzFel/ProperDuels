<?php

declare(strict_types = 1);

namespace JavierLeon9966\ProperDuels\session;

use JavierLeon9966\ProperDuels\arena\Arena;
use JavierLeon9966\ProperDuels\match\Match;
use JavierLeon9966\ProperDuels\ProperDuels;

use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

final class Session{
	private $invites = [];

	private $tasks = [];

	private $match = null;

	private $player;

	private $info;

	public function __construct(Player $player){
		$this->player = $player;
		$this->saveInfo();
	}

	public function addInvite(Session $session, ?Arena $arena): void{
		$properDuels = ProperDuels::getInstance();
		$arenaManager = $properDuels->getArenaManager();
		if($this->match !== null or $session->getMatch() !== null or $arena === null and count($arenaManager->all()) === 0){
			return;
		}

		$matchManager = $properDuels->getMatchManager();
		$arena = $arena ?? $arenaManager->get(array_rand(count($matchManager->all()) === 0 ? $arenaManager->all() : array_udiff(
			$arenaManager->all(),
			$matchManager->all(),
			static function(Arena $a, Match $b): int{
				return strcasecmp($a->getName(), $b->getArena()->getName());
			}
		)));

		$config = $properDuels->getConfig();

		$player = $session->getPlayer();

		if($matchManager->has($arena->getName())){
			$player->sendMessage($config->getNested('match.InUse'));
			return;
		}

		$time = $config->getNested('request.expire.time');
		$player->sendMessage(str_replace(
			['{player}', '{arena}', '{seconds}'],
			[$this->player->getDisplayName(), $arena->getName(), $time],
			$config->getNested('request.invite.success')
		));
		$this->player->sendMessage(str_replace(
			['{player}', '{arena}', '{seconds}'],
			[$player->getDisplayName(), $arena->getName(), $time],
			$config->getNested('request.invite.message')
		));

		$this->invites[$playerUUID = $player->getRawUniqueId()] = $arena;

		if($time > 0){
			$this->tasks[$playerUUID] = $properDuels->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($config, $player, $playerUUID): void{
				unset($this->invites[$playerUUID], $this->tasks[$playerUUID]);

				$player->sendMessage(str_replace(
					'{player}',
					$this->player->getDisplayName(),
					$config->getNested('request.expire.to')
				));
				$this->player->sendMessage(str_replace(
					'{player}',
					$player->getDisplayName(),
					$config->getNested('request.expire.from')
				));
			}), (int)(20 * $time));
		}
	}

	public function getInfo(): SessionInfo{
		return $this->info;
	}

	public function getInvite(string $rawUUID): ?Arena{
		return $this->invites[$rawUUID] ?? null;
	}

	public function getMatch(): ?Match{
		return $this->match;
	}

	public function getPlayer(): Player{
		return $this->player;
	}

	public function hasInvite(string $rawUUID): bool{
		return isset($this->invites[$rawUUID]);
	}

	public function removeInvite(string $rawUUID): void{
		unset($this->invites[$rawUUID]);

		if(isset($this->tasks[$rawUUID])){
			$this->tasks[$rawUUID]->cancel();
			unset($this->tasks[$rawUUID]);
		}
	}

	public function saveInfo(): void{
		$this->info = new SessionInfo(
			$this->player->getArmorInventory()->getContents(),
			$this->player->getInventory()->getContents(),
			$this->player->getCurrentTotalXp()
		);
	}

	public function setMatch(?Match $match): void{
		$this->match = $match;
	}
}
