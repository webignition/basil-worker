<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Job;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    public function testCreate()
    {
        $label = md5('label source');
        $callbackUrl = 'http://example.com/callback';

        $job = Job::create($label, $callbackUrl);

        self::assertSame(1, $job->getId());
        self::assertSame($label, $job->getLabel());
        self::assertSame($callbackUrl, $job->getCallbackUrl());
        self::assertSame([], $job->getSources());
    }

    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param Job $job
     * @param array<mixed> $expectedSerializedJob
     */
    public function testJsonSerialize(Job $job, array $expectedSerializedJob)
    {
        self::assertSame($expectedSerializedJob, $job->jsonSerialize());
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'state compilation-awaiting, no sources' => [
                'job' => Job::create('label content', 'http://example.com/callback'),
                'expectedSerializedJob' => [
                    'label' => 'label content',
                    'callback_url' => 'http://example.com/callback',
                    'sources' => [],
                ],
            ],
            'state compilation-awaiting, has sources' => [
                'job' => $this->createJobWithSources(
                    Job::create('label content', 'http://example.com/callback'),
                    [
                        'Test/test1.yml',
                        'Test/test2.yml',
                        'Test/test3.yml',
                    ]
                ),
                'expectedSerializedJob' => [
                    'label' => 'label content',
                    'callback_url' => 'http://example.com/callback',
                    'sources' => [
                        'Test/test1.yml',
                        'Test/test2.yml',
                        'Test/test3.yml',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param Job $job
     * @param string[] $sources
     *
     * @return Job
     */
    private function createJobWithSources(Job $job, array $sources): Job
    {
        $job->setSources($sources);

        return $job;
    }
}
