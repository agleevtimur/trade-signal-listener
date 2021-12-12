<?php

namespace App\DTO;

use JsonSerializable;

abstract class BaseOrderDTO implements JsonSerializable
{
    public int $orderId;
    public string $channelId;
    public string $type;
    public string $ticker;
    public string $action;
    public ?float $price = null;
    public float $percentage;
    public ?float $takeProfit = null;
    public ?float $stopLoss = null;
    public int $messageId;

    public function jsonSerialize(): array
    {
        return [
            'orderId' => $this->orderId,
            'action' => $this->action,
            'type' => $this->type,
            'channelId' => $this->channelId,
            'ticker' => $this->ticker,
            'price' => $this->price,
            'percentage' => $this->percentage,
            'takeProfit' => $this->takeProfit,
            'stopLoss' => $this->stopLoss,
            'messageId' => $this->messageId
        ];
    }
}