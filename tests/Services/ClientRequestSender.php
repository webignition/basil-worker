<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Request\AddSourcesRequest;
use App\Request\JobCreateRequest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ClientRequestSender
{
    private KernelBrowser $client;

    public function __construct(KernelBrowser $client)
    {
        $this->client = $client;
    }

    public function createJob(string $label, string $callbackUrl): JsonResponse
    {
        $this->client->request('POST', '/create', [
            JobCreateRequest::KEY_LABEL => $label,
            JobCreateRequest::KEY_CALLBACK_URL => $callbackUrl,
        ]);

        /**
         * @var JsonResponse $response
         */
        $response = $this->client->getResponse();

        return $response;
    }

    /**
     * @param UploadedFile[] $sourceUploadedFiles
     *
     * @return Response
     */
    public function addJobSources(UploadedFile $manifest, array $sourceUploadedFiles): JsonResponse
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

        /**
         * @var JsonResponse $response
         */
        $response = $this->client->getResponse();

        return $response;
    }
}
