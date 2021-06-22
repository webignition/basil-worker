<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Model\UploadedFileKey;
use App\Request\AddSourcesRequest;
use App\Services\CompilationState;
use App\Services\ExecutionState;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    private const MICROSECONDS_PER_SECOND = 1000000;
    private const WAIT_INTERVAL = self::MICROSECONDS_PER_SECOND * 1;
    private const WAIT_TIMEOUT = self::MICROSECONDS_PER_SECOND * 30;

    private Client $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = new Client();
    }

    public function testInitialStatus(): void
    {
        try {
            $response = $this->httpClient->get('http://localhost:/status');
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
        }

        self::assertSame(400, $response->getStatusCode());
    }

    /**
     * @depends testInitialStatus
     */
    public function testCreateJob(): void
    {
        $response = $this->httpClient->post('http://localhost/create', [
            'form_params' => [
                'label' => md5('label content'),
                'callback-url' => 'http://example.com/callback',
                'maximum-duration-in-seconds' => 600,
            ],
        ]);

        self::assertSame(200, $response->getStatusCode());

        $this->assertJobStatus([
            'label' => md5('label content'),
            'callback_url' => 'http://example.com/callback',
            'maximum_duration_in_seconds' => 600,
            'sources' => [],
            'compilation_state' => 'awaiting',
            'execution_state' => 'awaiting',
            'tests' => [],
        ]);
    }

    /**
     * @depends testCreateJob
     */
    public function testAddSources(): void
    {
        $manifestKey = new UploadedFileKey(AddSourcesRequest::KEY_MANIFEST);

        $this->httpClient->post('http://localhost/add-sources', [
            'multipart' => [
                [
                    'name' => $manifestKey->encode(),
                    'contents' => file_get_contents(
                        getcwd() . '/tests/Fixtures/Manifest/manifest.txt'
                    ),
                    'filename' => 'manifest.txt'
                ],
                $this->createFileUploadData('Test/chrome-open-index.yml'),
                $this->createFileUploadData('Test/chrome-firefox-open-index.yml'),
                $this->createFileUploadData('Test/chrome-open-form.yml'),
                $this->createFileUploadData('Page/index.yml'),
            ],
        ]);

        $this->assertJobStatus([
            'label' => md5('label content'),
            'callback_url' => 'http://example.com/callback',
            'maximum_duration_in_seconds' => 600,
            'sources' => [
                'Test/chrome-open-index.yml',
                'Test/chrome-firefox-open-index.yml',
                'Test/chrome-open-form.yml',
                'Page/index.yml',
            ],
            'compilation_state' => 'running',
            'execution_state' => 'awaiting',
            'tests' => [],
        ]);
    }

    /**
     * @depends testAddSources
     */
    public function testCompilationExecution(): void
    {
        $duration = 0;
        $durationExceeded = false;

        while (
            false === $durationExceeded &&
            false === $this->waitForApplicationState(CompilationState::STATE_COMPLETE, ExecutionState::STATE_COMPLETE)
        ) {
            usleep(self::WAIT_INTERVAL);
            $duration += self::WAIT_INTERVAL;
            $durationExceeded = $duration >= self::WAIT_TIMEOUT;
        }

        $this->assertJobStatus([
            'label' => md5('label content'),
            'callback_url' => 'http://example.com/callback',
            'maximum_duration_in_seconds' => 600,
            'sources' => [
                'Test/chrome-open-index.yml',
                'Test/chrome-firefox-open-index.yml',
                'Test/chrome-open-form.yml',
                'Page/index.yml',
            ],
            'compilation_state' => 'complete',
            'execution_state' => 'complete',
            'tests' => [
                [
                    'configuration' => [
                        'browser' => 'chrome',
                        'url' => 'http://nginx-html/index.html',
                    ],
                    'source' => 'Test/chrome-open-index.yml',
                    'step_count' => 1,
                    'state' => 'complete',
                    'position' => 1,
                ],
                [
                    'configuration' => [
                        'browser' => 'chrome',
                        'url' => 'http://nginx-html/index.html',
                    ],
                    'source' => 'Test/chrome-firefox-open-index.yml',
                    'step_count' => 1,
                    'state' => 'complete',
                    'position' => 2,
                ],
                [
                    'configuration' => [
                        'browser' => 'firefox',
                        'url' => 'http://nginx-html/index.html',
                    ],
                    'source' => 'Test/chrome-firefox-open-index.yml',
                    'step_count' => 1,
                    'state' => 'complete',
                    'position' => 3,
                ],
                [
                    'configuration' => [
                        'browser' => 'chrome',
                        'url' => 'http://nginx-html/form.html',
                    ],
                    'source' => 'Test/chrome-open-form.yml',
                    'step_count' => 1,
                    'state' => 'complete',
                    'position' => 4,
                ],
            ],
        ]);
    }

    /**
     * @param array<mixed> $expectedJobData
     */
    private function assertJobStatus(array $expectedJobData): void
    {
        $this->assertJobProperties(
            $expectedJobData['label'],
            $expectedJobData['callback_url'],
            $expectedJobData['maximum_duration_in_seconds']
        );

        $this->assertJobSources($expectedJobData['sources']);
        $this->assertJobState($expectedJobData['compilation_state'], $expectedJobData['execution_state']);
        $this->assertTests($expectedJobData['tests']);
    }

    private function assertJobProperties(
        string $expectedLabel,
        string $expectedCallbackUrl,
        int $expectedDurationInSeconds
    ): void {
        $jobStatus = $this->getJsonResponse('http://localhost/status');

        self::assertSame($expectedLabel, $jobStatus['label']);
        self::assertSame($expectedCallbackUrl, $jobStatus['callback_url']);
        self::assertSame($expectedDurationInSeconds, $jobStatus['maximum_duration_in_seconds']);
    }

    /**
     * @param string[] $expectedSources
     */
    private function assertJobSources(array $expectedSources): void
    {
        $jobStatus = $this->getJsonResponse('http://localhost/status');

        self::assertSame($expectedSources, $jobStatus['sources']);
    }

    /**
     * @param CompilationState::STATE_* $expectedCompilationState
     * @param ExecutionState::STATE_* $expectedExecutionState
     */
    private function assertJobState(string $expectedCompilationState, string $expectedExecutionState): void
    {
        $jobStatus = $this->getJsonResponse('http://localhost/status');

        self::assertSame($expectedCompilationState, $jobStatus['compilation_state']);
        self::assertSame($expectedExecutionState, $jobStatus['execution_state']);
    }

    /**
     * @param array<mixed> $expectedTests
     */
    private function assertTests(array $expectedTests): void
    {
        $jobStatus = $this->getJsonResponse('http://localhost/status');
        $tests = $jobStatus['tests'];
        self::assertIsArray($tests);

        self::assertCount(count($expectedTests), $tests);

        foreach ($expectedTests as $index => $expectedTest) {
            self::assertArrayHasKey($index, $tests);
            $actualTest = $tests[$index];
            self::assertIsArray($actualTest);

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

        self::assertSame($expectedSource, $actual['source']);
        self::assertArrayHasKey('target', $actual);
        self::assertMatchesRegularExpression('/^Generated[0-9a-f]{32}Test\.php$/', $actual['target']);
        self::assertSame($expectedStepCount, $actual['step_count']);
        self::assertSame($expectedState, $actual['state']);
        self::assertSame($expectedPosition, $actual['position']);
    }

    /**
     * @param array<mixed> $actual
     */
    private function assertTestConfiguration(string $expectedBrowser, string $expectedUrl, array $actual): void
    {
        self::assertSame($expectedBrowser, $actual['browser']);
        self::assertSame($expectedUrl, $actual['url']);
    }

    /**
     * @return array<mixed>
     */
    private function getJsonResponse(string $url): array
    {
        $response = $this->httpClient->sendRequest(new Request('GET', $url));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        $body = $response->getBody()->getContents();

        return json_decode($body, true);
    }

    /**
     * @return array<string, string>
     */
    private function createFileUploadData(string $path): array
    {
        return [
            'name' => base64_encode($path),
            'contents' => (string) file_get_contents(getcwd() . '/tests/Fixtures/Basil/' . $path),
            'filename' => $path
        ];
    }

    /**
     * @param CompilationState::STATE_* $compilationState
     * @param ExecutionState::STATE_* $executionState
     */
    private function waitForApplicationState(string $compilationState, string $executionState): bool
    {
        $jobStatus = $this->getJsonResponse('http://localhost/status');

        return $compilationState === $jobStatus['compilation_state'] &&
            $executionState === $jobStatus['execution_state'];
    }
}
