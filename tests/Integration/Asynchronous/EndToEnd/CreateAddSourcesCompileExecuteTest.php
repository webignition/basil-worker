<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous\EndToEnd;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Test;
use App\Repository\TestRepository;
use App\Services\ApplicationState;
use App\Services\CompilationState;
use App\Services\ExecutionState;
use App\Tests\Integration\AbstractCreateAddSourcesCompileExecuteTest;
use App\Tests\Services\Integration\HttpLogReader;
use Psr\Http\Message\RequestInterface;

class CreateAddSourcesCompileExecuteTest extends AbstractCreateAddSourcesCompileExecuteTest
{
    /**
     * @return array[]
     */
    public function createAddSourcesCompileExecuteDataProvider(): array
    {
        return [
            'default' => [
                'jobMaximumDurationInSeconds' => 99,
                'manifestPath' => getcwd() . '/tests/Fixtures/Manifest/manifest.txt',
                'sourcePaths' => [
                    'Page/index.yml',
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'postAddSources' => null,
                'expectedCompilationEndState' => CompilationState::STATE_COMPLETE,
                'expectedExecutionEndState' => ExecutionState::STATE_COMPLETE,
                'expectedApplicationEndState' => ApplicationState::STATE_COMPLETE,
            ],
            'verify job is timed out' => [
                'jobMaximumDurationInSeconds' => 1,
                'manifestPath' => getcwd() . '/tests/Fixtures/Manifest/manifest.txt',
                'sourcePaths' => [
                    'Page/index.yml',
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'postAddSources' => null,
                'expectedCompilationEndState' => CompilationState::STATE_COMPLETE,
                'expectedExecutionEndState' => ExecutionState::STATE_CANCELLED,
                'expectedApplicationEndState' => ApplicationState::STATE_TIMED_OUT,
                'assertions' => function (TestRepository $testRepository, HttpLogReader $httpLogReader) {
                    // Verify job and test end states
                    $tests = $testRepository->findAll();
                    $hasFoundCancelledTest = false;

                    foreach ($tests as $test) {
                        if (Test::STATE_CANCELLED === $test->getState() && false === $hasFoundCancelledTest) {
                            $hasFoundCancelledTest = true;
                        }

                        if ($hasFoundCancelledTest) {
                            self::assertSame(Test::STATE_CANCELLED, $test->getState());
                        } else {
                            self::assertSame(Test::STATE_COMPLETE, $test->getState());
                        }
                    }

                    self::assertTrue($hasFoundCancelledTest);

                    // Verify final HTTP request
                    // Fixes #676. Wait (0.05 seconds) for the HTTP transaction log to be written to fully.
                    usleep(50000);

                    $httpTransactions = $httpLogReader->getTransactions();
                    $httpLogReader->reset();

                    $lastRequestPayload = [];
                    $lastRequest = $httpTransactions->getRequests()->getLast();
                    if ($lastRequest instanceof RequestInterface) {
                        $lastRequestPayload = json_decode($lastRequest->getBody()->getContents(), true);
                    }

                    self::assertSame(CallbackInterface::TYPE_JOB_TIME_OUT, $lastRequestPayload['type']);
                },
            ],
        ];
    }
}
