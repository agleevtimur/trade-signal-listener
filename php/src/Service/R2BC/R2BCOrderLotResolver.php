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

    public static function resolve(string $ticker, string $action, string $price): float
    {
        if ($ticker === 'XAU.USD') {
            return R2BCSignalEnum::LOT_START;
        }

        $factor = self::updateStateAndGetFactor($ticker, $action, $price);
        $result = R2BCSignalEnum::LOT_START + (R2BCSignalEnum::LOT_STEP * $factor);

        return min($result, R2BCSignalEnum::LOT_MAX);
    }

    public static function fillStateWithDefaultValues(): void
    {
        $redis = self::connectDb();

        foreach (self::$defaultStateValues as $defaultStateKey => $value) {
            $redis->set($defaultStateKey, $value);
        }
    }

    private static function updateStateAndGetFactor(string $ticker, string $action, string $currentPrice): int
    {
        $redis = self::connectDb();
        $key = $ticker . $action;
        $prevPrice = $redis->get($key . 'PRICE');

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