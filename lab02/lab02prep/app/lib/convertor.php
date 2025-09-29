<?php
/**
 * Currency exchange
 */

class Convertor {
    /**
     * Contains exchange rates based on MDL in the following format:
     * [
     *   {'date' => 'YYYY-MM-DD', 'usd'=>float, 'eur'=>float, 'rub'=>float, 'ron'=>float, 'uah'=>float},
     *   ...
     * ]
     * @var array
     */
    private $exchangeRates;

    public function __construct(array $exchangeRates) {
        $this->exchangeRates = $exchangeRates;
    }

    public function exchange(Currency $from, Currency $to, DateTime $date = new DateTime()): float {
        $dateString = $date->format('Y-m-d');
        $rate = end($this->exchangeRates);

        foreach($this->exchangeRates as $exchangeRate) {
            if($exchangeRate->date === $dateString) {
                $rate = $exchangeRate;
            }
        }
        // a little fix for MDL
        $rate->mdl = 1.0;
        $fromCurrency = strtolower($from->name);
        $toCurrency = strtolower($to->name);

        if(empty($rate->{$fromCurrency}) ) {
            throw new Exception("Unknown currency {$fromCurrency}");
        }
        if(empty($rate->{$toCurrency}) ) {
            throw new Exception("Unknown currency {$toCurrency}");
        }

        return $rate->{$toCurrency} / $rate->{$fromCurrency};
    }
}
