<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\TestExecuteDocumentReceivedEvent;
use App\Services\Compiler;
use App\Services\TestExecutor;
use App\Services\TestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\ObjectReflector\ObjectReflector;
use webignition\TcpCliProxyClient\Client;
use webignition\YamlDocument\Document;

class TestExecutorTest extends AbstractBaseFunctionalTest
{
    private TestExecutor $testExecutor;
    private Compiler $compiler;
    private TestStore $testStore;

    protected function setUp(): void
    {
        parent::setUp();

        $testExecutor = self::$container->get(TestExecutor::class);
        self::assertInstanceOf(TestExecutor::class, $testExecutor);
        if ($testExecutor instanceof TestExecutor) {
            $this->testExecutor = $testExecutor;
        }

        $compiler = self::$container->get(Compiler::class);
        self::assertInstanceOf(Compiler::class, $compiler);
        if ($compiler instanceof Compiler) {
            $this->compiler = $compiler;
        }

        $testStore = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $testStore);
        if ($testStore instanceof TestStore) {
            $this->testStore = $testStore;
        }
    }

    /**
     * @dataProvider executeSuccessDataProvider
     */
    public function testExecute(string $source, EventDispatcherInterface $eventDispatcher)
    {
        /** @var SuiteManifest $suiteManifest */
        $suiteManifest = $this->compiler->compile($source);
        self::assertInstanceOf(SuiteManifest::class, $suiteManifest);

        ObjectReflector::setProperty(
            $this->testExecutor,
            TestExecutor::class,
            'eventDispatcher',
            $eventDispatcher
        );

        foreach ($suiteManifest->getTestManifests() as $testManifest) {
            $test = $this->testStore->createFromTestManifest($testManifest);
            $this->testExecutor->execute($test);
        }
    }

    public function executeSuccessDataProvider(): array
    {
        $expectedVerifyPageIsOpenStepDispatchedEvent = new ExpectedDispatchedEvent(
            new TestExecuteDocumentReceivedEvent(
                new Document(
                    'type: step' . "\n" .
                    'name: \'verify page is open\'' . "\n" .
                    'status: passed' . "\n" .
                    'statements:' . "\n" .
                    '  -' . "\n" .
                    '    type: assertion' . "\n" .
                    '    source: \'$page.url is "http://nginx/index.html"\'' . "\n" .
                    '    status: passed' . "\n"
                )
            ),
            TestExecuteDocumentReceivedEvent::NAME
        );

        return [
            'Test/chrome-open-index.yml: single-browser test (chrome)' => [
                'source' => 'Test/chrome-open-index.yml',
                'eventDispatcher' => (new MockEventDispatcher())
                    ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                        new ExpectedDispatchedEvent(
                            new TestExecuteDocumentReceivedEvent(
                                new Document(
                                    '---' . "\n" .
                                    'type: test' . "\n" .
                                    'path: /app/source/Test/chrome-open-index.yml' . "\n" .
                                    'config:' . "\n" .
                                    '  browser: chrome' . "\n" .
                                    '  url: \'http://nginx/index.html\'' . "\n" .
                                    '...' . "\n"
                                )
                            ),
                            TestExecuteDocumentReceivedEvent::NAME
                        ),
                        $expectedVerifyPageIsOpenStepDispatchedEvent,
                    ]))
                    ->getMock(),
            ],
            'Test/chrome-open-index.yml: single-browser test (firefox)' => [
                'source' => 'Test/firefox-open-index.yml',
                'eventDispatcher' => (new MockEventDispatcher())
                    ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                        new ExpectedDispatchedEvent(
                            new TestExecuteDocumentReceivedEvent(
                                new Document(
                                    '---' . "\n" .
                                    'type: test' . "\n" .
                                    'path: /app/source/Test/firefox-open-index.yml' . "\n" .
                                    'config:' . "\n" .
                                    '  browser: firefox' . "\n" .
                                    '  url: \'http://nginx/index.html\'' . "\n" .
                                    '...' . "\n"
                                )
                            ),
                            TestExecuteDocumentReceivedEvent::NAME
                        ),
                        $expectedVerifyPageIsOpenStepDispatchedEvent,
                    ]))
                    ->getMock(),
            ],
            'Test/chrome-firefox-open-index.yml: multi-browser test' => [
                'source' => 'Test/chrome-firefox-open-index.yml',
                'eventDispatcher' => (new MockEventDispatcher())
                    ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                        new ExpectedDispatchedEvent(
                            new TestExecuteDocumentReceivedEvent(
                                new Document(
                                    '---' . "\n" .
                                    'type: test' . "\n" .
                                    'path: /app/source/Test/chrome-firefox-open-index.yml' . "\n" .
                                    'config:' . "\n" .
                                    '  browser: chrome' . "\n" .
                                    '  url: \'http://nginx/index.html\'' . "\n" .
                                    '...' . "\n"
                                )
                            ),
                            TestExecuteDocumentReceivedEvent::NAME
                        ),
                        $expectedVerifyPageIsOpenStepDispatchedEvent,
                        new ExpectedDispatchedEvent(
                            new TestExecuteDocumentReceivedEvent(
                                new Document(
                                    '---' . "\n" .
                                    'type: test' . "\n" .
                                    'path: /app/source/Test/chrome-firefox-open-index.yml' . "\n" .
                                    'config:' . "\n" .
                                    '  browser: firefox' . "\n" .
                                    '  url: \'http://nginx/index.html\'' . "\n" .
                                    '...' . "\n"
                                )
                            ),
                            TestExecuteDocumentReceivedEvent::NAME
                        ),
                        $expectedVerifyPageIsOpenStepDispatchedEvent,
                    ]))
                    ->getMock(),
            ],
        ];
    }

    protected function tearDown(): void
    {
        $compilerClient = self::$container->get('app.services.compiler-client');
        self::assertInstanceOf(Client::class, $compilerClient);

        $compilerTargetDirectory = self::$container->getParameter('compiler_target_directory');

        $request = 'rm ' . $compilerTargetDirectory . '/*.php';
        $compilerClient->request($request);

        parent::tearDown();
    }
}
