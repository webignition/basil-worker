<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class ApplicationTest extends TestCase
{
    private const CALLBACK_WEB_SERVER_PORT = 8080;
    private const CALLBACK_WEB_SERVER_LOG_PATH = 'web-server.log';
    private const JOB_LABEL = 'job-label-content';
    private const JOB_MAXIMUM_DURATION_IN_SECONDS = 600;

    private static Client $httpClient;
    private static OutputInterface $output;
    private static CallbackWebServer $callbackWebServer;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$httpClient = new Client();
        self::$output = new StreamOutput(STDOUT);

        self::$callbackWebServer = new CallbackWebServer(
            self::CALLBACK_WEB_SERVER_PORT,
            self::CALLBACK_WEB_SERVER_LOG_PATH,
            self::$output
        );
        self::$callbackWebServer->start();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        self::$callbackWebServer->stop();
    }

    public function testCreateJob()
    {
        $createJobResponse = self::$httpClient->post('http://localhost/create', [
            'form_params' => [
                'label' => self::JOB_LABEL,
                'callback-url' => self::$callbackWebServer->getUrl(),
                'maximum-duration-in-seconds' => self::JOB_MAXIMUM_DURATION_IN_SECONDS,
            ],
        ]);
        self::assertSame(200, $createJobResponse->getStatusCode());

        $this->assertJobStatus([
            'label' => self::JOB_LABEL,
            'callback_url' => self::$callbackWebServer->getUrl(),
            'maximum_duration_in_seconds' => self::JOB_MAXIMUM_DURATION_IN_SECONDS,
            'sources' => [],
            'compilation_state' => 'awaiting',
            'execution_state' => 'awaiting',
            'tests' => [],
        ]);
    }

    /**
     * @param array<mixed> $expectedJobData
     */
    private function assertJobStatus(array $expectedJobData): void
    {
        $response = self::$httpClient->get('http://localhost/status');
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            $expectedJobData,
            json_decode($response->getBody()->getContents(), true)
        );
    }
}
