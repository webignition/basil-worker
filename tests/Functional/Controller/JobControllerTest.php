<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Job;
use App\Request\JobCreateRequest;
use App\Services\JobStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;

class JobControllerTest extends AbstractBaseFunctionalTest
{
    public function testCreate()
    {
        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        self::assertNull($jobStore->retrieve());

        $label = md5('label content');
        $callbackUrl = 'http://example.com/callback';

        $this->client->request('POST', '/create', [
            JobCreateRequest::KEY_LABEL => $label,
            JobCreateRequest::KEY_CALLBACK_URL => $callbackUrl,
        ]);

        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('content-type'));
        self::assertSame('{}', $response->getContent());

        self::assertNotNull($jobStore->retrieve());
        self::assertEquals(
            Job::create($label, $callbackUrl),
            $jobStore->retrieve()
        );
    }
}
