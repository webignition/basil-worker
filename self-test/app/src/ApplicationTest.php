<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    private const JOB_LABEL = 'job-label-content';
    private const JOB_MAXIMUM_DURATION_IN_SECONDS = 600;

    private const CALLBACK_URL = 'http://callback-receiver:8080/';

    private static Client $httpClient;
    private static string $fixturePath;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$httpClient = new Client();
        self::$fixturePath = (string) realpath(getcwd() . '/../fixtures');
    }

    public function testCreateJob(): void
    {
        $createJobResponse = self::$httpClient->post('http://localhost/job', [
            'form_params' => [
                'label' => self::JOB_LABEL,
                'callback-url' => self::CALLBACK_URL,
                'maximum-duration-in-seconds' => self::JOB_MAXIMUM_DURATION_IN_SECONDS,
            ],
        ]);
        self::assertSame(200, $createJobResponse->getStatusCode());
        self::assertSame('application/json', $createJobResponse->getHeaderLine('content-type'));
        $this->assertJobStatus([
            'label' => self::JOB_LABEL,
            'callback_url' => self::CALLBACK_URL,
            'maximum_duration_in_seconds' => self::JOB_MAXIMUM_DURATION_IN_SECONDS,
            'sources' => [],
            'compilation_states' => ['awaiting'],
            'execution_states' => ['awaiting'],
            'callback_states' => ['awaiting'],
            'tests' => [],
        ]);
    }

    /**
     * @depends testCreateJob
     */
    public function testAddSources(): void
    {
        $addSourcesResponse = self::$httpClient->post('http://localhost/add-sources', [
            'multipart' => [
                [
                    'name' => base64_encode('manifest'),
                    'contents' => (string) file_get_contents(self::$fixturePath . '/basil/manifest.txt'),
                    'filename' => 'manifest.txt'
                ],
                $this->createFileUploadData('test.yml', [
                    '{{ BROWSER }}' => 'chrome',
                ]),
            ],
        ]);

        self::assertSame(200, $addSourcesResponse->getStatusCode());
        self::assertSame('application/json', $addSourcesResponse->getHeaderLine('content-type'));
        $this->assertJobStatus([
            'label' => self::JOB_LABEL,
            'callback_url' => self::CALLBACK_URL,
            'maximum_duration_in_seconds' => self::JOB_MAXIMUM_DURATION_IN_SECONDS,
            'sources' => [
                'test.yml',
            ],
            'compilation_states' => ['running', 'complete'],
            'execution_states' => ['awaiting'],
            'callback_states' => ['awaiting', 'running'],
            'tests' => [],
        ]);
    }

    /**
     * @return array<mixed>
     */
    private function getJobStatus(): array
    {
        $response = self::$httpClient->get('http://localhost/status');
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<mixed> $replacements
     *
     * @return array<string, string>
     */
    private function createFileUploadData(string $path, array $replacements = []): array
    {
        $contents = (string) file_get_contents(self::$fixturePath . '/basil/' . $path);
        $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);

        return [
            'name' => base64_encode($path),
            'contents' => $contents,
            'filename' => $path
        ];
    }

    /**
     * @param array<mixed> $expectedJobData
     */
    private function assertJobStatus(array $expectedJobData): void
    {
        $job = $this->getJobStatus();

        self::assertSame($expectedJobData['label'], $job['label']);
        self::assertSame($expectedJobData['callback_url'], $job['callback_url']);
        self::assertSame($expectedJobData['maximum_duration_in_seconds'], $job['maximum_duration_in_seconds']);
        self::assertSame($expectedJobData['sources'], $job['sources']);
        self::assertContains($job['compilation_state'], $expectedJobData['compilation_states']);
        self::assertContains($job['execution_state'], $expectedJobData['execution_states']);
        self::assertContains($job['callback_state'], $expectedJobData['callback_states']);
        self::assertSame($job['tests'], $expectedJobData['tests']);
    }
}
