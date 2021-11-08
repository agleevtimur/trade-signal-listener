<?php

namespace App\Service\R2BC;

use App\DTO\BaseOrderDTO;
use App\DTO\CloseOrderDTO;
use App\DTO\ModificationDTO;
use App\DTO\NewOrderDTO;
use App\Service\SignalHandlerAbstract;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class R2BCSignalHandler extends SignalHandlerAbstract
{
    public const CHANNEL_TELEGRAM_ID = 1210594398;
    protected static string $channelId = 'R2BC';

    public function resolve(string $text, int $messageId = 0): void
    {
        $signalParsed = $this->parse($text);
        if ($signalParsed === null) {
            return;
        }
        $signalParsed->messageId = $messageId;

        $this->send($signalParsed);
    }

    protected function parse(string $text): ?BaseOrderDTO
    {
        $type = $this->parseType($text);
        if ($type === null) {
            return null;
        }

        $parsedSignal = match ($type) {
            'OPEN' => $this->parseNewOrder($text),
            'MODIFICATION' => $this->parseModification($text),
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

        $parsedSignal->orderId = explode(' ', explode('#id', $text)[1])[0];
        $parsedSignal->type = $type;

        return $parsedSignal;
    }

    private function parseType(string $text): ?string
    {
        if (str_contains($text, R2BCSignalEnum::MODIFICATION)) {
            return 'MODIFICATION';
        } elseif (strpos($text, R2BCSignalEnum::NEW_ORDER)) {
           return 'OPEN';
        } elseif (str_contains($text, R2BCSignalEnum::CLOSE_ORDER)) {
            return 'CLOSE';
        }

        return null;
    }

    private function parseNewOrder(string $text): NewOrderDTO
    {
        $signal = new NewOrderDTO();

        $signal->contractType = 'LIMIT';
        $type = str_contains($text, R2BCSignalEnum::BUY) === true ? 'BUY' : 'SELL';
        $signal->price = (float)explode('\n', explode(' ', explode($type, $text)[1])[1])[0];

        $preTicker = explode(' ', explode('#', $text)[2])[0];
        $signal->ticker = substr_replace($preTicker, '.', 3, 0);

        $splitText = explode(' ', $text);
        $signal->takeProfit = (float)array_pop($splitText);

        $signal->percentage = 1;
        $signal->channelId = self::$channelId;

        return $signal;
    }

    private function parseModification(string $text): ModificationDTO
    {
        $signal = new ModificationDTO();

        $splitText = explode(' ', $text);
        $signal->takeProfit = (float)array_pop($splitText);
        array_pop($splitText);
        $signal->previousTakeProfit = (float)array_pop($splitText);

        return $signal;
    }

    private function parseCloseOrder(string $text): CloseOrderDTO
    {
        $signal = new CloseOrderDTO();

        $splitText = explode(' ', explode('TP:', $text)[1]);
        $signal->takeProfit = (float)$splitText[1];

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