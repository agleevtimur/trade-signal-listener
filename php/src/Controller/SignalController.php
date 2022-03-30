<?php

namespace App\Controller;

use App\Service\R2BC\R2BCOrderLotResolver;
use App\Service\SignalHandlerManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SignalController extends AbstractController
{
    public function receive(Request $request, SignalHandlerManager $signalHandlerManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $signalHandlerManager->handle($data['peer'], $data['message'], $data['messageLink']);

        return $this->json(['status' => 'success']);
    }

    public function reloadState(R2BCOrderLotResolver $lotResolver): JsonResponse
    {
        $lotResolver->fillStateWithDefaultValues();

        return $this->json(['status' => 'success']);
    }
}