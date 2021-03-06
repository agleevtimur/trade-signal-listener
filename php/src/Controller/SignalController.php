<?php

namespace App\Controller;

use App\Service\SignalHandlerManager;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SignalController extends AbstractController
{
    public function receive(Request $request, LoggerInterface $logger, ParameterBagInterface $parameterBag): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $handler = new SignalHandlerManager($logger, new Client(), $parameterBag);

        $handler->handle($data['peer'], $data['message'], $data['messageLink']);

        return $this->json(['status' => 'success']);
    }
}