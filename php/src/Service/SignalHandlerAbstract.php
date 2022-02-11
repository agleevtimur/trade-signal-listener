<?php

namespace App\Service;

use App\DTO\BaseOrderDTO;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

abstract class SignalHandlerAbstract
{
    protected static string $channelId;

    abstract protected function resolve(string $text, string $messageLink, int $messageId = 0): void;

    abstract protected function parse(string $text): ?BaseOrderDTO;

    public function __construct(
        protected LoggerInterface $logger,
        private Client            $client,
        private string            $endpoint,
    )
    {
    }

    protected function send(BaseOrderDTO $signal): void
    {
        try {
            $response = $this->client->post($this->endpoint, [
                'json' => $signal
           ]);
        } catch (\Exception $exception) {
            $this->logger->info(json_encode($signal), [$exception->getMessage()]);
            return;
        }

        $result = $response->getStatusCode() === 200 ? 'successful' : 'error';
        $this->logger->info(json_encode($signal), ["signal sent $result"]);
    }
}
