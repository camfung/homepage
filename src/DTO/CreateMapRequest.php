<?php

declare(strict_types=1);

namespace TrafficPortal\DTO;

/**
 * Create Map Request DTO
 *
 * Data Transfer Object for creating a masked record (shortlink)
 *
 * @package TrafficPortal\DTO
 */
class CreateMapRequest
{
    private int $uid;
    private string $tpKey;
    private string $domain;
    private string $destination;
    private string $status;
    private string $type;
    private int $isSet;
    private string $tags;
    private string $notes;
    private string $settings;
    private int $cacheContent;

    /**
     * Constructor
     *
     * @param int $uid User ID
     * @param string $tpKey The short key for the redirect
     * @param string $domain The domain for the shortlink
     * @param string $destination The destination URL
     * @param string $status Status (e.g., 'active', 'inactive')
     * @param string $type Type of redirect (default: 'redirect')
     * @param int $isSet Whether this is a set (0 or 1, default: 0)
     * @param string $tags Tags for the record (default: '')
     * @param string $notes Notes for the record (default: '')
     * @param string $settings Settings JSON string (default: '{}')
     * @param int $cacheContent Whether to cache content (0 or 1, default: 0)
     */
    public function __construct(
        int $uid,
        string $tpKey,
        string $domain,
        string $destination,
        string $status = 'active',
        string $type = 'redirect',
        int $isSet = 0,
        string $tags = '',
        string $notes = '',
        string $settings = '{}',
        int $cacheContent = 0
    ) {
        $this->uid = $uid;
        $this->tpKey = $tpKey;
        $this->domain = $domain;
        $this->destination = $destination;
        $this->status = $status;
        $this->type = $type;
        $this->isSet = $isSet;
        $this->tags = $tags;
        $this->notes = $notes;
        $this->settings = $settings;
        $this->cacheContent = $cacheContent;
    }

    /**
     * Convert to array for JSON encoding
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'uid' => $this->uid,
            'tpKey' => $this->tpKey,
            'domain' => $this->domain,
            'destination' => $this->destination,
            'status' => $this->status,
            'type' => $this->type,
            'is_set' => $this->isSet,
            'tags' => $this->tags,
            'notes' => $this->notes,
            'settings' => $this->settings,
            'cache_content' => $this->cacheContent,
        ];
    }

    // Getters
    public function getUid(): int { return $this->uid; }
    public function getTpKey(): string { return $this->tpKey; }
    public function getDomain(): string { return $this->domain; }
    public function getDestination(): string { return $this->destination; }
    public function getStatus(): string { return $this->status; }
    public function getType(): string { return $this->type; }
    public function getIsSet(): int { return $this->isSet; }
    public function getTags(): string { return $this->tags; }
    public function getNotes(): string { return $this->notes; }
    public function getSettings(): string { return $this->settings; }
    public function getCacheContent(): int { return $this->cacheContent; }
}
