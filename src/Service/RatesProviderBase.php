<?php

namespace App\Service;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class RatesProviderBase implements RatesProviderInterface 
{
    use FileHandlerTrait;

    public function __construct(
        protected HttpClientInterface $client,
        protected EmailService $emailService,
        protected string $outputRoot,
        protected string $bankName,
        protected string $apiUrl,
        protected string $baseCurrency
    ) { }

    public function processRates(float $threshold, SymfonyStyle $io): void
    {
        $response = $this->connectApi($this->apiUrl, $this->getApiParams(), $io);
        $filePath = $this->getFilePath();
        $isFirstFetch = $this->isFirstFetch();
        $newRates = $this->fetchRatesFromApi($response);
        $oldRates = $this->loadOldRates($filePath);
        // Compare old & new rates; print new rates in console.
        $changes = $this->printRates($newRates, $oldRates, $threshold, $io);
        $this->saveRates($filePath, $newRates);

        $payload = [
            'bank' => $this->bankName,
            'newRates' => $newRates,
            'changes' => $changes,
            'threshold' => $threshold,
            'isFirstFetch' => $isFirstFetch,
        ];
        $this->emailService->sendRatesEmail($payload, $io);
    }

    public function connectApi(string $url, array $parameters = null, SymfonyStyle $io): array {
        $response = $this->client->request('GET', $url, $parameters);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(sprintf("Error accessing %s API", $this->bankName));
        }

        return $response->toArray();
    }

    public function getApiParams(): array {
        return [];
    }

    protected function printRates(array $newRates, array $oldRates, float $threshold, SymfonyStyle $io): array
    {
        $changes = [];
    
        foreach ($newRates as $currency => $rate) {
            // Print rate inconsole
            $this->printRateToConsole($currency, $rate['buy'], $rate['sell'], $io);
            // Compare old & new values.
            $rateChanges = $this->checkRateChange($currency, $rate, $oldRates, $threshold);
    
            if (!empty($rateChanges)) {
                $changes[$currency] = $rateChanges;
            }
        }
    
        return $changes;
    }
    
    private function printRateToConsole(string $currency, float $buy, float $sell, SymfonyStyle $io): void
    {
        $io->writeln($this->emailService->printRate($currency, $buy, $sell));
    }
    
    private function checkRateChange(string $currency, array $newRate, array $oldRates, float $threshold): array
    {
        if (!isset($oldRates[$currency])) {
            return [];
        }
    
        $buyNew = $newRate['buy'];
        $sellNew = $newRate['sell'];
    
        $buyOld = $oldRates[$currency]['buy'];
        $sellOld = $oldRates[$currency]['sell'];
    
        $buyChange = $this->calculateChange($buyNew, $buyOld);
        $sellChange = $this->calculateChange($sellNew, $sellOld);
    
        if ($buyChange > $threshold || $sellChange > $threshold) {
            return [
                'buy' => $buyNew,
                'sell' => $sellNew,
            ];
        }
    
        return [];
    }
    
    private function calculateChange(float $newRate, float $oldRate): float {
        return $oldRate ? abs(($newRate - $oldRate) / $oldRate * 100) : 0;
    }
}