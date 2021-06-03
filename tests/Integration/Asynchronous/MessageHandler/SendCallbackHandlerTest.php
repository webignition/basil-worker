<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous\MessageHandler;

use App\Message\SendCallbackMessage;
use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Model\EnvironmentSetup;
use App\Tests\Model\JobSetup;
use App\Tests\Services\CallableInvoker;
use App\Tests\Services\EntityRefresher;
use App\Tests\Services\EnvironmentFactory;
use App\Tests\Services\Integration\HttpLogReader;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\EntityPersister;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\CallbackRepository;

class SendCallbackHandlerTest extends AbstractBaseIntegrationTest
{
    private EnvironmentFactory $environmentFactory;
    private EntityPersister $entityPersister;
    private HttpLogReader $httpLogReader;
    private MessageBusInterface $messageBus;
    private CallableInvoker $callableInvoker;

    protected function setUp(): void
    {
        parent::setUp();

        $environmentFactory = self::$container->get(EnvironmentFactory::class);
        \assert($environmentFactory instanceof EnvironmentFactory);
        $this->environmentFactory = $environmentFactory;

        $entityPersister = self::$container->get(EntityPersister::class);
        \assert($entityPersister instanceof EntityPersister);
        $this->entityPersister = $entityPersister;

        $httpLogReader = self::$container->get(HttpLogReader::class);
        \assert($httpLogReader instanceof HttpLogReader);
        $this->httpLogReader = $httpLogReader;

        $messageBus = self::$container->get(MessageBusInterface::class);
        \assert($messageBus instanceof MessageBusInterface);
        $this->messageBus = $messageBus;

        $callableInvoker = self::$container->get(CallableInvoker::class);
        \assert($callableInvoker instanceof CallableInvoker);
        $this->callableInvoker = $callableInvoker;

        $this->httpLogReader->reset();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->httpLogReader->reset();
    }

    /**
     * @dataProvider sendDataProvider
     */
    public function testSend(
        EnvironmentSetup $setup,
        CallbackInterface $callback,
        callable $waitUntil,
        callable $assertions
    ): void {
        $this->environmentFactory->create($setup);

        $callback->setState(CallbackInterface::STATE_SENDING);
        $this->entityPersister->persist($callback->getEntity());

        $this->messageBus->dispatch(new SendCallbackMessage((int) $callback->getId()));

        $intervalInMicroseconds = 100000;
        while (false === $this->callableInvoker->invoke($waitUntil)) {
            usleep($intervalInMicroseconds);
        }

        $this->callableInvoker->invoke($assertions);
    }

    /**
     * @return array[]
     */
    public function sendDataProvider(): array
    {
        $callbackBaseUrl = $_ENV['CALLBACK_BASE_URL'] ?? '';

        return [
            'success' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(
                        (new JobSetup())->withCallbackUrl($callbackBaseUrl . '/status/200')
                    ),
                'callback' => CallbackEntity::create(CallbackInterface::TYPE_JOB_TIME_OUT, [
                    'maximum_duration_in_seconds' => 600,
                ]),
                'waitUntil' => $this->createWaitUntilCallbackIsFinished(),
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
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(
                        (new JobSetup())->withCallbackUrl($callbackBaseUrl . '/status/500')
                    ),
                'callback' => CallbackEntity::create(CallbackInterface::TYPE_JOB_TIME_OUT, [
                    'maximum_duration_in_seconds' => 600,
                ]),
                'waitUntil' => $this->createWaitUntilCallbackIsFinished(),
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

    private function createWaitUntilCallbackIsFinished(): callable
    {
        return function (EntityRefresher $entityRefresher, CallbackRepository $callbackRepository) {
            $entityRefresher->refreshForEntities([
                CallbackEntity::class,
            ]);

            $callbacks = $callbackRepository->findAll();

            /** @var CallbackInterface $callback */
            $callback = $callbacks[0];

            return in_array($callback->getState(), [
                CallbackInterface::STATE_COMPLETE,
                CallbackInterface::STATE_FAILED,
            ]);
        };
    }
}
