<?php

namespace App\DTO;

use JsonSerializable;

abstract class BaseOrderDTO implements JsonSerializable
{
    public int $orderId;
    public string $type;
    public string $action;
    public ?float $takeProfit = null;
    public ?float $stopLoss = null;
    public int $messageId;

    public function jsonSerialize(): array
    {
        return [
            'orderId' => $this->orderId,
            'action' => $this->action,
            'type' => $this->type,
            'takeProfit' => $this->takeProfit,
            'stopLoss' => $this->stopLoss,
            'messageId' => $this->messageId
        ];
    }
}