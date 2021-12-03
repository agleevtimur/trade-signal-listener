<?php

namespace App\Service;

 use App\DTO\BaseOrderDTO;
 use GuzzleHttp\Client;
 use Psr\Log\LoggerInterface;
 use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

 abstract class SignalHandlerAbstract
{
    protected static string $channelId;

    abstract protected function resolve(string $text, int $messageId = 0): void;
    abstract protected function parse(string $text): ?BaseOrderDTO;

    public function __construct(protected LoggerInterface $logger, private Client $client, private ParameterBagInterface $parameterBag) {}

     protected function send(BaseOrderDTO $signal): void
     {
         try {
             $response = $this->client->post($this->parameterBag->get('signal_receiver_url'), [
                 'json' => $signal
             ]);
         } catch (\Exception $exception) {
             $this->logger->info($exception->getMessage(), ['request error', 'signal' => json_encode($signal)]);
             return;
         }

         $result = $response->getStatusCode() === 200 ? 'successful' : 'error';
         $this->logger->info("signal sent $result", ['signal' => json_encode($signal)]);
     }
}