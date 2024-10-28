<?php

namespace App\Service;

final class MonobankProvider extends RatesProviderBase
{
    public function fetchRatesFromApi(array $raw): array
    {
        $formattedRates = [];

        if (!empty($raw)) {
            foreach ($raw as $rate) {
                // Search only by one base currency.
                // Searchable currency shouldn't be as base currency.
                if ($rate['currencyCodeB'] == $this->baseCurrency && $rate['currencyCodeA'] != $this->baseCurrency) {
                    $formattedRates[$rate['currencyCodeA']] = [
                        'buy' => $rate['rateBuy'] ?? $rate['rateCross'],
                        'sell' => $rate['rateSell'] ?? $rate['rateCross'],
                    ];
                }
            }
        }

        return $formattedRates;
    }
}
