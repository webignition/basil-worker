<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Request\AddSourcesRequest;
use App\Request\JobCreateRequest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class ClientRequestSender
{
    private KernelBrowser $client;
    private BasilFixtureHandler $basilFixtureHandler;

    public function __construct(KernelBrowser $client, BasilFixtureHandler $basilFixtureHandler)
    {
        $this->client = $client;
        $this->basilFixtureHandler = $basilFixtureHandler;
    }

    public function createJob(string $label, string $callbackUrl): Response
    {
        $this->client->request('POST', '/create', [
            JobCreateRequest::KEY_LABEL => $label,
            JobCreateRequest::KEY_CALLBACK_URL => $callbackUrl,
        ]);

        return $this->client->getResponse();
    }

    /**
     * @param UploadedFile[] $sourceUploadedFiles
     *
     * @return Response
     */
    public function addJobSources(UploadedFile $manifest, array $sourceUploadedFiles): Response
    {
        $requestFiles = array_merge(
            [
                AddSourcesRequest::KEY_MANIFEST => $manifest,
            ],
            $sourceUploadedFiles
        );

        $this->client->request(
            'POST',
            '/add-sources',
            [],
            $requestFiles
        );

        return $this->client->getResponse();
    }

    public function foo(KernelBrowser $client, UploadedFile $manifest, array $requestSources): Response
    {
        $requestFiles = array_merge(
            [
                AddSourcesRequest::KEY_MANIFEST => $manifest,
            ],
            $requestSources
        );

        $client->request(
            'POST',
            '/add-sources',
            [],
            $requestFiles
        );

        return $client->getResponse();
    }
}
