<?php

namespace App\Service;

trait FileHandlerTrait
{
    private function loadOldRates(string $filePath): array
    {
        // Create new empty file if it's not exists.
        if ($this->isFirstFetch()) {
            $this->saveRates($filePath, []);
            
            return [];
        }

        $file = new \SplFileObject($filePath, 'r');
        $file->rewind();

        return json_decode($file->fread($file->getSize()), true);
    }

    private function saveRates(string $filePath, array $newRates): void
    {
        $file = new \SplFileObject($filePath, 'w');
        $file->fwrite(json_encode($newRates, JSON_PRETTY_PRINT));
    }
    
    public function isFirstFetch(): bool {
        $filePath = $this->getFilePath();

        if (!file_exists($filePath)) {
            return true;
        }

        return false;
    }

    public function getFilePath() {
        return $this->outputRoot . '/' . $this->bankName . '.json';
    }
}