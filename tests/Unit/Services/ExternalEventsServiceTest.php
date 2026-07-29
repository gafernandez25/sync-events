<?php

namespace Tests\Unit\Services;

use App\Services\ExternalEventsService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use RuntimeException;
use Tests\TestCase;

class ExternalEventsServiceTest extends TestCase
{
    public function test_it_make_request_with_headers(): void
    {
        // Arrange
        $history = [];

        $mockHandler = new MockHandler([
            new Response(200, [], '<events />'),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($history));

        $client = new Client([
            'handler' => $handlerStack,
        ]);

        $uri = 'https://provider.example.test/events';

        $headers = [
            'Accept' => 'application/xml',
            'X-Provider-Token' => '<provider-token>',
        ];

        $service = new ExternalEventsService($client);

        // Act
        $response = $service->fetchExternalEvents($uri, 'POST', $headers);

        // Assert
        $this->assertSame('<events />', $response);

        $this->assertCount(1, $history);

        $request = $history[0]['request'];

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame($uri, (string) $request->getUri());

        $this->assertSame('application/xml', $request->getHeaderLine('Accept'));
        $this->assertSame('<provider-token>', $request->getHeaderLine('X-Provider-Token'));
    }

    public function test_it_throws_exception_when_provider_returns_non_successful_status_code(): void
    {
        // Arrange
        $mockHandler = new MockHandler([
            new Response(500, [], 'Provider error'),
        ]);

        $client = new Client([
            'handler' => HandlerStack::create($mockHandler),
        ]);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('External events provider returned HTTP status 500.');

        // Act
        (new ExternalEventsService($client))->fetchExternalEvents('https://provider.example.test/events');;
    }

    public function test_it_throws_exception_when_provider_connection_fails(): void
    {
        // Arrange
        $request = new Request('GET', 'https://provider.example.test/events');

        $mockHandler = new MockHandler([
            new ConnectException('Connection failed.', $request),
        ]);

        $client = new Client([
            'handler' => HandlerStack::create($mockHandler),
        ]);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not connect to external events provider.');

        // Act
        (new ExternalEventsService($client))->fetchExternalEvents('https://provider.example.test/events');;
    }
}
