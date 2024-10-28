<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use PHPUnit\Framework\MockObject\MockObject;
use App\Service\MonobankProvider;
use App\Service\EmailService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MonobankProviderTest extends TestCase
{
    private MockObject $emailService;

    protected function setUp(): void
    {
        $this->emailService = $this->createMock(EmailService::class);
    }

    public function testFetchRatesFromApi(): void
    {
        $inputData = [
            ['currencyCodeA' => '840', 'currencyCodeB' => '980', 'rateBuy' => 41.05, 'rateSell' => 41.5],
            ['currencyCodeA' => '978', 'currencyCodeB' => '980', 'rateBuy' => 44.33, 'rateSell' => 45.0005],
        ];

        // Mock the HTTP client response
        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock->method('request')
            ->willReturn(new MockResponse(json_encode($inputData)));

        $provider = new MonobankProvider(
            $httpClientMock, 
            $this->emailService, 
            '/files', 
            'Monobank', 
            'https://api.monobank.ua/bank/currency', 
            '980'
        );

        $rates = $provider->fetchRatesFromApi($inputData);

        $this->assertArrayHasKey('840', $rates);
        $this->assertArrayHasKey('978', $rates);
        $this->assertEquals(41.05, $rates['840']['buy']);
        $this->assertEquals(44.33, $rates['978']['buy']);
    }
}
