<?php

namespace App\Service;

use Symfony\Component\Console\Style\SymfonyStyle;

interface RatesProviderInterface
{
    public function processRates(float $threshold, SymfonyStyle $io): void;
    
    public function connectApi(string $url, SymfonyStyle $io, array $parameters = null): array;

    public function getApiParams(): array;

    public function fetchRatesFromApi(array $raw): array;

}
