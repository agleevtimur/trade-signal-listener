<?php

namespace App\Service\R2BC;

use App\Service\RedisClient;
use Predis\Client;

class R2BCOrderLotResolver
{
    const MIN_RISK = false;

    private static array $defaultStateValues = [
        'EUR.USD' . R2BCSignalEnum::BUY => 0,
        'EUR.USD' . R2BCSignalEnum::SELL => 0,
        'GBP.USD' . R2BCSignalEnum::BUY => 0,
        'GBP.USD' . R2BCSignalEnum::SELL => 0,
        'AUD.USD' . R2BCSignalEnum::BUY => 0,
        'AUD.USD' . R2BCSignalEnum::SELL => 0,
        'USD.CHF' . R2BCSignalEnum::BUY => 0,
        'USD.CHF' . R2BCSignalEnum::SELL => 0,
        'EUR.CHF' . R2BCSignalEnum::BUY => 0,
        'EUR.CHF' . R2BCSignalEnum::SELL => 0,
        'XAU.USD' . R2BCSignalEnum::BUY => 0,
        'XAU.USD' . R2BCSignalEnum::SELL => 0,
        'USD.JPY' . R2BCSignalEnum::BUY => 0,
        'USD.JPY' . R2BCSignalEnum::SELL => 0,
        'EUR.JPY' . R2BCSignalEnum::BUY => 0,
        'EUR.JPY' . R2BCSignalEnum::SELL => 0,
        'EUR.GBP' . R2BCSignalEnum::BUY => 0,
        'EUR.GBP' . R2BCSignalEnum::SELL => 0,
        'USD.CAD' . R2BCSignalEnum::BUY => 0,
        'USD.CAD' . R2BCSignalEnum::SELL => 0,
    ];

    private static array $factorDictionary = [1, 1, 1, 1, 5, 8, 11, 16, 21, 25, 28, 35, 40, 45, 56];
    private static array $factorDictionaryMinRisk = [1, 1, 0, 2, 0, 3, 0, 5, 0, 7, 0, 10, 0, 13, 0, 15, 0, 17, 0, 19, 0, 21, 0, 24, 0, 27, 0, 34, 0, 40, 0, 55];

    private Client $redisClient;

    public function __construct(RedisClient $redis)
    {
        $this->redisClient = $redis->getClient();
    }

    public function resolve(string $ticker, string $action, string $price): float
    {
        $step = $this->updateStateAndGetStep($ticker, $action, $price);

        if ($ticker !== 'EUR.USD' && $step < 2) {
            return 0.0;
        }

        return R2BCSignalEnum::LOT_BASE * ((self::MIN_RISK ? self::$factorDictionaryMinRisk[$step] : self::$factorDictionary[$step]) ?? 55);
    }

    public function fillStateWithDefaultValues(): void
    {
        foreach (self::$defaultStateValues as $defaultStateKey => $value) {
            $this->redisClient->set($defaultStateKey . '-FIRST', $value);
            $this->redisClient->set($defaultStateKey . '-FIRST-PRICE', $value);
            $this->redisClient->set($defaultStateKey . '-COUNT', $value);
            $this->redisClient->set($defaultStateKey . '-SECOND', $value);
            $this->redisClient->set($defaultStateKey . '-SECOND-PRICE', $value);
        }
    }

    private function updateStateAndGetStep(string $ticker, string $action, string $currentPrice): int
    {
        $countKey = $ticker . $action . '-COUNT';
        $signalReceivedTime = getdate();
        $prefix = $signalReceivedTime['minutes'] > 1 && $signalReceivedTime['minutes'] < 59 ? '-SECOND' : '-FIRST';
        $key = $ticker . $action . $prefix;
        $prevPrice = $this->redisClient->get($key . '-PRICE');

        if ($ticker === 'EUR.USD') {
            $currentStep = $this->redisClient->get($key);
            $delta = $currentStep < 5 ? 0.0003 : 0.0007;
            if (abs((float)$currentPrice - (float)$prevPrice) < $delta) {
                $this->redisClient->set($key . '-PRICE', $currentPrice);
                return $currentStep;
            }
        }

        $this->updateState($action, $countKey, $key, $currentPrice, $prevPrice);

        return $this->redisClient->get($key);
    }

    private function updateState(string $action, string $countKey, string $key, string $currentPrice, string $prevPrice): void
    {
        $priceLess = $currentPrice < $prevPrice;
        $flag = $action === R2BCSignalEnum::BUY;

        if ($this->redisClient->get($countKey) > 0 && $priceLess === $flag) {
            $this->redisClient->incr($key);
        } else {
            $this->redisClient->set($key, 0);
        }

        $this->redisClient->incr($countKey);
        $this->redisClient->set($key . '-PRICE', $currentPrice);
    }
}
