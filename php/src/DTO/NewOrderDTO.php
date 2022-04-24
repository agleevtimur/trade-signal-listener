<?php

namespace App\DTO;


class NewOrderDTO extends BaseOrderDTO
{
    public string $contractType;
    public bool $hasTakeProfit;

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'contractType' => $this->contractType,
        ]);
    }
}