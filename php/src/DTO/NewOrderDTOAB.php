<?php

namespace App\DTO;

class NewOrderDTOAB extends BaseOrderDTOAB
{
    public string $contractType;

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'contractType' => $this->contractType,
        ]);
    }
}