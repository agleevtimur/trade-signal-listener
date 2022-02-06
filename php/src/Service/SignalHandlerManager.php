<?php

namespace App\Service;

use App\Service\R2BC\R2BCSignalHandler;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SignalHandlerManager
{
    private static array $handlerDictionary = [
        'r2bc' => R2BCSignalHandler::class,
    ];

    public function __construct(
        private LoggerInterface       $logger,
        private Client                $client,
        private ParameterBagInterface $parameterBag,
    )
    {
    }

    public function handle(string $channel, string $message, string $messageLink, int $messageId = 0): void
    {
        $handlerClass = static::$handlerDictionary[$channel] ?? null;
        if ($handlerClass === null) {
            return;
        }

        $this->logger->info('new signal received');
        (new $handlerClass($this->logger, $this->client, $this->parameterBag->get('signal_receiver_url')))->resolve($message, $messageLink, $messageId);
    }
}
