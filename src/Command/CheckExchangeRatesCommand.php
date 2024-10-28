<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-exchange-rates',
    description: 'Check exchange rates and notify if there are changes beyond a set threshold',
    aliases: ['app:cer'],
)]
class CheckExchangeRatesCommand extends Command
{
    public function __construct(
        private readonly float $thresholdDefault,
        private array $bankProviders,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('bank', InputArgument::REQUIRED, 'Choose a bank')
            ->addArgument('threshold', InputArgument::REQUIRED, 'Input threshold percentage')
        ;
    }    

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get list of banks.
        $banks = array_keys($this->bankProviders);
        // Bank from user input.
        $bank = $input->getArgument('bank');
        // Threshold from user input.
        $threshold = (float) $input->getArgument('threshold');

        if (!in_array($bank, $banks)) {
            $io->error(sprintf("%s not found in the system. Registered banks - %s", $bank, implode(", ", $banks)));
    
            return Command::FAILURE;
        }

        if (!is_numeric($threshold) || $threshold <= 0) {
            $io->error('Threshold value should be numeric and > 0!');

            return Command::FAILURE;
        }

        $io->title("Welcome to exchange rate checker!");

        // Get the rates.
        $io->title(sprintf('Fetching rates from %s', $bank));
        $io->progressStart(100);
        $this->bankProviders[$bank]->processRates($threshold, $io);
        $io->progressFinish();

        return Command::SUCCESS;
    }
}
