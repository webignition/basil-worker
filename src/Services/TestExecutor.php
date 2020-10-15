<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Event\TestExecuteDocumentReceivedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use webignition\TcpCliProxyClient\Client;
use webignition\TcpCliProxyClient\Handler;
use webignition\YamlDocument\Document;

class TestExecutor
{
    private Client $delegatorClient;
    private EventDispatcherInterface $eventDispatcher;
    private YamlDocumentFactory $yamlDocumentFactory;

    public function __construct(
        Client $delegatorClient,
        YamlDocumentFactory $yamlDocumentFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->delegatorClient = $delegatorClient;
        $this->yamlDocumentFactory = $yamlDocumentFactory;
        $this->eventDispatcher = $eventDispatcher;
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

        $this->yamlDocumentFactory->setOnDocumentCreated(function (Document $document) {
            $this->eventDispatcher->dispatch(
                new TestExecuteDocumentReceivedEvent($document),
                TestExecuteDocumentReceivedEvent::NAME
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
