<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\Workflow\CompilationWorkflow;
use App\Services\CompilationWorkflowFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CompilationWorkflowFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CompilationWorkflowFactory $compilationWorkflowFactory;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(InvokableInterface $setup, CompilationWorkflow $expectedWorkflow)
    {
        $this->invokableHandler->invoke($setup);

        self::assertEquals($expectedWorkflow, $this->compilationWorkflowFactory->create());
    }

    public function createDataProvider(): array
    {
        $sources = [
            'Test/testZebra.yml',
            'Test/testApple.yml',
            'Test/testBat.yml',
        ];

        return [
            'no job' => [
                'setup' => Invokable::createEmpty(),
                'expectedWorkflow' => new CompilationWorkflow([], null),
            ],
            'has job, no sources' => [
                'setup' => JobSetupInvokableFactory::setup(new JobSetup()),
                'expectedWorkflow' => new CompilationWorkflow([], null),
            ],
            'has job, has sources, no tests' => [
                'setup' => JobSetupInvokableFactory::setup(
                    (new JobSetup())->withSources($sources)
                ),
                'expectedWorkflow' => new CompilationWorkflow([], $sources[0]),
            ],
            'test exists for first source' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())->withSources($sources)
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())->withSource('/app/source/' . $sources[0]),
                    ]),
                ]),
                'expectedWorkflow' => new CompilationWorkflow(
                    [
                        $sources[0],
                    ],
                    $sources[1]
                ),
            ],
            'test exists for first and second sources' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())->withSources($sources)
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())->withSource('/app/source/' . $sources[0]),
                        (new TestSetup())->withSource('/app/source/' . $sources[1]),
                    ]),
                ]),
                'expectedWorkflow' => new CompilationWorkflow(
                    [
                        $sources[0],
                        $sources[1],
                    ],
                    $sources[2],
                ),
            ],
            'tests exist for all sources' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())->withSources($sources)
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())->withSource('/app/source/' . $sources[0]),
                        (new TestSetup())->withSource('/app/source/' . $sources[1]),
                        (new TestSetup())->withSource('/app/source/' . $sources[2]),
                    ]),
                ]),
                'expectedWorkflow' => new CompilationWorkflow(
                    [
                        $sources[0],
                        $sources[1],
                        $sources[2],
                    ],
                    null
                ),
            ],
        ];
    }
}
