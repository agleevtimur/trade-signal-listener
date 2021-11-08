<?php

namespace App\Event;

use App\Service\SignalHandlerManager;
use danog\MadelineProto\Db\MemoryArray;
use danog\MadelineProto\EventHandler;
use Doctrine\ORM\EntityManagerInterface;
use Generator;

class NewSignalEvent extends EventHandler
{
    protected static array $dbProperties = [
        'session' => [
            'type' => 'array123',
            'config' => ['enableCache' => false]
        ]
    ];

    protected array $dataStoredOnDb;

    public function onUpdateNewChannelMessage(array $update): Generator
    {
        return $this->onUpdateNewMessage($update);
    }

    /**
     * @param array $update Update
     * @return Generator
     */
    public function onUpdateNewMessage(array $update): Generator
    {
        if ($update['message']['_'] === 'messageEmpty' || $update['message']['out'] ?? false) {
            return yield null;
        }

        $channelId = $update['message']['peer_id']['channel_id'] ?? $update['message']['peer_id']['user_id'];

        SignalHandlerManager::handle($channelId, $update['message']['message'], $update['message']['id']);

        yield 1;
    }
}