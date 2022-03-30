<?php

namespace App\Service;

use App\Service\R2BC\R2BCOrderLotResolver;
use App\Service\R2BC\R2BCSignalHandler;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SignalHandlerManager
{
    private static array $handlerDictionary = [
        'r2bc' => R2BCSignalHandler::class,
    ];

    public function __construct(
        private LoggerInterface       $logger,
        private R2BCSignalHandler $r2BCSignalHandler
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
        $this->r2BCSignalHandler->resolve($message, $messageLink, $messageId);
    }
}
