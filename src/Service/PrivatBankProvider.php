<?php

declare(strict_types=1);

namespace App\Service;

final class PrivatBankProvider extends RatesProviderBase
{
    public function getApiParams(): array
    {
        return [
            'query' => [
                'date' => date('d.m.Y'),
            ]
        ];
    }
    
    public function fetchRatesFromApi(array $raw): array
    {
        $formattedRates = [];

        if (empty($raw['exchangeRate'])) {
            throw new \RuntimeException(sprintf("%s API Error: exchange rates not found", $this->bankName));
        }

        foreach ($raw['exchangeRate'] as $rate) {
            // Search only by one base currency.
            // Searchable currency shouldn't be as base currency.
            if ($rate['currency'] !== $this->baseCurrency) {
                $formattedRates[$rate['currency']] = [
                    'sell' => $rate['saleRate'] ?? $rate['saleRateNB'],
                    'buy' => $rate['purchaseRate'] ?? $rate['purchaseRateNB'],
                ];
            }
        }

        return $formattedRates;
    }
}
