<?php

namespace App\Controller;

use App\Event\NewSignalEvent;
use App\Service\SignalHandlerManager;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
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
        $appInfo = new AppInfo();
        $appInfo->setApiId(8003470)->setApiHash('84f8375bbe0abbc53c6e4f9ac4064550');

        $settings->setAppInfo($appInfo);
        $settings->setDb(new Memory());

        $madeline = new API('session.madeline');
        $madeline->startAndLoop(NewSignalEvent::class);

        return $this->json('end');
    }
}