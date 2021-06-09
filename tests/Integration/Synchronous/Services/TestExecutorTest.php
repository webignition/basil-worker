<?php

declare(strict_types=1);

namespace App\Tests\Integration\Synchronous\Services;

use App\Event\TestStepPassedEvent;
use App\Services\Compiler;
use App\Services\TestExecutor;
use App\Services\TestFactory;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\ObjectReflector\ObjectReflector;
use webignition\TcpCliProxyClient\Client;
use webignition\YamlDocument\Document;

class TestExecutorTest extends AbstractBaseIntegrationTest
{
    private const COMPILER_SOURCE_DIRECTORY = '/app/source';
    private const COMPILER_TARGET_DIRECTORY = '/app/tests';

    private TestExecutor $testExecutor;
    private Compiler $compiler;
    private TestFactory $testFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $testExecutor = self::$container->get(TestExecutor::class);
        \assert($testExecutor instanceof TestExecutor);
        $this->testExecutor = $testExecutor;

        $compiler = self::$container->get(Compiler::class);
        \assert($compiler instanceof Compiler);
        $this->compiler = $compiler;

        $testFactory = self::$container->get(TestFactory::class);
        \assert($testFactory instanceof TestFactory);
        $this->testFactory = $testFactory;

        ObjectReflector::setProperty(
            $compiler,
            Compiler::class,
            'compilerSourceDirectory',
            self::COMPILER_SOURCE_DIRECTORY
        );

        ObjectReflector::setProperty(
            $compiler,
            Compiler::class,
            'compilerTargetDirectory',
            self::COMPILER_TARGET_DIRECTORY
        );

        $this->entityRemover->removeAll();
    }

    protected function tearDown(): void
    {
        $this->entityRemover->removeAll();

        $compilerClient = self::$container->get('app.services.compiler-client');
        self::assertInstanceOf(Client::class, $compilerClient);

        $request = 'rm ' . self::COMPILER_TARGET_DIRECTORY . '/*.php';
        $compilerClient->request($request);

        parent::tearDown();
    }

    /**
     * @dataProvider executeSuccessDataProvider
     */
    public function testExecute(string $source, ExpectedDispatchedEventCollection $expectedDispatchedEvents): void
    {
        /** @var SuiteManifest $suiteManifest */
        $suiteManifest = $this->compiler->compile($source);
        self::assertInstanceOf(SuiteManifest::class, $suiteManifest);

        $tests = $this->testFactory->createFromManifestCollection($suiteManifest->getTestManifests());

        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls($expectedDispatchedEvents)
            ->getMock()
        ;

        ObjectReflector::setProperty(
            $this->testExecutor,
            TestExecutor::class,
            'eventDispatcher',
            $eventDispatcher
        );

        foreach ($tests as $test) {
            $this->testExecutor->execute($test);
        }
    }

    /**
     * @return array[]
     */
    public function executeSuccessDataProvider(): array
    {
        return [
            'Test/chrome-open-index.yml: single-browser test (chrome)' => [
                'source' => 'Test/chrome-open-index.yml',
                'expectedDispatchedEventCollection' => new ExpectedDispatchedEventCollection([
                    new ExpectedDispatchedEvent(
                        function (Event $event): bool {
                            self::assertInstanceOf(TestStepPassedEvent::class, $event);

                            $expectedDocument = new Document(
                                'type: step' . "\n" .
                                'name: \'verify page is open\'' . "\n" .
                                'status: passed' . "\n" .
                                'statements:' . "\n" .
                                '  -' . "\n" .
                                '    type: assertion' . "\n" .
                                '    source: \'$page.url is "http://nginx-html/index.html"\'' . "\n" .
                                '    status: passed' . "\n" .
                                '    transformations:' . "\n" .
                                '      -' . "\n" .
                                '        type: resolution' . "\n" .
                                '        source: \'$page.url is $index.url\'' . "\n"
                            );

                            if ($event instanceof TestStepPassedEvent) {
                                self::assertEquals($expectedDocument, $event->getDocument());
                            }

                            return true;
                        }
                    ),
                ]),
            ],
            'Test/chrome-open-index.yml: single-browser test (firefox)' => [
                'source' => 'Test/firefox-open-index.yml',
                'expectedDispatchedEventCollection' => new ExpectedDispatchedEventCollection([
                    new ExpectedDispatchedEvent(
                        function (Event $event): bool {
                            self::assertInstanceOf(TestStepPassedEvent::class, $event);

                            $expectedDocument = new Document(
                                'type: step' . "\n" .
                                'name: \'verify page is open\'' . "\n" .
                                'status: passed' . "\n" .
                                'statements:' . "\n" .
                                '  -' . "\n" .
                                '    type: assertion' . "\n" .
                                '    source: \'$page.url is "http://nginx-html/index.html"\'' . "\n" .
                                '    status: passed' . "\n"
                            );

                            if ($event instanceof TestStepPassedEvent) {
                                self::assertEquals($event->getDocument(), $expectedDocument);
                            }

                            return true;
                        }
                    ),
                ]),
            ],
            'Test/chrome-firefox-open-index.yml: multi-browser test' => [
                'source' => 'Test/chrome-firefox-open-index.yml',
                'expectedDispatchedEventCollection' => new ExpectedDispatchedEventCollection([
                    new ExpectedDispatchedEvent(
                        function (Event $event): bool {
                            self::assertInstanceOf(TestStepPassedEvent::class, $event);

                            $expectedDocument = new Document(
                                'type: step' . "\n" .
                                'name: \'verify page is open\'' . "\n" .
                                'status: passed' . "\n" .
                                'statements:' . "\n" .
                                '  -' . "\n" .
                                '    type: assertion' . "\n" .
                                '    source: \'$page.url is "http://nginx-html/index.html"\'' . "\n" .
                                '    status: passed' . "\n"
                            );

                            if ($event instanceof TestStepPassedEvent) {
                                self::assertEquals($event->getDocument(), $expectedDocument);
                            }

                            return true;
                        }
                    ),
                    new ExpectedDispatchedEvent(
                        function (Event $event): bool {
                            self::assertInstanceOf(TestStepPassedEvent::class, $event);

                            $expectedDocument = new Document(
                                'type: step' . "\n" .
                                'name: \'verify page is open\'' . "\n" .
                                'status: passed' . "\n" .
                                'statements:' . "\n" .
                                '  -' . "\n" .
                                '    type: assertion' . "\n" .
                                '    source: \'$page.url is "http://nginx-html/index.html"\'' . "\n" .
                                '    status: passed' . "\n"
                            );

                            if ($event instanceof TestStepPassedEvent) {
                                self::assertEquals($event->getDocument(), $expectedDocument);
                            }

                            return true;
                        }
                    ),
                ]),
            ],
        ];
    }
}
