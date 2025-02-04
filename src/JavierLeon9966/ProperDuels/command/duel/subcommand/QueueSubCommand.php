<?php

declare(strict_types = 1);

namespace JavierLeon9966\ProperDuels\command\duel\subcommand;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class QueueSubCommand extends BaseSubCommand{

	public function onRun(CommandSender $sender, string $commandLabel, array $args): void{
		$arenaManager = $this->plugin->getArenaManager();
		$queueManager = $this->plugin->getQueueManager();
		$rawUUID = $sender->getRawUniqueId();
		if(isset($args['arena'])){
			$arena = $arenaManager->get($args['arena']);
			if($arena === null){
				$sender->sendMessage(TextFormat::RED."No arena was found by the name '$args[arena]'");
				return;
			}

			if($queueManager->has($rawUUID)){
				$sender->sendMessage(TextFormat::RED.'You are already in a queue');
				return;
			}

			$queueManager->add($rawUUID, $arena);
			$sender->sendMessage('Successfully added into the queue');
			return;
		}elseif($queueManager->has($rawUUID)){
			$queueManager->remove($rawUUID);
			$sender->sendMessage('Successfully removed from the queue');
			return;
		}

		$queueManager->add($rawUUID);
		$sender->sendMessage('Successfully added into the queue');
	}

	public function prepare(): void{
		$this->addConstraint(new InGameRequiredConstraint($this));

		$this->setPermission('properduels.command.duel.queue');

		$this->registerArgument(0, new RawStringArgument('arena', true));
	}
}
