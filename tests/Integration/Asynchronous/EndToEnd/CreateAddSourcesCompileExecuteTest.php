<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous\EndToEnd;

use App\Entity\Test;
use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use App\Model\JobState;
use App\Repository\TestRepository;
use App\Tests\Integration\AbstractEndToEndTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\JobConfiguration;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\Integration\HttpLogReader;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CreateAddSourcesCompileExecuteTest extends AbstractEndToEndTest
{
    use TestClassServicePropertyInjectorTrait;

    private TestRepository $testRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider createAddSourcesCompileExecuteDataProvider
     *
     * @param JobConfiguration $jobConfiguration
     * @param string[] $expectedSourcePaths
     * @param JobState::STATE_* $expectedJobEndState
     * @param array<Test::STATE_*> $expectedTestEndStates
     */
    public function testCreateAddSourcesCompileExecute(
        JobConfiguration $jobConfiguration,
        array $expectedSourcePaths,
        string $expectedJobEndState,
        array $expectedTestEndStates,
        Invokable $assertions
    ) {
        $expectedTestEndStatesAssertions = new Invokable(
            function (array $expectedTestEndStates) {
                $tests = $this->testRepository->findAll();
                self::assertCount(count($expectedTestEndStates), $tests);

                foreach ($tests as $testIndex => $test) {
                    $expectedTestEndState = $expectedTestEndStates[$testIndex] ?? null;
                    self::assertSame($expectedTestEndState, $test->getState());
                }
            },
            [
                $expectedTestEndStates,
            ]
        );

        $this->doCreateJobAddSourcesTest(
            $jobConfiguration,
            $expectedSourcePaths,
            $expectedJobEndState,
            new InvokableCollection([
                $expectedTestEndStatesAssertions,
                $assertions
            ])
        );
    }

    public function createAddSourcesCompileExecuteDataProvider(): array
    {
        return [
            'default' => [
                'jobConfiguration' => new JobConfiguration(
                    md5('label content'),
                    'http://200.example.com/callback/1',
                    getcwd() . '/tests/Fixtures/Manifest/manifest.txt'
                ),
                'expectedSourcePaths' => [
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'expectedJobEndState' => JobState::STATE_EXECUTION_COMPLETE,
                'expectedTestEndStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                ],
                'assertions' => Invokable::createEmpty(),
            ],
            'verify retried transactions are delayed' => [
                'jobConfiguration' => new JobConfiguration(
                    md5('label content'),
                    'http://200.500.500.200.example.com/callback/2',
                    getcwd() . '/tests/Fixtures/Manifest/manifest-chrome-open-index.txt'
                ),
                'expectedSourcePaths' => [
                    'Test/chrome-open-index.yml',
                ],
                'expectedJobEndState' => JobState::STATE_EXECUTION_COMPLETE,
                'expectedTestEndStates' => [
                    Test::STATE_COMPLETE,
                ],
                'assertions' => new Invokable(
                    function (HttpLogReader $httpLogReader) {
                        $httpTransactions = $httpLogReader->getTransactions();
                        $httpLogReader->reset();

                        self::assertCount(4, $httpTransactions);

                        $transactionPeriods = $httpTransactions->getPeriods()->getPeriodsInMicroseconds();
                        array_shift($transactionPeriods);

                        self::assertCount(3, $transactionPeriods);

                        $firstStepTransactionPeriod = array_shift($transactionPeriods);
                        $retriedTransactionPeriods = [];
                        foreach ($transactionPeriods as $transactionPeriod) {
                            $retriedTransactionPeriods[] = $transactionPeriod - $firstStepTransactionPeriod;
                        }

                        $backoffStrategy = new ExponentialBackoffStrategy();
                        foreach ($retriedTransactionPeriods as $retryIndex => $retriedTransactionPeriod) {
                            $retryCount = $retryIndex + 1;
                            $expectedLowerThreshold = $backoffStrategy->getDelay($retryCount) * 1000;
                            $expectedUpperThreshold = $backoffStrategy->getDelay($retryCount + 1) * 1000;

                            self::assertGreaterThanOrEqual($expectedLowerThreshold, $retriedTransactionPeriod);
                            self::assertLessThan($expectedUpperThreshold, $retriedTransactionPeriod);
                        }
                    },
                    [
                        new ServiceReference(HttpLogReader::class),
                    ]
                ),
            ],
        ];
    }
}
