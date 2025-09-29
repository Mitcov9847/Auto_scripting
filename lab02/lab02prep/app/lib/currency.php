<?php
/**
 * The default currency is `MDL` (Moldovan Leu) and the service provides
 * exchange rates for `USD`, `EUR`, `RON`, 'RUS' and 'UAH';
 */

enum Currency: string {
    case MDL = 'MDL';
    case USD = 'USD';
    case EUR = 'EUR';
    case RON = 'RON';
    case RUS = 'RUS';
    case UAH = 'UAH';
}

function IsKnownCurrency(string $currency): bool {
    return Currency::tryFrom($currency) !== null;
}