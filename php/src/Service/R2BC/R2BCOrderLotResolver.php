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

    public function resolve(string $ticker, string $action, float $takeProfit): array
    {
        $key = $ticker . '-' . $action . '-' . $takeProfit;
        $result = ['key' => $key, 'lot' => 0.0];
        $step = $this->updateStateAndGetStep($key);

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

    private function updateStateAndGetStep(string $key): int
    {
        $countKey = $key . '-COUNT';

        $this->redisClient->incr($key);
        $this->redisClient->incr($countKey);

        return $this->redisClient->get($key);
    }
}
