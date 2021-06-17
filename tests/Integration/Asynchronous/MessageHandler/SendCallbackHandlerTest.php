<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous\MessageHandler;

use App\Message\SendCallbackMessage;
use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Model\EnvironmentSetup;
use App\Tests\Model\JobSetup;
use App\Tests\Services\CallableInvoker;
use App\Tests\Services\EntityRetriever;
use App\Tests\Services\EnvironmentFactory;
use App\Tests\Services\Integration\HttpLogReader;
use App\Tests\Services\IntegrationJobProperties;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\EntityPersister;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\CallbackRepository;

class SendCallbackHandlerTest extends AbstractBaseIntegrationTest
{
    private EntityPersister $entityPersister;
    private MessageBusInterface $messageBus;
    private CallableInvoker $callableInvoker;
    private EntityRetriever $entityRetriever;

    protected function setUp(): void
    {
        parent::setUp();

        $entityPersister = self::$container->get(EntityPersister::class);
        \assert($entityPersister instanceof EntityPersister);
        $this->entityPersister = $entityPersister;

        $messageBus = self::$container->get(MessageBusInterface::class);
        \assert($messageBus instanceof MessageBusInterface);
        $this->messageBus = $messageBus;

        $callableInvoker = self::$container->get(CallableInvoker::class);
        \assert($callableInvoker instanceof CallableInvoker);
        $this->callableInvoker = $callableInvoker;

        $entityRetriever = self::$container->get(EntityRetriever::class);
        \assert($entityRetriever instanceof EntityRetriever);
        $this->entityRetriever = $entityRetriever;

        $this->entityRemover->removeAll();
    }

    protected function tearDown(): void
    {
        $this->entityRemover->removeAll();

        parent::tearDown();
    }

    /**
     * @dataProvider sendDataProvider
     */
    public function testSend(
        callable $setup,
        CallbackInterface $callback,
        callable $assertions
    ): void {
        $this->callableInvoker->invoke($setup);

        $callback->setState(CallbackInterface::STATE_SENDING);
        $this->entityPersister->persist($callback->getEntity());

        $this->messageBus->dispatch(new SendCallbackMessage((int) $callback->getId()));

        $intervalInMicroseconds = 100000;
        while (false === $this->isCallbackFinished()) {
            usleep($intervalInMicroseconds);
        }

        $this->callableInvoker->invoke($assertions);
    }

    /**
     * @return array[]
     */
    public function sendDataProvider(): array
    {
        return [
            'success' => [
                'setup' => function (EnvironmentFactory $environmentFactory, IntegrationJobProperties $jobProperties) {
                    $environmentFactory->create(
                        (new EnvironmentSetup())
                            ->withJobSetup(
                                (new JobSetup())->withCallbackUrl($jobProperties->getCallbackUrl())
                            )
                    );
                },
                'callback' => CallbackEntity::create(CallbackInterface::TYPE_JOB_TIME_OUT, [
                    'maximum_duration_in_seconds' => 600,
                ]),
                'assertions' => function (CallbackRepository $callbackRepository) {
                    $callbacks = $callbackRepository->findAll();
                    self::assertCount(1, $callbacks);

                    /** @var CallbackInterface $callback */
                    $callback = $callbacks[0];
                    self::assertInstanceOf(CallbackInterface::class, $callback);

                    self::assertSame(CallbackInterface::STATE_COMPLETE, $callback->getState());
                },
            ],
            'verify retried http transactions are delayed' => [
                'setup' => function (
                    EnvironmentFactory $environmentFactory,
                    HttpLogReader $httpLogReader,
                    IntegrationJobProperties $jobProperties
                ) {
                    $environmentFactory->create(
                        (new EnvironmentSetup())
                            ->withJobSetup(
                                (new JobSetup())->withCallbackUrl($jobProperties->createCallbackUrlForStatusCode(500))
                            )
                    );

                    $httpLogReader->reset();
                },
                'callback' => CallbackEntity::create(CallbackInterface::TYPE_JOB_TIME_OUT, [
                    'maximum_duration_in_seconds' => 600,
                ]),
                'assertions' => function (HttpLogReader $httpLogReader) {
                    $httpTransactions = $httpLogReader->getTransactions();

                    self::assertCount(4, $httpTransactions);

                    $transactionPeriods = $httpTransactions->getPeriods()->getPeriodsInMicroseconds();
                    self::assertCount(4, $transactionPeriods);

                    $retriedTransactionPeriods = $transactionPeriods;
                    array_shift($retriedTransactionPeriods);

                    $backoffStrategy = new ExponentialBackoffStrategy();
                    foreach ($retriedTransactionPeriods as $retryIndex => $retriedTransactionPeriod) {
                        $retryCount = $retryIndex + 1;
                        $expectedLowerThreshold = $backoffStrategy->getDelay($retryCount) * 1000;
                        $expectedUpperThreshold = $backoffStrategy->getDelay($retryCount + 1) * 1000;

                        self::assertGreaterThanOrEqual($expectedLowerThreshold, $retriedTransactionPeriod);
                        self::assertLessThan($expectedUpperThreshold, $retriedTransactionPeriod);
                    }
                },
            ],
        ];
    }

    private function isCallbackFinished(): bool
    {
        $callback = $this->entityRetriever->first(CallbackEntity::class);
        \assert($callback instanceof CallbackEntity);

        return in_array($callback->getState(), [
            CallbackInterface::STATE_COMPLETE,
            CallbackInterface::STATE_FAILED,
        ]);
    }
}
