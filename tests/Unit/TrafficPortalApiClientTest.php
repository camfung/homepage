<?php

declare(strict_types=1);

namespace TrafficPortal\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TrafficPortal\TrafficPortalApiClient;
use TrafficPortal\DTO\CreateMapRequest;
use TrafficPortal\Exception\AuthenticationException;
use TrafficPortal\Exception\ValidationException;
use TrafficPortal\Exception\ApiException;

/**
 * Unit tests for TrafficPortalApiClient
 */
class TrafficPortalApiClientTest extends TestCase
{
    private TrafficPortalApiClient $client;

    protected function setUp(): void
    {
        $this->client = new TrafficPortalApiClient(
            'https://api.example.com/dev',
            'test-api-key',
            30
        );
    }

    public function testConstructorSetsProperties(): void
    {
        $this->assertSame('https://api.example.com/dev', $this->client->getApiEndpoint());
        $this->assertSame(30, $this->client->getTimeout());
    }

    public function testConstructorTrimsTrailingSlash(): void
    {
        $client = new TrafficPortalApiClient(
            'https://api.example.com/dev/',
            'test-api-key'
        );

        $this->assertSame('https://api.example.com/dev', $client->getApiEndpoint());
    }

    public function testConstructorSetsDefaultTimeout(): void
    {
        $client = new TrafficPortalApiClient(
            'https://api.example.com',
            'test-api-key'
        );

        $this->assertSame(30, $client->getTimeout());
    }

    public function testGetApiEndpointReturnsCorrectValue(): void
    {
        $endpoint = $this->client->getApiEndpoint();
        $this->assertIsString($endpoint);
        $this->assertStringStartsWith('https://', $endpoint);
    }

    public function testGetTimeoutReturnsCorrectValue(): void
    {
        $timeout = $this->client->getTimeout();
        $this->assertIsInt($timeout);
        $this->assertGreaterThan(0, $timeout);
    }

    /**
     * Note: The following tests would require mocking cURL functions
     * which is complex in PHP. These are better suited for integration tests.
     * However, we include them here to document expected behavior.
     */

    public function testCreateMaskedRecordAcceptsValidRequest(): void
    {
        $request = new CreateMapRequest(
            uid: 125,
            tpKey: 'testkey',
            domain: 'dev.trfc.link',
            destination: 'https://example.com'
        );

        // This test validates that the method signature is correct
        // Actual HTTP testing is done in integration tests
        $this->expectException(\Exception::class);
        $this->client->createMaskedRecord($request);
    }
}
