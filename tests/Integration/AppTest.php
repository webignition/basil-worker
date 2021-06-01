<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Model\UploadedFileKey;
use App\Request\AddSourcesRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    private Client $httpClient;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        exec('composer db-recreate --quiet');
    }

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
     * @param array<mixed> $expectedJobData
     */
    private function assertJobStatus(array $expectedJobData): void
    {
        self::assertSame($expectedJobData, $this->getJsonResponse('http://localhost/status'));
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
}
