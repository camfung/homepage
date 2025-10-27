<?php

declare(strict_types=1);

namespace TrafficPortal\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use TrafficPortal\DTO\CreateMapResponse;

/**
 * Unit tests for CreateMapResponse DTO
 */
class CreateMapResponseTest extends TestCase
{
    public function testFromArrayCreatesInstanceCorrectly(): void
    {
        $data = [
            'message' => 'Record Created',
            'success' => true,
            'source' => [
                'mid' => 123,
                'tpKey' => 'testkey',
                'domain' => 'dev.trfc.link',
                'destination' => 'https://example.com',
                'status' => 'active',
            ],
        ];

        $response = CreateMapResponse::fromArray($data);

        $this->assertSame('Record Created', $response->getMessage());
        $this->assertTrue($response->isSuccess());
        $this->assertIsArray($response->getSource());
        $this->assertSame(123, $response->getMid());
        $this->assertSame('testkey', $response->getTpKey());
        $this->assertSame('dev.trfc.link', $response->getDomain());
        $this->assertSame('https://example.com', $response->getDestination());
    }

    public function testFromArrayHandlesMissingFields(): void
    {
        $data = [];

        $response = CreateMapResponse::fromArray($data);

        $this->assertSame('', $response->getMessage());
        $this->assertFalse($response->isSuccess());
        $this->assertNull($response->getSource());
        $this->assertNull($response->getMid());
    }

    public function testFromArrayHandlesNullSource(): void
    {
        $data = [
            'message' => 'Error',
            'success' => false,
            'source' => null,
        ];

        $response = CreateMapResponse::fromArray($data);

        $this->assertSame('Error', $response->getMessage());
        $this->assertFalse($response->isSuccess());
        $this->assertNull($response->getSource());
        $this->assertNull($response->getMid());
        $this->assertNull($response->getTpKey());
        $this->assertNull($response->getDomain());
        $this->assertNull($response->getDestination());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $response = new CreateMapResponse(
            message: 'Record Created',
            success: true,
            source: [
                'mid' => 123,
                'tpKey' => 'testkey',
            ]
        );

        $array = $response->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('source', $array);
        $this->assertSame('Record Created', $array['message']);
        $this->assertTrue($array['success']);
        $this->assertSame(123, $array['source']['mid']);
    }

    public function testGettersReturnNullWhenSourceFieldsMissing(): void
    {
        $response = new CreateMapResponse(
            message: 'Test',
            success: true,
            source: ['other_field' => 'value']
        );

        $this->assertNull($response->getMid());
        $this->assertNull($response->getTpKey());
        $this->assertNull($response->getDomain());
        $this->assertNull($response->getDestination());
    }
}
