<?php

namespace App\DTO;

class CloseOrderDTO extends BaseOrderDTO
{
    public array $extra = [];

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
           'extra' => $this->extra
        ]);
    }
}
