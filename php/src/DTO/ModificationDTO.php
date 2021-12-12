<?php

namespace App\DTO;

class ModificationDTO extends BaseOrderDTO
{
    public ?float $previousTakeProfit = null;
    public ?float $previousStopLoss = null;

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'previousTakeProfit' => $this->previousTakeProfit,
            'previousStopLoss' => $this->previousStopLoss
        ]);
    }
}