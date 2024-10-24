<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Console\Style\SymfonyStyle;

class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $mailFrom,
        private readonly string $mailTo
    ) {}

    public function sendRatesEmail(array $payload, SymfonyStyle $io): void {
        $subject = sprintf("Exchange Rates in %s", $payload['bank']);
        $body = "";

        if (!empty($payload['changes'])) {
            $subject = sprintf("Currency Exchange Rates Changes in %s", $payload['bank']);
            $body .= sprintf("The following currencies have changed above the %s%%:\n", $payload['threshold']);
            $body .= $this->printList($payload['changes']);
            $body .= "\n";
        }

        $body .= "The full exchange rates list:\n";
        $body .= $this->printList($payload['newRates']);

        $email = (new Email())
            ->from($this->mailFrom)
            ->to($this->mailTo)
            ->subject($subject)
            ->text($body);

        if ($payload['isFirstFetch'] === true) {
            $io->success('Exchange rates received successfully! Email with the rates list is sent!');
            $this->mailer->send($email);
        } elseif (!empty($payload['changes'])) {
            $io->success(sprintf('There are some rates changes: %s. The list of changes was sent via email!', implode(', ', array_keys($payload['changes']))));
            $this->mailer->send($email);
        } else {
            $io->success('There no changes in exchange rates! No need to send email');
        }
    }

    public function printRate(string $currency, float $rateBuy, float $rateSell): string {
        return sprintf("%s - Buy: %.4f / Sell: %.4f", $currency, $rateBuy, $rateSell);
    }

    public function printList(array $data): string {
        $list = [];
    
        foreach ($data as $currency => $rate) {
            $list[] = $this->printRate($currency, $rate['buy'], $rate['sell']);
        }
    
        return implode("\n", $list);
    }
}