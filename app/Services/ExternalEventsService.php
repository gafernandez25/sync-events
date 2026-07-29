<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use RuntimeException;

class ExternalEventsService
{
    public function __construct(private readonly Client $client)
    {
    }

    public function fetchExternalEvents(string $uri, string $method = 'GET', array $headers = []): string
    {
        try {
            $response = $this->client->request(
                $method,
                $uri,
                [
                    RequestOptions::TIMEOUT => 5,
                    RequestOptions::CONNECT_TIMEOUT => 2,
                    RequestOptions::HTTP_ERRORS => false,
                    RequestOptions::HEADERS => $headers,
                ],
            );
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Could not connect to external events provider.', previous: $exception);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException("External events provider returned HTTP status {$statusCode}.");
        }

        return $response->getBody()->getContents();
    }
}
