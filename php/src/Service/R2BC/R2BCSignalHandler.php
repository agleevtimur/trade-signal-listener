<?php

namespace App\Service\R2BC;

use App\DTO\BaseOrderDTO;
use App\DTO\CloseOrderDTO;
use App\DTO\NewOrderDTO;
use App\Service\SignalHandlerAbstract;

class R2BCSignalHandler extends SignalHandlerAbstract
{
    const SKIP_TICKERS = [];
    public const CHANNEL_TELEGRAM_ID = 1210594398;
    protected static string $channelId = 'R2BC';

    public function resolve(string $text, string $messageLink, int $messageId = 0): void
    {
        $signalParsed = $this->parse($text);
        if ($signalParsed === null || $this->needToSkip($signalParsed) === true) {
            return;
        }

        $signalParsed->lot = R2BCOrderLotResolver::resolve($signalParsed->ticker, $signalParsed->action, $signalParsed->price);
        $signalParsed->messageId = $messageId;
        $signalParsed->extra['messageLink'] = $messageLink;

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
            'CLOSE' => $this->parseCloseOrder($text),
            default => null
        };

        if ($parsedSignal === null) {
            return null;
        }

        if (str_contains($text, R2BCSignalEnum::SELL)) {
            $parsedSignal->action = 'SELL';
        } elseif (str_contains($text, R2BCSignalEnum::BUY)) {
            $parsedSignal->action = 'BUY';
        }

        $parsedSignal->price = (float)explode('\n', explode(' ', explode($parsedSignal->action, $text)[1])[1])[0];
        $parsedSignal->percentage = 100;

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
        $signal = new NewOrderDTO();

        $signal->contractType = 'MARKET';

        return $signal;
    }

    protected function parseCloseOrder(string $text): BaseOrderDTO
    {
        $signal = new CloseOrderDTO();

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

    protected function needToSkip(BaseOrderDTO $signal): bool
    {
        $result = array_filter(self::SKIP_TICKERS, fn($ticker) => str_contains($signal->ticker, $ticker));
        return $result !== [];
    }
}
