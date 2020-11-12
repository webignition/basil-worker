<?php

declare(strict_types=1);

namespace App\Tests\Services\Guzzle\Handler;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

class StatusCodeFromHostnameHandler
{
    /**
     * @var HostStatusCodeGenerator[]
     */
    private array $statusCodeGenerators = [];

    public function __invoke(RequestInterface $request): PromiseInterface
    {
        return Create::promiseFor(
            new Response($this->deriveResponseStatusCode(
                $request->getUri()->getHost()
            ))
        );
    }

    private function deriveResponseStatusCode(string $hostname): int
    {
        if (!array_key_exists($hostname, $this->statusCodeGenerators)) {
            $this->statusCodeGenerators[$hostname] = new HostStatusCodeGenerator($hostname);
        }

        $hostStatusCode = $this->statusCodeGenerators[$hostname];

        return $hostStatusCode->getNext();
    }
}
