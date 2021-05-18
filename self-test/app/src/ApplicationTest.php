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
        self::$fixturePath = realpath(getcwd() . '/../fixtures');
    }

    public function testCreateJob()
    {
        $createJobResponse = self::$httpClient->post('http://localhost/create', [
            'form_params' => [
                'label' => self::JOB_LABEL,
                'callback-url' => self::CALLBACK_URL,
                'maximum-duration-in-seconds' => self::JOB_MAXIMUM_DURATION_IN_SECONDS,
            ],
        ]);
        self::assertSame(200, $createJobResponse->getStatusCode());
        self::assertSame('application/json', $createJobResponse->getHeaderLine('content-type'));
        self::assertSame(
            [
                'label' => self::JOB_LABEL,
                'callback_url' => self::CALLBACK_URL,
                'maximum_duration_in_seconds' => self::JOB_MAXIMUM_DURATION_IN_SECONDS,
                'sources' => [],
                'compilation_state' => 'awaiting',
                'execution_state' => 'awaiting',
                'tests' => [],
            ],
            $this->getJobStatus()
        );
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
        self::assertSame(
            [
                'label' => self::JOB_LABEL,
                'callback_url' => self::CALLBACK_URL,
                'maximum_duration_in_seconds' => self::JOB_MAXIMUM_DURATION_IN_SECONDS,
                'sources' => [
                    'test.yml',
                ],
                'compilation_state' => 'running',
                'execution_state' => 'awaiting',
                'tests' => [],
            ],
            $this->getJobStatus()
        );
    }

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
}
