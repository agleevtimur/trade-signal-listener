<?php

namespace App\Service;

use Predis\Client;

class RedisClient
{
    private ?Client $client = null;

    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client([
                'host' => 'redis',
                'port' => 6379,
                'persistent' => '1'
            ]);
        }

        return $this->client;
    }
}