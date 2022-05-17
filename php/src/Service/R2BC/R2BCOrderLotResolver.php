<?php

namespace App\Service\R2BC;

use App\Service\RedisClient;
use Predis\Client;

class R2BCOrderLotResolver
{
    const MIN_RISK = false;

    private static array $tickers = ['EUR.USD', 'GBP.USD', 'AUD.USD', 'USD.CHF', 'EUR.CHF', 'XAU.USD', 'USD.JPY', 'EUR.JPY', 'EUR.GBP', 'USD.CAD'];
    private static array $factorDictionary = [1, 1, 2, 3, 5, 8, 11, 16, 21, 25, 28, 35, 40, 45, 56];
    private static array $factorDictionaryMinRisk = [1, 1, 0, 2, 0, 3, 0, 5, 0, 7, 0, 10, 0, 13, 0, 15, 0, 17, 0, 19, 0, 21, 0, 24, 0, 27, 0, 34, 0, 40, 0, 55];

    private Client $redisClient;

    public function __construct(RedisClient $redis)
    {
        $this->redisClient = $redis->getClient();
    }

    public function resolve(string $orderId, string $ticker, string $action, string $price, bool $hasTP): float
    {
        $lotStep = $this->resolveLotStep($orderId, $price, $ticker, $action, $hasTP);

        if ($ticker !== 'EUR.USD' && $lotStep < 2) {
            return 0.0;
        }

        return R2BCSignalEnum::LOT_BASE * ((self::MIN_RISK ? self::$factorDictionaryMinRisk[$lotStep] : self::$factorDictionary[$lotStep]) ?? 56);
    }

    public function cleanOrdersState(): void
    {
        $this->redisClient->flushall();
    }

    private function resolveLotStep(string $orderId, string $currentPrice, string $ticker, string $action, bool $hasTP): int
    {
        $tp = $hasTP === true ? 'tp' : 'notp';
        $priceKeys = $this->redisClient->keys("*-$tp-$ticker-$action-price");

        $resultOrderId = '';
        if ($action === R2BCSignalEnum::SELL) {
            $maxPrice = 0;
            foreach ($priceKeys as $priceKey) {
                $price = $this->redisClient->get($priceKey);
                $currentOrderId = explode('-', $priceKey)[0];
                $link = $this->redisClient->keys("*-$currentOrderId-link");

                if ($link === [] && $currentPrice > $price && $price > $maxPrice) {
                    $maxPrice = $price;
                    $resultOrderId = $currentOrderId;
                }
            }
        } else {
            $minPrice = 10000;
            foreach ($priceKeys as $priceKey) {
                $price = $this->redisClient->get($priceKey);
                $currentOrderId = explode('-', $priceKey)[0];
                $link = $this->redisClient->keys("*-$currentOrderId-link");

                if ($link === [] && $currentPrice < $price && $price < $minPrice) {
                    $minPrice = $price;
                    $resultOrderId = $currentOrderId;
                }
            }
        }

        $key = "$orderId-$tp-$ticker-$action";
        $this->redisClient->set($key . '-price', $currentPrice);

        if ($resultOrderId === '') {
            $this->redisClient->set($key . '-state', 0);
            return 0;
        }

        $this->redisClient->set("$orderId-$resultOrderId-link", 1);
        $lastKey = "$resultOrderId-$tp-$ticker-$action";
        $prevState = $this->redisClient->get($lastKey . '-state');

        if ($ticker === 'EUR.USD') {
            if ($prevState > 4) {
                $prevPrice = $action === R2BCSignalEnum::SELL ? $maxPrice : $minPrice;
                if (abs((float)$currentPrice - (float)$prevPrice) < 0.0005) {
                    $this->redisClient->set($key . '-state', $prevState);
                    return $prevState;
                }
            }
        }

        $newState = $prevState + 1;
        $this->redisClient->set($key . '-state', $newState);

        return $newState;
    }
}
