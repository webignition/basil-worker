<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Event\SourceCompileSuccessEvent;
use App\EventSubscriber\SourceCompileSuccessEventSubscriber;
use App\Message\CompileSource;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use webignition\BasilCompilerModels\ConfigurationInterface;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration;

class SourceCompileSuccessEventSubscriberTest extends AbstractBaseFunctionalTest
{
    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        if ($jobStore instanceof JobStore) {
            $jobStore->create('label content', 'http://example.com/callback');
            $this->jobStore = $jobStore;
        }
    }

    public function testGetSubscribedEvents()
    {
        self::assertSame(
            [
                SourceCompileSuccessEvent::NAME => [
                    ['createTests', 10],
                    ['dispatchNextCompileSourceMessage', 0],
                ],
            ],
            SourceCompileSuccessEventSubscriber::getSubscribedEvents()
        );
    }

    public function testIntegration()
    {
        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);

        $testStore = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $testStore);

        $job = $this->jobStore->getJob();
        $job->setSources([
            'Test/test1.yml',
            'Test/test2.yml',
        ]);
        $this->jobStore->store($job);

        $source = 'Test/test1.yml';

        $manifest1 = new TestManifest(
            new Configuration('chrome', 'http://example.com'),
            '/app/source/Test/test1.yml',
            '/app/tests/GeneratedChromeTest.php',
            2
        );

        $manifest2 = new TestManifest(
            new Configuration('firefox', 'http://example.com'),
            '/app/source/Test/test1.yml',
            '/app/tests/GeneratedFirefoxTest.php',
            2
        );

        $suiteManifest = new SuiteManifest(
            \Mockery::mock(ConfigurationInterface::class),
            [
                $manifest1,
                $manifest2,
            ]
        );

        self::assertCount(0, $testStore->findAll());

        $eventDispatcher->dispatch(
            new SourceCompileSuccessEvent($source, $suiteManifest),
            SourceCompileSuccessEvent::NAME
        );

        self::assertCount(2, $testStore->findAll());

        $expectedQueuedMessage = new CompileSource('Test/test2.yml');

        $messengerTransport = self::$container->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $messengerTransport);

        $queue = $messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        /** @var Envelope $envelope */
        $envelope = $queue[0] ?? null;

        self::assertEquals($expectedQueuedMessage, $envelope->getMessage());
    }
}
