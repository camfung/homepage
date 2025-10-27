<?php

declare(strict_types=1);

namespace TrafficPortal\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use TrafficPortal\DTO\CreateMapRequest;

/**
 * Unit tests for CreateMapRequest DTO
 */
class CreateMapRequestTest extends TestCase
{
    public function testConstructorSetsPropertiesCorrectly(): void
    {
        $request = new CreateMapRequest(
            uid: 125,
            tpKey: 'testkey',
            domain: 'dev.trfc.link',
            destination: 'https://example.com',
            status: 'active',
            type: 'redirect',
            isSet: 0,
            tags: 'test,demo',
            notes: 'Test note',
            settings: '{"foo":"bar"}',
            cacheContent: 1
        );

        $this->assertSame(125, $request->getUid());
        $this->assertSame('testkey', $request->getTpKey());
        $this->assertSame('dev.trfc.link', $request->getDomain());
        $this->assertSame('https://example.com', $request->getDestination());
        $this->assertSame('active', $request->getStatus());
        $this->assertSame('redirect', $request->getType());
        $this->assertSame(0, $request->getIsSet());
        $this->assertSame('test,demo', $request->getTags());
        $this->assertSame('Test note', $request->getNotes());
        $this->assertSame('{"foo":"bar"}', $request->getSettings());
        $this->assertSame(1, $request->getCacheContent());
    }

    public function testConstructorWithDefaults(): void
    {
        $request = new CreateMapRequest(
            uid: 125,
            tpKey: 'testkey',
            domain: 'dev.trfc.link',
            destination: 'https://example.com'
        );

        $this->assertSame('active', $request->getStatus());
        $this->assertSame('redirect', $request->getType());
        $this->assertSame(0, $request->getIsSet());
        $this->assertSame('', $request->getTags());
        $this->assertSame('', $request->getNotes());
        $this->assertSame('{}', $request->getSettings());
        $this->assertSame(0, $request->getCacheContent());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $request = new CreateMapRequest(
            uid: 125,
            tpKey: 'testkey',
            domain: 'dev.trfc.link',
            destination: 'https://example.com',
            status: 'active',
            type: 'redirect',
            isSet: 0,
            tags: '',
            notes: 'Test',
            settings: '{}',
            cacheContent: 0
        );

        $array = $request->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('uid', $array);
        $this->assertArrayHasKey('tpKey', $array);
        $this->assertArrayHasKey('domain', $array);
        $this->assertArrayHasKey('destination', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('is_set', $array);
        $this->assertArrayHasKey('tags', $array);
        $this->assertArrayHasKey('notes', $array);
        $this->assertArrayHasKey('settings', $array);
        $this->assertArrayHasKey('cache_content', $array);

        $this->assertSame(125, $array['uid']);
        $this->assertSame('testkey', $array['tpKey']);
    }

    public function testToArrayUsesSnakeCaseForApiFields(): void
    {
        $request = new CreateMapRequest(
            uid: 125,
            tpKey: 'testkey',
            domain: 'dev.trfc.link',
            destination: 'https://example.com'
        );

        $array = $request->toArray();

        // Verify snake_case for API compatibility
        $this->assertArrayHasKey('is_set', $array);
        $this->assertArrayHasKey('cache_content', $array);
        $this->assertArrayNotHasKey('isSet', $array);
        $this->assertArrayNotHasKey('cacheContent', $array);
    }
}
