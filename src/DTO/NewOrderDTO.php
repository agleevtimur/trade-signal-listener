<?php

namespace App\DTO;

use JsonSerializable;

class NewOrderDTO extends BaseOrderDTO
{
    public string $channelId;
    public string $ticker;
    public string $contractType;
    public ?float $price = null;
    public float $percentage;

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'channelId' => $this->channelId,
            'ticker' => $this->ticker,
            'contractType' => $this->contractType,
            'price' => $this->price,
            'percentage' => $this->percentage
        ]);
    }
}