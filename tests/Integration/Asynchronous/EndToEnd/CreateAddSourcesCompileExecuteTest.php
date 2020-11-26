<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous\EndToEnd;

use App\Entity\Test;
use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use App\Services\CompilationState;
use App\Services\ExecutionState;
use App\Tests\Integration\AbstractEndToEndTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\JobConfiguration;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\Integration\HttpLogReader;
use App\Tests\Services\InvokableFactory\TestGetterFactory;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CreateAddSourcesCompileExecuteTest extends AbstractEndToEndTest
{
    use TestClassServicePropertyInjectorTrait;

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
     * @param CompilationState::STATE_* $expectedCompilationEndState
     * @param ExecutionState::STATE_* $expectedExecutionEndState
     */
    public function testCreateAddSourcesCompileExecute(
        JobConfiguration $jobConfiguration,
        array $expectedSourcePaths,
        string $expectedCompilationEndState,
        string $expectedExecutionEndState,
        InvokableInterface $assertions
    ) {
        $this->doCreateJobAddSourcesTest(
            $jobConfiguration,
            $expectedSourcePaths,
            $expectedCompilationEndState,
            $expectedExecutionEndState,
            $assertions
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
                'expectedCompilationEndState' => CompilationState::STATE_COMPLETE,
                'expectedExecutionEndState' => ExecutionState::STATE_COMPLETE,
                'assertions' => TestGetterFactory::assertStates([
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                ]),
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
                'expectedCompilationEndState' => CompilationState::STATE_COMPLETE,
                'expectedExecutionEndState' => ExecutionState::STATE_COMPLETE,
                'assertions' => new InvokableCollection([
                    'verify test end states' => TestGetterFactory::assertStates([
                        Test::STATE_COMPLETE,
                    ]),
                    'verify http transactions' => new Invokable(
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
                    )
                ]),
            ],
        ];
    }
}
