<?php

namespace App\Service\R2BC;

use App\DTO\BaseOrderDTO;
use App\DTO\CloseOrderDTOAB;
use App\DTO\NewOrderDTO;
use App\DTO\NewOrderDTOAB;

class R2BCSignalHandlerAB extends R2BCSignalHandler
{
    protected function parse(string $text): ?BaseOrderDTO
    {
        $type = $this->parseType($text);
        if ($type === null) {
            return null;
        }

        $parsedSignal = match ($type) {
            'OPEN' => $this->parseNewOrder($text),
            'CLOSE' => $this->parseCloseOrder($text),
            default => null
        };

        if ($parsedSignal === null) {
            return null;
        }

        if (str_contains($text, R2BCSignalEnum::SELL)) {
            $parsedSignal->action = 'SELL';
        } elseif (str_contains($text, R2BCSignalEnum::BUY)){
            $parsedSignal->action = 'BUY';
        }

        $parsedSignal->price = (float)explode('\n', explode(' ', explode($parsedSignal->action, $text)[1])[1])[0];
        $parsedSignal->percentage = 1;

        $parsedSignal->orderId = explode(' ', explode('#id', $text)[1])[0];
        $parsedSignal->type = $type;

        $preTicker = explode(' ', explode('#', $text)[2])[0];
        $parsedSignal->ticker = substr_replace($preTicker, '.', 3, 0);

        $parsedSignal->channelId = self::$channelId;

        return $parsedSignal;
    }

    protected function parseType(string $text): ?string
    {
        if (strpos($text, R2BCSignalEnum::NEW_ORDER)) {
            return 'OPEN';
        } elseif (str_contains($text, R2BCSignalEnum::CLOSE_ORDER)) {
            return 'CLOSE';
        }

        return null;
    }

    protected function parseNewOrder(string $text): BaseOrderDTO
    {
        $signal = new NewOrderDTOAB();

        $signal->contractType = 'LIMIT';

        return $signal;
    }

    protected function parseCloseOrder(string $text): BaseOrderDTO
    {
        $signal = new CloseOrderDTOAB();

        $priceClose = (float)explode(' ', explode(R2BCSignalEnum::CLOSE_PRICE, $text)[1])[1];
        $income = (float)explode(' ', explode(R2BCSignalEnum::PROFIT, $text)[1])[1];

        $signal->extra = [
            'expected' =>
                [
                    'income' => $income,
                    'price' => $priceClose
                ]
        ];

        return $signal;
    }
}