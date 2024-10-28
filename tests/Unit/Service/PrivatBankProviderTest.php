<?php

declare(strict_types=1);

namespace App\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use App\Service\PrivatBankProvider;
use App\Service\EmailService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PrivatBankProviderTest extends TestCase
{
    private MockObject $emailService;

    protected function setUp(): void
    {
        $this->emailService = $this->createMock(EmailService::class);
    }

    public function testFetchRatesFromApi(): void
    {
        $inputData = [
            'exchangeRate' => [
                ['currency' => 'USD', 'purchaseRate' => 41.05, 'saleRate' => 41.5],
                ['currency' => 'EUR', 'purchaseRate' => 44.33, 'saleRate' => 45.05],
            ],
        ];

        // Mock the HTTP client response
        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock->method('request')
            ->willReturn(new MockResponse(json_encode($inputData)));

        $provider = new PrivatBankProvider(
            $httpClientMock,
            $this->emailService,
            '/files',
            'PrivatBank',
            'https://api.privatbank.ua/p24api/exchange_rates',
            'UAH'
        );

        $rates = $provider->fetchRatesFromApi($inputData);

        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertEquals(41.05, $rates['USD']['buy']);
        $this->assertEquals(44.33, $rates['EUR']['buy']);
    }
}
