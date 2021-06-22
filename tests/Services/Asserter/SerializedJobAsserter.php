<?php

declare(strict_types=1);

namespace App\Tests\Services\Asserter;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class SerializedJobAsserter
{
    private const URL = 'http://localhost/status';

    public function __construct(private Client $httpClient)
    {
    }

    /**
     * @param array<mixed> $expectedJob
     */
    public function assertJob(array $expectedJob): void
    {
        $job = $this->fetchJob();

        TestCase::assertIsArray($job);

        TestCase::assertSame($expectedJob['label'], $job['label']);
        TestCase::assertSame($expectedJob['callback_url'], $job['callback_url']);
        TestCase::assertSame($expectedJob['maximum_duration_in_seconds'], $job['maximum_duration_in_seconds']);
        TestCase::assertSame($expectedJob['sources'], $job['sources']);
        TestCase::assertSame($expectedJob['compilation_state'], $job['compilation_state']);
        TestCase::assertSame($expectedJob['execution_state'], $job['execution_state']);

        $tests = $job['tests'];
        TestCase::assertIsArray($tests);

        foreach ($expectedJob['tests'] as $index => $expectedTest) {
            TestCase::assertArrayHasKey($index, $tests);
            $actualTest = $tests[$index];
            TestCase::assertIsArray($actualTest);

            $this->assertTest(
                $expectedTest['configuration'],
                $expectedTest['source'],
                $expectedTest['step_count'],
                $expectedTest['state'],
                $expectedTest['position'],
                $actualTest
            );
        }
    }

    /**
     * @param array<mixed> $expectedConfiguration
     * @param array<mixed> $actual
     */
    private function assertTest(
        array $expectedConfiguration,
        string $expectedSource,
        int $expectedStepCount,
        string $expectedState,
        int $expectedPosition,
        array $actual
    ): void {
        $this->assertTestConfiguration(
            $expectedConfiguration['browser'],
            $expectedConfiguration['url'],
            $actual['configuration']
        );

        TestCase::assertSame($expectedSource, $actual['source']);
        TestCase::assertArrayHasKey('target', $actual);
        TestCase::assertMatchesRegularExpression('/^Generated[0-9a-f]{32}Test\.php$/', $actual['target']);
        TestCase::assertSame($expectedStepCount, $actual['step_count']);
        TestCase::assertSame($expectedState, $actual['state']);
        TestCase::assertSame($expectedPosition, $actual['position']);
    }

    /**
     * @param array<mixed> $actual
     */
    private function assertTestConfiguration(string $expectedBrowser, string $expectedUrl, array $actual): void
    {
        TestCase::assertSame($expectedBrowser, $actual['browser']);
        TestCase::assertSame($expectedUrl, $actual['url']);
    }

    /**
     * @return array<mixed>
     */
    private function fetchJob(): array
    {
        $response = $this->httpClient->sendRequest(new Request('GET', self::URL));

        TestCase::assertSame(200, $response->getStatusCode());
        TestCase::assertSame('application/json', $response->getHeaderLine('content-type'));

        $body = $response->getBody()->getContents();

        return json_decode($body, true);
    }
}
