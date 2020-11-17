<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous\EndToEnd;

use App\Entity\Job;
use App\Entity\Test;
use App\Repository\TestRepository;
use App\Tests\Integration\AbstractEndToEndTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\JobConfiguration;
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
     * @param Job::STATE_* $expectedJobEndState
     * @param array<Test::STATE_*> $expectedTestEndStates
     */
    public function testCreateAddSourcesCompileExecute(
        JobConfiguration $jobConfiguration,
        array $expectedSourcePaths,
        string $expectedJobEndState,
        array $expectedTestEndStates
    ) {
        $this->doCreateJobAddSourcesTest(
            $jobConfiguration,
            $expectedSourcePaths,
            $expectedJobEndState,
            new Invokable(
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
            )
        );
    }

    public function createAddSourcesCompileExecuteDataProvider(): array
    {
        return [
            'default' => [
                'jobConfiguration' => new JobConfiguration(
                    md5('label content'),
                    'http://200.example.com/callback',
                    getcwd() . '/tests/Fixtures/Manifest/manifest.txt'
                ),
                'expectedSourcePaths' => [
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'expectedJobEndState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedTestEndState' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                ],
            ],
        ];
    }
}
