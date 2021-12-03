<?php

namespace App\Event;

use App\Service\SignalHandlerManager;
use danog\MadelineProto\Db\MemoryArray;
use danog\MadelineProto\EventHandler;
use Doctrine\ORM\EntityManagerInterface;
use Generator;

class NewSignalEvent extends EventHandler
{
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

        if (isset($update['message']['peer_id']['chat_id'])) {
            yield 1;
        }

        $channelId = $update['message']['peer_id']['channel_id'] ?? $update['message']['peer_id']['user_id'];
        SignalHandlerManager::handle($channelId, $update['message']['message'], $update['message']['id']);

        yield 1;
    }
}