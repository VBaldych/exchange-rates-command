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
        $changes = $this->compareRates($newRates, $oldRates, $threshold, $io);
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

    protected function compareRates(array $newRates, array $oldRates, float $threshold, SymfonyStyle $io): array
    {
        $changes = [];

        foreach ($newRates as $currency => $rate) {
            $buy = $rate['buy'];
            $sell = $rate['sell'];

            // Print rate in console.
            $io->writeln($this->emailService->printRate($currency, $buy, $sell));

            if (isset($oldRates[$currency])) {
                $buyOld = $oldRates[$currency]['buy'];
                $sellOld = $oldRates[$currency]['sell'];

                // Calculate difference between old an new (in percents).
                $buyChange = $buyOld ? abs(($buy - $buyOld) / $buyOld * 100) : 0;
                $sellChange = $sellOld ? abs(($sell - $sellOld) / $sellOld * 100) : 0;

                if ($buyChange > $threshold || $sellChange > $threshold) {
                    $changes[$currency] = [
                        'buy' => $buy,
                        'sell' => $sell,
                    ];
                }
            }
        }

        return $changes;
    }
}