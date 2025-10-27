# Traffic Portal API Client for PHP

A modern, type-safe PHP client for the Traffic Portal API, specifically designed for creating masked records (shortlinks) via the POST /items endpoint.

## Features

- ✅ **Modern PHP 8.0+** with strict types and type declarations
- ✅ **PSR-4 autoloading** following PHP standards
- ✅ **Data Transfer Objects (DTOs)** for type safety
- ✅ **Custom exceptions** for granular error handling
- ✅ **Comprehensive test suite** (unit + integration tests)
- ✅ **Zero dependencies** (uses built-in cURL)
- ✅ **Well-documented** with PHPDoc comments

## Requirements

- PHP 8.0 or higher
- cURL extension enabled
- JSON extension enabled

## Installation

```bash
composer install
```

## Configuration

Copy the example environment file and configure your credentials:

```bash
cp .env.example .env
```

Edit `.env` with your actual values:

```env
TP_API_ENDPOINT=https://ce7jzbocq1.execute-api.ca-central-1.amazonaws.com/dev
TP_API_KEY=your-api-key-here
TP_TEST_UID=125
TP_TEST_TOKEN=your-test-token-here
```

## Usage

### Basic Example

```php
<?php

require_once 'vendor/autoload.php';

use TrafficPortal\TrafficPortalApiClient;
use TrafficPortal\DTO\CreateMapRequest;

// Initialize the client
$client = new TrafficPortalApiClient(
    apiEndpoint: 'https://api.example.com/dev',
    apiKey: 'your-api-key',
    timeout: 30
);

// Create a request
$request = new CreateMapRequest(
    uid: 125,
    tpTkn: 'your-token',
    tpKey: 'myshortlink',
    domain: 'dev.trfc.link',
    destination: 'https://example.com',
    status: 'active',
    type: 'redirect',
    isSet: 0,
    tags: 'marketing,campaign',
    notes: 'Campaign shortlink',
    settings: '{}',
    cacheContent: 0
);

// Make the API call
try {
    $response = $client->createMaskedRecord($request);

    if ($response->isSuccess()) {
        echo "Record created with ID: " . $response->getMid();
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Error Handling

The client provides specific exceptions for different error types:

```php
use TrafficPortal\Exception\AuthenticationException;
use TrafficPortal\Exception\ValidationException;
use TrafficPortal\Exception\NetworkException;
use TrafficPortal\Exception\ApiException;

try {
    $response = $client->createMaskedRecord($request);
} catch (AuthenticationException $e) {
    // Handle 401 authentication errors
    echo "Invalid credentials";
} catch (ValidationException $e) {
    // Handle 400 validation errors
    echo "Invalid data: " . $e->getMessage();
} catch (NetworkException $e) {
    // Handle network/cURL errors
    echo "Connection failed";
} catch (ApiException $e) {
    // Handle other API errors
    echo "API error: " . $e->getMessage();
}
```

## API Reference

### TrafficPortalApiClient

Main client class for interacting with the API.

#### Constructor

```php
public function __construct(
    string $apiEndpoint,
    string $apiKey,
    int $timeout = 30
)
```

#### Methods

##### createMaskedRecord()

```php
public function createMaskedRecord(CreateMapRequest $request): CreateMapResponse
```

Creates a masked record (shortlink) in the Traffic Portal system.

**Parameters:**
- `$request` - CreateMapRequest object with all required fields

**Returns:**
- `CreateMapResponse` - Response object with created record data

**Throws:**
- `AuthenticationException` - When authentication fails (401)
- `ValidationException` - When validation fails (400)
- `NetworkException` - When network errors occur
- `ApiException` - For other API errors

### CreateMapRequest

Data Transfer Object for API requests.

#### Required Fields

- `uid` (int) - User ID
- `tpTkn` (string) - User authentication token
- `tpKey` (string) - The short key for the redirect
- `domain` (string) - The domain for the shortlink
- `destination` (string) - The destination URL

#### Optional Fields (with defaults)

- `status` (string) - Status, default: 'active'
- `type` (string) - Type of redirect, default: 'redirect'
- `isSet` (int) - Whether this is a set (0 or 1), default: 0
- `tags` (string) - Tags for the record, default: ''
- `notes` (string) - Notes for the record, default: ''
- `settings` (string) - Settings JSON string, default: '{}'
- `cacheContent` (int) - Whether to cache content (0 or 1), default: 0

### CreateMapResponse

Data Transfer Object for API responses.

#### Methods

- `getMessage()` - Get response message
- `isSuccess()` - Check if operation was successful
- `getSource()` - Get full source data array
- `getMid()` - Get created record ID
- `getTpKey()` - Get the tpKey
- `getDomain()` - Get the domain
- `getDestination()` - Get the destination URL

## Testing

The package includes comprehensive unit and integration tests.

### Run All Tests

```bash
composer test
```

### Run Unit Tests Only

```bash
composer test-unit
```

### Run Integration Tests Only

```bash
composer test-integration
```

**Note:** Integration tests require environment variables to be set (see Configuration section).

### Test Coverage

The test suite includes:

#### Unit Tests
- ✅ CreateMapRequest DTO tests
- ✅ CreateMapResponse DTO tests
- ✅ TrafficPortalApiClient tests

#### Integration Tests
- ✅ Successful record creation
- ✅ Authentication failure handling
- ✅ Duplicate key validation
- ✅ Full field population
- ✅ Network error handling
- ✅ Timeout configuration

## Project Structure

```
.
├── src/
│   ├── TrafficPortalApiClient.php      # Main client class
│   ├── DTO/
│   │   ├── CreateMapRequest.php        # Request DTO
│   │   └── CreateMapResponse.php       # Response DTO
│   └── Exception/
│       ├── ApiException.php            # Base exception
│       ├── AuthenticationException.php # Auth errors
│       ├── ValidationException.php     # Validation errors
│       └── NetworkException.php        # Network errors
├── tests/
│   ├── Unit/                           # Unit tests
│   │   ├── DTO/
│   │   │   ├── CreateMapRequestTest.php
│   │   │   └── CreateMapResponseTest.php
│   │   └── TrafficPortalApiClientTest.php
│   └── Integration/                    # Integration tests
│       └── TrafficPortalApiClientIntegrationTest.php
├── composer.json                       # Composer configuration
├── phpunit.xml                         # PHPUnit configuration
├── example-usage.php                   # Usage example
└── README.md                           # This file
```

## Research & Best Practices

This client was built following modern PHP best practices based on research from:

1. **PHP: The Right Way** - Reference for PHP best practices
2. **PHPUnit Documentation** - Official testing framework documentation
3. **Modern PHP Practices 2025** - Contemporary PHP development standards
4. **API Client Design Patterns** - Best practices for PHP API clients

### Key Design Decisions

- **cURL over Guzzle**: Chosen for zero dependencies and built-in availability
- **Strict Types**: All files use `declare(strict_types=1)` for type safety
- **DTOs**: Separate request/response objects for clean data handling
- **Exception Hierarchy**: Specific exceptions for different error types
- **PSR Standards**: Following PSR-4 autoloading and PSR-12 coding style

## Known Issues & API Problems

During development, we discovered that the API endpoint has several issues:

1. **Missing Required Fields**: The original API documentation didn't mention these required fields:
   - `cache_content`
   - `type`
   - `is_set`
   - `tags`
   - `notes`
   - `settings`

2. **Poor Error Handling**: The Lambda function accesses fields directly (`body['field']`) instead of using safe access (`body.get('field', default)`), causing KeyError exceptions.

3. **502 Errors**: Missing any required field results in a 502 error instead of a proper 400 validation error.

This client handles all required fields properly to avoid these issues.

## License

Proprietary - Traffic Portal

## Contributing

This is an internal project. For issues or contributions, please contact the development team.
