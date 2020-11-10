<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Model\RunnerTest\TestProxy;
use Psr\EventDispatcher\EventDispatcherInterface;
use webignition\TcpCliProxyClient\Client;
use webignition\TcpCliProxyClient\Handler;
use webignition\YamlDocument\Document;
use webignition\YamlDocumentGenerator\YamlGenerator;

class TestExecutor
{
    private Client $delegatorClient;
    private EventDispatcherInterface $eventDispatcher;
    private YamlDocumentFactory $yamlDocumentFactory;
    private YamlGenerator $yamlGenerator;
    private TestDocumentMutator $testDocumentMutator;

    public function __construct(
        Client $delegatorClient,
        YamlDocumentFactory $yamlDocumentFactory,
        EventDispatcherInterface $eventDispatcher,
        YamlGenerator $yamlGenerator,
        TestDocumentMutator $testDocumentMutator
    ) {
        $this->delegatorClient = $delegatorClient;
        $this->yamlDocumentFactory = $yamlDocumentFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->yamlGenerator = $yamlGenerator;
        $this->testDocumentMutator = $testDocumentMutator;
    }

    public function execute(Test $test): void
    {
        $delegatorClientHandler = new Handler();
        $delegatorClientHandler
            ->addCallback(function (string $buffer) {
                if (false === ctype_digit($buffer) && '' !== trim($buffer)) {
                    $this->yamlDocumentFactory->process($buffer);
                }
            });

        $runnerTest = new TestProxy($test);
        $runnerTestString = $this->yamlGenerator->generate($runnerTest);

        $this->eventDispatcher->dispatch(
            new TestExecuteDocumentReceivedEvent(
                $test,
                $this->testDocumentMutator->removeCompilerSourceDirectoryFromSource(new Document($runnerTestString))
            )
        );

        $this->yamlDocumentFactory->setOnDocumentCreated(function (Document $document) use ($test) {
            $this->eventDispatcher->dispatch(
                new TestExecuteDocumentReceivedEvent($test, $document)
            );
        });

        $this->yamlDocumentFactory->start();

        $this->delegatorClient->request(
            sprintf(
                './bin/delegator --browser %s %s',
                $test->getConfiguration()->getBrowser(),
                $test->getTarget()
            ),
            $delegatorClientHandler
        );

        $this->yamlDocumentFactory->stop();
    }
}
