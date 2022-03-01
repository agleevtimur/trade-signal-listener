<?php

namespace App\Service\R2BC;

use Predis\Client;

class R2BCOrderLotResolver
{
    private static array $defaultStateValues = [
        'EUR.USD' . R2BCSignalEnum::BUY => 0,
        'EUR.USD' . R2BCSignalEnum::BUY . 'PRICE' => 0,
        'EUR.USD' . R2BCSignalEnum::SELL => 0,
        'EUR.USD' . R2BCSignalEnum::SELL . 'PRICE' => 0,
        'GBP.USD' . R2BCSignalEnum::BUY => 0,
        'GBP.USD' . R2BCSignalEnum::BUY . 'PRICE' => 0,
        'GBP.USD' . R2BCSignalEnum::SELL => 0,
        'GBP.USD' . R2BCSignalEnum::SELL . 'PRICE' => 0,
        'AUD.USD' . R2BCSignalEnum::BUY => 0,
        'AUD.USD' . R2BCSignalEnum::BUY . 'PRICE' => 0,
        'AUD.USD' . R2BCSignalEnum::SELL => 0,
        'AUD.USD' . R2BCSignalEnum::SELL . 'PRICE' => 0,
        'USD.CHF' . R2BCSignalEnum::BUY => 0,
        'USD.CHF' . R2BCSignalEnum::BUY . 'PRICE' => 0,
        'USD.CHF' . R2BCSignalEnum::SELL => 0,
        'USD.CHF' . R2BCSignalEnum::SELL . 'PRICE' => 0,
        'EUR.CHF' . R2BCSignalEnum::BUY => 0,
        'EUR.CHF' . R2BCSignalEnum::BUY . 'PRICE' => 0,
        'EUR.CHF' . R2BCSignalEnum::SELL => 0,
        'EUR.CHF' . R2BCSignalEnum::SELL . 'PRICE' => 0,
        'XAU.USD' . R2BCSignalEnum::BUY => 0,
        'XAU.USD' . R2BCSignalEnum::BUY . 'PRICE' => 0,
        'XAU.USD' . R2BCSignalEnum::SELL => 0,
        'XAU.USD' . R2BCSignalEnum::SELL . 'PRICE' => 0,
        'USD.JPY' . R2BCSignalEnum::BUY => 0,
        'USD.JPY' . R2BCSignalEnum::BUY . 'PRICE' => 0,
        'USD.JPY' . R2BCSignalEnum::SELL => 0,
        'USD.JPY' . R2BCSignalEnum::SELL . 'PRICE' => 0,
        'EUR.JPY' . R2BCSignalEnum::BUY => 0,
        'EUR.JPY' . R2BCSignalEnum::BUY . 'PRICE' => 0,
        'EUR.JPY' . R2BCSignalEnum::SELL => 0,
        'EUR.JPY' . R2BCSignalEnum::SELL . 'PRICE' => 0,
        'EUR.GBP' . R2BCSignalEnum::BUY => 0,
        'EUR.GBP' . R2BCSignalEnum::BUY . 'PRICE' => 0,
        'EUR.GBP' . R2BCSignalEnum::SELL => 0,
        'EUR.GBP' . R2BCSignalEnum::SELL . 'PRICE' => 0,
        'USD.CAD' . R2BCSignalEnum::BUY => 0,
        'USD.CAD' . R2BCSignalEnum::BUY . 'PRICE' => 0,
        'USD.CAD' . R2BCSignalEnum::SELL => 0,
        'USD.CAD' . R2BCSignalEnum::SELL . 'PRICE' => 0,
    ];

    private static array $lotList = [0.01, 0.02, 0.03, 0.05, 0.07, 0.1, 0.13, 0.17, 0.23, 0.34, 0.4, 0.55];
    private static array $factorDictionary = [1, 1, 1, 2, 2, 3, 5, 8, 13, 21, 34, 55];

    public static function resolve(string $ticker, string $action, string $price): float
    {
        if ($ticker === 'XAU.USD') {
            return R2BCSignalEnum::LOT_BASE;
        }

        $signalReceivedTime = getdate();
        if ($signalReceivedTime['minutes'] > 3 && $signalReceivedTime['minutes'] < 59) {
            return self::getLotSecondStrategy($ticker, $action, $price);
        }

        $step = self::updateStateAndGetStep($ticker, $action, $price);

        return R2BCSignalEnum::LOT_BASE * (self::$factorDictionary[$step] ?? 55);
    }

    public static function fillStateWithDefaultValues(): void
    {
        $redis = self::connectDb();

        foreach (self::$defaultStateValues as $defaultStateKey => $value) {
            $redis->set($defaultStateKey, $value);
        }

        foreach (self::$defaultStateValues as $defaultStateKey => $value) {
            $redis->set($defaultStateKey . '-SECOND', $value);
        }
    }

    private static function getLotSecondStrategy(string $ticker, string $action, string $currentPrice): float
    {
        $step = self::updateStateAndGetStep($ticker, $action, $currentPrice, true);

        return self::$lotList[$step] ??  0.55;
    }

    private static function updateStateAndGetStep(string $ticker, string $action, string $currentPrice, bool $secondStrategy = false): int
    {
        $redis = self::connectDb();
        $key = $ticker . $action;
        if ($secondStrategy) {
            $key .= '-SECOND';
        }
        $prevPrice = $redis->get($key . 'PRICE');

        if ($ticker === 'EUR.USD') {
            $currentStep = $redis->get($key);
            $delta = $secondStrategy === true ? 0.0006 : 0.0003;

            if (abs((float)$currentPrice - (float)$prevPrice) < $delta) {
                $redis->set($key . 'PRICE', $currentPrice);
                return $currentStep;
            }
        }

        if ($action === R2BCSignalEnum::BUY) {
            if ($currentPrice < $prevPrice) {
                $redis->incr($key);
            } else {
                $redis->set($key, 0);
            }
        } else {
            if ($currentPrice > $prevPrice) {
                $redis->incr($key);
            } else {
                $redis->set($key, 0);
            }
        }

        $redis->set($key . 'PRICE', $currentPrice);

        return $redis->get($key);
    }

    private static function connectDb(): Client
    {
        return new Client([
            'host' => 'redis',
            'port' => 6379,
            'persistent' => '1'
        ]);
    }
}
