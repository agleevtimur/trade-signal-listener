<?php

namespace App\Service\R2BC;

use App\Service\RedisClient;
use Predis\Client;

class R2BCOrderLotResolver
{
    const MIN_RISK = false;

    private static array $tickers = ['EUR.USD', 'GBP.USD', 'AUD.USD', 'USD.CHF', 'EUR.CHF', 'XAU.USD', 'USD.JPY', 'EUR.JPY', 'EUR.GBP', 'USD.CAD'];
    private static array $factorDictionary = [1, 1, 1, 1, 5, 8, 11, 16, 21, 25, 28, 35, 40, 45, 56];
    private static array $factorDictionaryMinRisk = [1, 1, 0, 2, 0, 3, 0, 5, 0, 7, 0, 10, 0, 13, 0, 15, 0, 17, 0, 19, 0, 21, 0, 24, 0, 27, 0, 34, 0, 40, 0, 55];

    private Client $redisClient;

    public function __construct(RedisClient $redis)
    {
        $this->redisClient = $redis->getClient();
    }

    public function resolve(string $ticker, string $action, string $price, bool $hasTP): array
    {
        $key = $this->resolveOrderChainKey($price, $ticker, $action, $hasTP);
        $result = ['key' => $key, 'lot' => 0.0];
        $step = $this->updateStateAndGetStep($key, $ticker, $action, $price);

        if ($ticker !== 'EUR.USD' && $step < 2) {
            return $result;
        }

        $result['lot'] = R2BCSignalEnum::LOT_BASE * ((self::MIN_RISK ? self::$factorDictionaryMinRisk[$step] : self::$factorDictionary[$step]) ?? 55);

        return $result;
    }

    public function cleanOrdersState(): void
    {
        $this->redisClient->flushall();
    }

    private function updateStateAndGetStep(string $key, string $ticker, string $action, string $currentPrice): int
    {
        $prevPrice = $this->redisClient->get($key . '-PRICE');

        if ($ticker === 'EUR.USD') {
            $currentStep = $this->redisClient->get($key);
            if ($currentPrice > 4) {
                if (abs((float)$currentPrice - (float)$prevPrice) < 0.0006) {
                    $this->redisClient->set($key . '-PRICE', $currentPrice);
                    return $currentStep;
                }
            }
        }

        $this->updateState($action, $key, $currentPrice, $prevPrice);

        return $this->redisClient->get($key);
    }

    private function updateState(string $action, string $key, string $currentPrice, ?string $prevPrice): void
    {
        $countKey = $key . '-COUNT';
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

    private function resolveOrderChainKey(string $currentPrice, string $ticker, string $action, bool $hasTP): string
    {
        $tp = $hasTP === true ? 'tp' : 'no-tp';
        $keys = $this->redisClient->keys($ticker . '-' . $action . $tp . '*');

        $resultKey = '';
        if ($action === R2BCSignalEnum::SELL) {
            $maxPrice = 0;
            foreach ($keys as $key) {
                $lastPrice = $this->redisClient->get($key . '-PRICE');
                if ($currentPrice > $lastPrice && $lastPrice > $maxPrice) {
                    $maxPrice = $lastPrice;
                    $resultKey = $key;
                }
            }
        } else {
            $minPrice = 10000;
            foreach ($keys as $key) {
                $lastPrice = $this->redisClient->get($key . '-PRICE');
                if ($currentPrice < $lastPrice && $lastPrice < $minPrice) {
                    $minPrice = $lastPrice;
                    $resultKey = $key;
                }
            }
        }

        if ($resultKey === '') {
            $resultKey = uniqid($ticker . '-' . $action . $tp);
        }

        return $resultKey;
    }
}
