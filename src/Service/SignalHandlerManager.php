<?php

namespace App\Service;

use App\Service\R2BC\R2BCSignalHandler;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SignalHandlerManager
{
    private static LoggerInterface $logger;
    private static Client $client;
    private static ParameterBagInterface $parameterBag;

    private static array $handlerDictionary = [
        R2BCSignalHandler::CHANNEL_TELEGRAM_ID => R2BCSignalHandler::class,
        //my (timur) telegram id
        1158257674 => R2BCSignalHandler::class
    ];

    public static function handle(int $channelTelegramId, string $message, int $messageId): void
    {
        $handlerClass = static::$handlerDictionary[$channelTelegramId] ?? null;
        if ($handlerClass === null) {
            return;
        }

        self::$logger->info('new signal received');
        (new $handlerClass(self::$logger, self::$client, self::$parameterBag))->resolve($message, $messageId);
    }

    public static function setServices(LoggerInterface $logger, Client $client, ParameterBagInterface $parameterBag)
    {
        self::$logger = $logger;
        self::$client = $client;
        self::$parameterBag = $parameterBag;
    }
}