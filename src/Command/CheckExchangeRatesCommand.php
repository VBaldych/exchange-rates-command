<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-exchange-rates',
    description: 'Check exchange rates and notify if there are changes beyond a set threshold',
    aliases: ['app:cer'],
)]
class CheckExchangeRatesCommand extends Command
{
    public function __construct(
        private float $thresholdDefault,
        private array $bankProviders,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');
        $threshold = 0;

        // Get list of banks.
        $banks = array_keys($this->bankProviders);

        $io->title(sprintf("Welcome to exchange rate checker!"));

        // Ask a bank.
        $question = new ChoiceQuestion(
            sprintf("Please select a bank to check exchange rates [%s is default]: ", $banks[0]),
            $banks,
            0
        );
        $bank = $helper->ask($input, $output, $question);

        // Ask a threshold if it's non-first fetch.
        if (!$this->bankProviders[$bank]->isFirstFetch()) {
            $thresholdQuestion = new Question(
                sprintf("Please enter the threshold percentage [{$this->thresholdDefault} is default]: "), 
                $this->thresholdDefault
            );
            $threshold = $helper->ask($input, $output, $thresholdQuestion);
    
            // Check threshold value.
            if (!is_numeric($threshold) || $threshold <= 0) {
                $io->error('Threshold value should be numeric and > 0!');
    
                return Command::FAILURE;
            }
        }

        // Get the rates.
        $io->title(sprintf('Fetching rates from %s', $bank));
        $io->progressStart(100);
        $this->bankProviders[$bank]->processRates($threshold, $io);
        $io->progressFinish();

        return Command::SUCCESS;
    }
}
