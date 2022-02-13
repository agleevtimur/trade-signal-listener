<?php

namespace App\Service\R2BC;

class R2BCSignalEnum
{
    public const NEW_ORDER = 'НОВЫЙ ОРДЕР';
    public const CLOSE_ORDER = 'ЗАКРЫТИЕ ОРДЕРА';
    public const BUY = 'BUY';
    public const SELL = 'SELL';
    public const CLOSE_PRICE = 'ЦЕНА ЗАКРЫТИЯ';
    public const PROFIT = 'ПРИБЫЛЬ';

    public const LOT_START = 0.01;
    public const LOT_STEP = 0.01;
    public const LOT_MAX = 0.51;
}
