<?php

declare(strict_types=1);

namespace App\Tests\Integration\Services;

use App\Event\TestExecuteDocumentReceivedEvent;
use App\Services\Compiler;
use App\Services\TestExecutor;
use App\Services\TestFactory;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\ObjectReflector\ObjectReflector;
use webignition\TcpCliProxyClient\Client;
use webignition\YamlDocument\Document;

class TestExecutorTest extends AbstractBaseIntegrationTest
{
    private TestExecutor $testExecutor;
    private Compiler $compiler;
    private TestFactory $testFactory;

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

        $testFactory = self::$container->get(TestFactory::class);
        self::assertInstanceOf(TestFactory::class, $testFactory);
        if ($testFactory instanceof TestFactory) {
            $this->testFactory = $testFactory;
        }
    }

    /**
     * @dataProvider executeSuccessDataProvider
     *
     * @param string $source
     * @param array<int, Document[]> $expectedDispatchedEventDocumentsPerTest
     */
    public function testExecute(string $source, array $expectedDispatchedEventDocumentsPerTest)
    {
        /** @var SuiteManifest $suiteManifest */
        $suiteManifest = $this->compiler->compile($source);
        self::assertInstanceOf(SuiteManifest::class, $suiteManifest);

        $tests = $this->testFactory->createFromManifestCollection($suiteManifest->getTestManifests());

        $expectedDispatchedEvents = [];
        foreach ($tests as $testIndex => $test) {
            $expectedDispatchedEventDocuments = $expectedDispatchedEventDocumentsPerTest[$testIndex] ?? [];

            foreach ($expectedDispatchedEventDocuments as $expectedDispatchedEventDocument) {
                $expectedDispatchedEvents[] = new ExpectedDispatchedEvent(
                    new TestExecuteDocumentReceivedEvent(
                        $test,
                        $expectedDispatchedEventDocument
                    ),
                    TestExecuteDocumentReceivedEvent::NAME
                );
            }
        }

        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls(new ExpectedDispatchedEventCollection($expectedDispatchedEvents))
            ->getMock();

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

    public function executeSuccessDataProvider(): array
    {
        return [
            'Test/chrome-open-index.yml: single-browser test (chrome)' => [
                'source' => 'Test/chrome-open-index.yml',
                'expectedDispatchedEventDocumentsPerTest' => [
                    [
                        new Document(
                            '---' . "\n" .
                            'type: test' . "\n" .
                            'path: /app/source/Test/chrome-open-index.yml' . "\n" .
                            'config:' . "\n" .
                            '  browser: chrome' . "\n" .
                            '  url: \'http://nginx/index.html\'' . "\n" .
                            '...' . "\n"
                        ),
                        new Document(
                            'type: step' . "\n" .
                            'name: \'verify page is open\'' . "\n" .
                            'status: passed' . "\n" .
                            'statements:' . "\n" .
                            '  -' . "\n" .
                            '    type: assertion' . "\n" .
                            '    source: \'$page.url is "http://nginx/index.html"\'' . "\n" .
                            '    status: passed' . "\n"
                        ),
                    ],
                ],
            ],
            'Test/chrome-open-index.yml: single-browser test (firefox)' => [
                'source' => 'Test/firefox-open-index.yml',
                'expectedDispatchedEventDocuments' => [
                    [
                        new Document(
                            '---' . "\n" .
                            'type: test' . "\n" .
                            'path: /app/source/Test/firefox-open-index.yml' . "\n" .
                            'config:' . "\n" .
                            '  browser: firefox' . "\n" .
                            '  url: \'http://nginx/index.html\'' . "\n" .
                            '...' . "\n"
                        ),
                        new Document(
                            'type: step' . "\n" .
                            'name: \'verify page is open\'' . "\n" .
                            'status: passed' . "\n" .
                            'statements:' . "\n" .
                            '  -' . "\n" .
                            '    type: assertion' . "\n" .
                            '    source: \'$page.url is "http://nginx/index.html"\'' . "\n" .
                            '    status: passed' . "\n"
                        ),
                    ],
                ],
            ],
            'Test/chrome-firefox-open-index.yml: multi-browser test' => [
                'source' => 'Test/chrome-firefox-open-index.yml',
                'expectedDispatchedEventDocuments' => [
                    [
                        new Document(
                            '---' . "\n" .
                            'type: test' . "\n" .
                            'path: /app/source/Test/chrome-firefox-open-index.yml' . "\n" .
                            'config:' . "\n" .
                            '  browser: chrome' . "\n" .
                            '  url: \'http://nginx/index.html\'' . "\n" .
                            '...' . "\n"
                        ),
                        new Document(
                            'type: step' . "\n" .
                            'name: \'verify page is open\'' . "\n" .
                            'status: passed' . "\n" .
                            'statements:' . "\n" .
                            '  -' . "\n" .
                            '    type: assertion' . "\n" .
                            '    source: \'$page.url is "http://nginx/index.html"\'' . "\n" .
                            '    status: passed' . "\n"
                        ),
                    ],
                    [
                        new Document(
                            '---' . "\n" .
                            'type: test' . "\n" .
                            'path: /app/source/Test/chrome-firefox-open-index.yml' . "\n" .
                            'config:' . "\n" .
                            '  browser: firefox' . "\n" .
                            '  url: \'http://nginx/index.html\'' . "\n" .
                            '...' . "\n"
                        ),
                        new Document(
                            'type: step' . "\n" .
                            'name: \'verify page is open\'' . "\n" .
                            'status: passed' . "\n" .
                            'statements:' . "\n" .
                            '  -' . "\n" .
                            '    type: assertion' . "\n" .
                            '    source: \'$page.url is "http://nginx/index.html"\'' . "\n" .
                            '    status: passed' . "\n"
                        ),
                    ],
                ],
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
