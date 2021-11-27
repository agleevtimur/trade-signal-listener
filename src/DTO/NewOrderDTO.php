<?php

namespace App\DTO;

use JsonSerializable;

class NewOrderDTO extends BaseOrderDTO
{
    public string $contractType;

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'contractType' => $this->contractType,
        ]);
    }
}