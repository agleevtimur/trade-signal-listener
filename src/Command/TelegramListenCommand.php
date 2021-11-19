<?php

namespace App\Command;

use App\Event\NewSignalEvent;
use App\Service\SignalHandlerManager;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Database\Memory;
use danog\MadelineProto\Settings\Ipc;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TelegramListenCommand extends Command
{
    protected static $defaultName = 'listen-telegram';

    public function __construct(private LoggerInterface $logger, private ParameterBagInterface $parameterBag)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        SignalHandlerManager::setServices($this->logger, new Client(), $this->parameterBag);

        $settings = new Settings();
        $settings->setDb(new Memory());

        $madeline = new API('session.madeline');
        $madeline->startAndLoop(NewSignalEvent::class);

        return Command::SUCCESS;
    }
}