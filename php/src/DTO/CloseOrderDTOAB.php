<?php

namespace App\DTO;

class CloseOrderDTOAB extends BaseOrderDTOAB
{
    public array $extra = [];

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'extra' => $this->extra
        ]);
    }
}