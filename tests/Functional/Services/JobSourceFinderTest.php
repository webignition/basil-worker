<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\JobSourceFinder;
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

class JobSourceFinderTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private JobSourceFinder $jobSourceFinder;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider findNextNonCompiledSourceDataProvider
     */
    public function testFindNextNonCompiledSource(InvokableInterface $setup, ?string $expectedNextNonCompiledSource)
    {
        $this->invokableHandler->invoke($setup);

        self::assertSame($expectedNextNonCompiledSource, $this->jobSourceFinder->findNextNonCompiledSource());
    }

    public function findNextNonCompiledSourceDataProvider(): array
    {
        $sources = [
            'Test/testZebra.yml',
            'Test/testApple.yml',
            'Test/testBat.yml',
        ];

        return [
            'no job' => [
                'setup' => Invokable::createEmpty(),
                'expectedNextNonCompiledSource' => null,
            ],
            'has job, no sources' => [
                'setup' => JobSetupInvokableFactory::setup(
                    new JobSetup()
                ),
                'expectedNextNonCompiledSource' => null,
            ],
            'has job, has sources, no tests' => [
                'setup' => JobSetupInvokableFactory::setup(
                    (new JobSetup())
                        ->withSources($sources)
                ),
                'expectedNextNonCompiledSource' => $sources[0],
            ],
            'test exists for first source' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources($sources)
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[0]),
                    ]),
                ]),
                'expectedNextNonCompiledSource' => $sources[1],
            ],
            'test exists for first and second sources' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources($sources)
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[0]),
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[1]),
                    ]),
                ]),
                'expectedNextNonCompiledSource' => $sources[2],
            ],
            'tests exist for all sources' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources($sources)
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[0]),
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[1]),
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[2]),
                    ]),
                ]),
                'expectedNextNonCompiledSource' => null,
            ],
        ];
    }

    /**
     * @dataProvider findCompiledSourcesDataProvider
     *
     * @param InvokableInterface $setup
     * @param string[] $expectedCompiledSources
     */
    public function testFindCompiledSources(InvokableInterface $setup, array $expectedCompiledSources)
    {
        $this->invokableHandler->invoke($setup);

        self::assertSame($expectedCompiledSources, $this->jobSourceFinder->findCompiledSources());
    }

    public function findCompiledSourcesDataProvider(): array
    {
        $sources = [
            'Test/testZebra.yml',
            'Test/testApple.yml',
            'Test/testBat.yml',
        ];

        return [
            'no job' => [
                'setup' => Invokable::createEmpty(),
                'expectedCompiledSources' => [],
            ],
            'has job, no sources' => [
                'setup' => JobSetupInvokableFactory::setup(
                    new JobSetup()
                ),
                'expectedCompiledSources' => [],
            ],
            'has job, has sources, no tests' => [
                'setup' => JobSetupInvokableFactory::setup(
                    (new JobSetup())
                        ->withSources($sources)
                ),
                'expectedCompiledSources' => [],
            ],
            'test exists for first source' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources($sources)
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[0]),
                    ]),
                ]),
                'expectedCompiledSources' => [
                    'Test/testZebra.yml',
                ],
            ],
            'test exists for first and second sources' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources($sources)
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[0]),
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[1]),
                    ]),
                ]),
                'expectedCompiledSources' => [
                    'Test/testZebra.yml',
                    'Test/testApple.yml',
                ],
            ],
            'tests exist for all sources' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources($sources)
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[0]),
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[1]),
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[2]),
                    ]),
                ]),
                'expectedCompiledSources' => [
                    'Test/testZebra.yml',
                    'Test/testApple.yml',
                    'Test/testBat.yml',
                ],
            ],
        ];
    }
}
