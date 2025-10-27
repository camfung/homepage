<?php

declare(strict_types=1);

namespace TrafficPortal\Tests\Integration;

use PHPUnit\Framework\TestCase;
use TrafficPortal\TrafficPortalApiClient;
use TrafficPortal\DTO\CreateMapRequest;
use TrafficPortal\DTO\CreateMapResponse;
use TrafficPortal\Exception\AuthenticationException;
use TrafficPortal\Exception\ValidationException;
use TrafficPortal\Exception\NetworkException;

/**
 * Integration tests for TrafficPortalApiClient
 *
 * These tests make actual API calls to the dev environment.
 * Set environment variables to run these tests:
 * - TP_API_ENDPOINT
 * - TP_API_KEY
 * - TP_TEST_UID
 * - TP_TEST_TOKEN
 */
class TrafficPortalApiClientIntegrationTest extends TestCase
{
    private TrafficPortalApiClient $client;
    private int $testUid;
    private bool $skipIntegrationTests = false;

    protected function setUp(): void
    {
        // Check if integration tests should run
        $apiEndpoint = getenv('TP_API_ENDPOINT');
        $apiKey = getenv('TP_API_KEY');
        $this->testUid = (int) getenv('TP_TEST_UID');

        if (!$apiEndpoint || !$apiKey || !$this->testUid) {
            $this->skipIntegrationTests = true;
            $this->markTestSkipped(
                'Integration tests skipped. Set environment variables: ' .
                'TP_API_ENDPOINT, TP_API_KEY, TP_TEST_UID'
            );
            return;
        }

        $this->client = new TrafficPortalApiClient(
            $apiEndpoint,
            $apiKey,
            30
        );
    }

    public function testCreateMaskedRecordSuccessfully(): void
    {
        if ($this->skipIntegrationTests) {
            $this->markTestSkipped('Integration tests disabled');
        }

        $tpKey = 'phptest' . time();
        $request = new CreateMapRequest(
            uid: $this->testUid,
            tpKey: $tpKey,
            domain: 'dev.trfc.link',
            destination: 'https://example.com',
            status: 'active',
            type: 'redirect',
            isSet: 0,
            tags: 'test,integration',
            notes: 'Created by PHPUnit integration test',
            settings: '{}',
            cacheContent: 0
        );

        $response = $this->client->createMaskedRecord($request);

        $this->assertInstanceOf(CreateMapResponse::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertSame('Record Created', $response->getMessage());
        $this->assertNotNull($response->getMid());
        $this->assertSame($tpKey, $response->getTpKey());
        $this->assertSame('dev.trfc.link', $response->getDomain());
        $this->assertSame('https://example.com', $response->getDestination());
    }

    public function testCreateMaskedRecordWithInvalidAuthenticationThrowsException(): void
    {
        if ($this->skipIntegrationTests) {
            $this->markTestSkipped('Integration tests disabled');
        }

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(401);

        $request = new CreateMapRequest(
            uid: 99999, // Invalid UID
            tpKey: 'testkey' . time(),
            domain: 'dev.trfc.link',
            destination: 'https://example.com'
        );

        $this->client->createMaskedRecord($request);
    }

    public function testCreateMaskedRecordWithDuplicateKeyThrowsValidationException(): void
    {
        if ($this->skipIntegrationTests) {
            $this->markTestSkipped('Integration tests disabled');
        }

        // First, create a record
        $tpKey = 'duplicate' . time();
        $request1 = new CreateMapRequest(
            uid: $this->testUid,
            tpKey: $tpKey,
            domain: 'dev.trfc.link',
            destination: 'https://example.com'
        );

        $response1 = $this->client->createMaskedRecord($request1);
        $this->assertTrue($response1->isSuccess());

        // Try to create duplicate
        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(400);

        $request2 = new CreateMapRequest(
            uid: $this->testUid,
            tpKey: $tpKey, // Same key
            domain: 'dev.trfc.link',
            destination: 'https://example2.com'
        );

        $this->client->createMaskedRecord($request2);
    }

    public function testCreateMaskedRecordWithAllFieldsPopulated(): void
    {
        if ($this->skipIntegrationTests) {
            $this->markTestSkipped('Integration tests disabled');
        }

        $tpKey = 'fulltest' . time();
        $request = new CreateMapRequest(
            uid: $this->testUid,
            tpKey: $tpKey,
            domain: 'dev.trfc.link',
            destination: 'https://example.com/full-test',
            status: 'active',
            type: 'redirect',
            isSet: 0,
            tags: 'integration,full-test,phpunit',
            notes: 'Full integration test with all fields',
            settings: '{"test": true, "version": 1}',
            cacheContent: 0
        );

        $response = $this->client->createMaskedRecord($request);

        $this->assertTrue($response->isSuccess());
        $this->assertNotNull($response->getMid());

        $source = $response->getSource();
        $this->assertNotNull($source);
        $this->assertArrayHasKey('tags', $source);
        $this->assertArrayHasKey('notes', $source);
        $this->assertArrayHasKey('settings', $source);
    }

    public function testInvalidApiEndpointThrowsNetworkException(): void
    {
        $client = new TrafficPortalApiClient(
            'https://invalid-endpoint-that-does-not-exist-12345.com/api',
            'test-key',
            5
        );

        $this->expectException(NetworkException::class);

        $request = new CreateMapRequest(
            uid: 1,
            tpKey: 'key',
            domain: 'test.com',
            destination: 'https://example.com'
        );

        $client->createMaskedRecord($request);
    }

    public function testTimeoutIsRespected(): void
    {
        // Create client with very short timeout
        $client = new TrafficPortalApiClient(
            getenv('TP_API_ENDPOINT') ?: 'https://example.com',
            getenv('TP_API_KEY') ?: 'test-key',
            1 // 1 second timeout
        );

        $this->assertSame(1, $client->getTimeout());
    }
}
