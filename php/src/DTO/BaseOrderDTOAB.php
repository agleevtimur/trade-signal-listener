<?php

namespace App\DTO;

class BaseOrderDTOAB extends BaseOrderDTO
{
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
            'messageId' => $this->messageId
        ];
    }
}