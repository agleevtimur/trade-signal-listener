<?php

namespace App\Controller;

use App\Event\NewSignalEvent;
use App\Service\SignalHandlerManager;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Database\Memory;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class TelegramController extends AbstractController
{
    public function init(LoggerInterface $logger, ParameterBagInterface $parameterBag): JsonResponse
    {
        SignalHandlerManager::setServices($logger, new Client(), $parameterBag);

        $settings = new Settings();
        $settings->setDb(new Memory());

        $madeline = new API('session.madeline');
        $madeline->startAndLoop(NewSignalEvent::class);

        return $this->json('end');
    }
}