<?php

declare(strict_types=1);

namespace TrafficPortal\DTO;

/**
 * Create Map Response DTO
 *
 * Data Transfer Object for the response from creating a masked record
 *
 * @package TrafficPortal\DTO
 */
class CreateMapResponse
{
    private string $message;
    private bool $success;
    private ?array $source;

    /**
     * Constructor
     *
     * @param string $message Response message
     * @param bool $success Whether the operation was successful
     * @param array|null $source The created record data
     */
    public function __construct(
        string $message,
        bool $success,
        ?array $source = null
    ) {
        $this->message = $message;
        $this->success = $success;
        $this->source = $source;
    }

    /**
     * Create from API response array
     *
     * @param array $data The response data from API
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['message'] ?? '',
            $data['success'] ?? false,
            $data['source'] ?? null
        );
    }

    /**
     * Get the response message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Check if the operation was successful
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get the source data (created record)
     *
     * @return array|null
     */
    public function getSource(): ?array
    {
        return $this->source;
    }

    /**
     * Get the created record ID (mid)
     *
     * @return int|null
     */
    public function getMid(): ?int
    {
        return $this->source['mid'] ?? null;
    }

    /**
     * Get the tpKey from the created record
     *
     * @return string|null
     */
    public function getTpKey(): ?string
    {
        return $this->source['tpKey'] ?? null;
    }

    /**
     * Get the domain from the created record
     *
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->source['domain'] ?? null;
    }

    /**
     * Get the destination from the created record
     *
     * @return string|null
     */
    public function getDestination(): ?string
    {
        return $this->source['destination'] ?? null;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'success' => $this->success,
            'source' => $this->source,
        ];
    }
}
