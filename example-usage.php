<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use TrafficPortal\TrafficPortalApiClient;
use TrafficPortal\DTO\CreateMapRequest;
use TrafficPortal\Exception\AuthenticationException;
use TrafficPortal\Exception\ValidationException;
use TrafficPortal\Exception\NetworkException;
use TrafficPortal\Exception\ApiException;

// Configuration
$apiEndpoint = 'https://ce7jzbocq1.execute-api.ca-central-1.amazonaws.com/dev';
$apiKey = 'q9D7lp99A818aVMcVM9vU1QoY7KM0SZa5lyw8M0d';

// Create client
$client = new TrafficPortalApiClient($apiEndpoint, $apiKey, 30);

try {
    // Create a masked record (shortlink)
    $request = new CreateMapRequest(
        uid: 125,
        tpKey: 'example' . time(), // Generate unique key
        domain: 'dev.trfc.link',
        destination: 'https://example.com',
        status: 'active',
        type: 'redirect',
        isSet: 0,
        tags: 'example,demo',
        notes: 'Created via PHP client',
        settings: '{}',
        cacheContent: 0
    );

    echo "Creating masked record...\n";
    $response = $client->createMaskedRecord($request);

    if ($response->isSuccess()) {
        echo "✓ Success!\n";
        echo "Message: " . $response->getMessage() . "\n";
        echo "Record ID: " . $response->getMid() . "\n";
        echo "TP Key: " . $response->getTpKey() . "\n";
        echo "Domain: " . $response->getDomain() . "\n";
        echo "Destination: " . $response->getDestination() . "\n";

        // Get full source data
        $source = $response->getSource();
        echo "\nFull response data:\n";
        print_r($source);
    }

} catch (AuthenticationException $e) {
    echo "✗ Authentication failed: " . $e->getMessage() . "\n";
    echo "Please check your UID and token.\n";
} catch (ValidationException $e) {
    echo "✗ Validation error: " . $e->getMessage() . "\n";
    echo "The key might already exist or the data is invalid.\n";
} catch (NetworkException $e) {
    echo "✗ Network error: " . $e->getMessage() . "\n";
    echo "Please check your internet connection and API endpoint.\n";
} catch (ApiException $e) {
    echo "✗ API error: " . $e->getMessage() . "\n";
    echo "HTTP Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "✗ Unexpected error: " . $e->getMessage() . "\n";
}
