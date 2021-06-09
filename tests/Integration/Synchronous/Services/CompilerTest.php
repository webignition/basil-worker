<?php

declare(strict_types=1);

namespace App\Tests\Integration\Synchronous\Services;

use App\Services\Compiler;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use webignition\BasilCompilerModels\ErrorOutput;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\ObjectReflector\ObjectReflector;
use webignition\TcpCliProxyClient\Client;

class CompilerTest extends AbstractBaseIntegrationTest
{
    private const COMPILER_SOURCE_DIRECTORY = '/app/source';
    private const COMPILER_TARGET_DIRECTORY = '/app/tests';

    private Compiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();

        $compiler = self::$container->get(Compiler::class);
        \assert($compiler instanceof Compiler);
        $this->compiler = $compiler;

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
     * @dataProvider compileSuccessDataProvider
     *
     * @param array<mixed> $expectedSuiteManifestData
     */
    public function testCompileSuccess(string $source, array $expectedSuiteManifestData): void
    {
        /** @var SuiteManifest $suiteManifest */
        $suiteManifest = $this->compiler->compile($source);

        self::assertInstanceOf(SuiteManifest::class, $suiteManifest);

        $expectedSuiteManifestData = $this->replaceSuiteManifestDataPlaceholders($expectedSuiteManifestData);
        $expectedSuiteManifest = SuiteManifest::fromArray($expectedSuiteManifestData);

        self::assertEquals($expectedSuiteManifest, $suiteManifest);
    }

    /**
     * @return array[]
     */
    public function compileSuccessDataProvider(): array
    {
        $sourcePath = self::COMPILER_SOURCE_DIRECTORY;
        $targetPath = self::COMPILER_TARGET_DIRECTORY;

        return [
            'Test/chrome-open-index.yml: single-browser test' => [
                'source' => 'Test/chrome-open-index.yml',
                'expectedSuiteManifestData' => [
                    'config' => [
                        'source' => $sourcePath . '/Test/chrome-open-index.yml',
                        'target' => $targetPath,
                        'base-class' => 'webignition\BaseBasilTestCase\AbstractBaseTest',
                    ],
                    'manifests' => [
                        [
                            'config' => [
                                'browser' => 'chrome',
                                'url' => 'http://nginx-html/index.html'
                            ],
                            'source' => $sourcePath . '/Test/chrome-open-index.yml',
                            'target' => $targetPath . '/Generated2380721d052389cf928f39ac198a41baTest.php',
                            'step_count' => 1,
                        ],
                    ],
                ],
            ],
            'Test/chrome-firefox-open-index.yml: multiple-browser test' => [
                'source' => 'Test/chrome-firefox-open-index.yml',
                'expectedSuiteManifestData' => [
                    'config' => [
                        'source' => $sourcePath . '/Test/chrome-firefox-open-index.yml',
                        'target' => $targetPath,
                        'base-class' => 'webignition\BaseBasilTestCase\AbstractBaseTest',
                    ],
                    'manifests' => [
                        [
                            'config' => [
                                'browser' => 'chrome',
                                'url' => 'http://nginx-html/index.html'
                            ],
                            'source' => $sourcePath . '/Test/chrome-firefox-open-index.yml',
                            'target' => $targetPath . '/Generated45ead8003cb8ba3fa966dc1ad5a91372Test.php',
                            'step_count' => 1,
                        ],
                        [
                            'config' => [
                                'browser' => 'firefox',
                                'url' => 'http://nginx-html/index.html'
                            ],
                            'source' => $sourcePath . '/Test/chrome-firefox-open-index.yml',
                            'target' => $targetPath . '/Generated88b4291e887760b0fe2eec8891356665Test.php',
                            'step_count' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider compileFailureDataProvider
     *
     * @param array<mixed> $expectedErrorOutputData
     */
    public function testCompileFailure(string $source, array $expectedErrorOutputData): void
    {
        /** @var ErrorOutput $errorOutput */
        $errorOutput = $this->compiler->compile($source);

        self::assertInstanceOf(ErrorOutput::class, $errorOutput);

        $expectedErrorOutputData = $this->replaceSuiteManifestDataPlaceholders($expectedErrorOutputData);

        $expectedErrorOutput = ErrorOutput::fromArray($expectedErrorOutputData);

        self::assertEquals($expectedErrorOutput, $errorOutput);
    }

    /**
     * @return array[]
     */
    public function compileFailureDataProvider(): array
    {
        $sourcePath = self::COMPILER_SOURCE_DIRECTORY;
        $targetPath = self::COMPILER_TARGET_DIRECTORY;

        return [
            'unparseable assertion' => [
                'source' => 'InvalidTest/invalid-unparseable-assertion.yml',
                'expectedErrorOutputData' => [
                    'config' => [
                        'source' => $sourcePath . '/InvalidTest/invalid-unparseable-assertion.yml',
                        'target' => $targetPath,
                        'base-class' => 'webignition\BaseBasilTestCase\AbstractBaseTest',
                    ],
                    'error' => [
                        'code' => 206,
                        'message' => 'Unparseable test',
                        'context' => [
                            'type' => 'test',
                            'test_path' => $sourcePath . '/InvalidTest/invalid-unparseable-assertion.yml',
                            'step_name' => 'verify page is open',
                            'reason' => 'empty-value',
                            'statement_type' => 'assertion',
                            'statement' => '$page.url is',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private function replaceSuiteManifestDataPlaceholders(array $data): array
    {
        $compilerSourceDirectory = self::$container->getParameter('compiler_source_directory');
        $compilerTargetDirectory = self::$container->getParameter('compiler_target_directory');

        $placeholders = [
            'COMPILER_SOURCE_DIRECTORY' => $compilerSourceDirectory,
            'COMPILER_TARGET_DIRECTORY' => $compilerTargetDirectory,
        ];

        $search = [];
        $replace = [];

        foreach ($placeholders as $key => $value) {
            $search[] = '{{ ' . $key . ' }}';
            $replace[] = $value;
        }

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = str_replace($search, $replace, $value);
            }

            if (is_array($value)) {
                $data[$key] = $this->replaceSuiteManifestDataPlaceholders($value);
            }
        }

        return $data;
    }
}
