<?php

declare(strict_types=1);

namespace TrafficPortal;

use TrafficPortal\DTO\CreateMapRequest;
use TrafficPortal\DTO\CreateMapResponse;
use TrafficPortal\Exception\ApiException;
use TrafficPortal\Exception\AuthenticationException;
use TrafficPortal\Exception\ValidationException;
use TrafficPortal\Exception\NetworkException;

/**
 * Traffic Portal API Client
 *
 * Client for interacting with the Traffic Portal API /items endpoint
 * to create masked records (shortlinks).
 *
 * @package TrafficPortal
 */
class TrafficPortalApiClient
{
    private string $apiEndpoint;
    private string $apiKey;
    private int $timeout;

    /**
     * Constructor
     *
     * @param string $apiEndpoint The API endpoint URL
     * @param string $apiKey The API key for authentication
     * @param int $timeout Request timeout in seconds (default: 30)
     */
    public function __construct(
        string $apiEndpoint,
        string $apiKey,
        int $timeout = 30
    ) {
        $this->apiEndpoint = rtrim($apiEndpoint, '/');
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }

    /**
     * Create a masked record (shortlink)
     *
     * @param CreateMapRequest $request The request data
     * @return CreateMapResponse The response data
     * @throws AuthenticationException If authentication fails
     * @throws ValidationException If validation fails
     * @throws NetworkException If network error occurs
     * @throws ApiException For other API errors
     */
    public function createMaskedRecord(CreateMapRequest $request): CreateMapResponse
    {
        $url = $this->apiEndpoint . '/items';
        $payload = $request->toArray();

        // Initialize cURL
        $ch = curl_init($url);
        if ($ch === false) {
            throw new NetworkException('Failed to initialize cURL');
        }

        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);

        curl_close($ch);

        // Handle cURL errors
        if ($curlErrno !== 0) {
            throw new NetworkException(
                sprintf('cURL error: %s', $curlError),
                $curlErrno
            );
        }

        // Handle empty response
        if ($response === false || $response === '') {
            throw new NetworkException('Empty response from API');
        }

        // Decode JSON response
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException(
                sprintf('Invalid JSON response: %s', json_last_error_msg()),
                $httpCode
            );
        }

        // Handle HTTP errors
        $this->handleHttpErrors($httpCode, $data);

        // Parse and return response
        return CreateMapResponse::fromArray($data);
    }

    /**
     * Handle HTTP error responses
     *
     * @param int $httpCode The HTTP status code
     * @param array $data The response data
     * @throws AuthenticationException If authentication fails
     * @throws ValidationException If validation fails
     * @throws ApiException For other API errors
     */
    private function handleHttpErrors(int $httpCode, array $data): void
    {
        if ($httpCode >= 200 && $httpCode < 300) {
            return; // Success
        }

        $message = $data['message'] ?? 'Unknown error';
        $success = $data['success'] ?? false;

        switch ($httpCode) {
            case 401:
                throw new AuthenticationException($message, $httpCode);

            case 400:
                throw new ValidationException($message, $httpCode);

            case 502:
            case 500:
                throw new ApiException(
                    sprintf('Server error: %s', $message),
                    $httpCode
                );

            default:
                throw new ApiException(
                    sprintf('API error (HTTP %d): %s', $httpCode, $message),
                    $httpCode
                );
        }
    }

    /**
     * Get the API endpoint
     *
     * @return string
     */
    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    /**
     * Get the timeout setting
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
