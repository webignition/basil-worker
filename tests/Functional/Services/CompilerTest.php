<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\Compiler;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use webignition\BasilCompilerModels\ErrorOutput;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\TcpCliProxyClient\Client;

class CompilerTest extends AbstractBaseFunctionalTest
{
    private Compiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();

        $compiler = self::$container->get(Compiler::class);
        if ($compiler instanceof Compiler) {
            $this->compiler = $compiler;
        }
    }

    /**
     * @dataProvider compileSuccessDataProvider
     *
     * @param string $source
     * @param array<mixed> $expectedSuiteManifestData
     */
    public function testCompileSuccess(string $source, array $expectedSuiteManifestData)
    {
        /** @var SuiteManifest $suiteManifest */
        $suiteManifest = $this->compiler->compile($source);

        self::assertInstanceOf(SuiteManifest::class, $suiteManifest);

        $expectedSuiteManifestData = $this->replaceSuiteManifestDataPlaceholders($expectedSuiteManifestData);

        $expectedSuiteManifest = SuiteManifest::fromArray($expectedSuiteManifestData);

        self::assertEquals($expectedSuiteManifest, $suiteManifest);
    }

    public function compileSuccessDataProvider(): array
    {
        return [
            'Test/chrome-open-index.yml: single-browser test' => [
                'source' => 'Test/chrome-open-index.yml',
                'expectedSuiteManifestData' => [
                    'config' => [
                        'source' => '{{ COMPILER_SOURCE_DIRECTORY }}/Test/chrome-open-index.yml',
                        'target' => '{{ COMPILER_TARGET_DIRECTORY }}',
                        'base-class' => 'webignition\BaseBasilTestCase\AbstractBaseTest',
                    ],
                    'manifests' => [
                        [
                            'config' => [
                                'browser' => 'chrome',
                                'url' => 'https://nginx/index.html'
                            ],
                            'source' => '{{ COMPILER_SOURCE_DIRECTORY }}/Test/chrome-open-index.yml',
                            'target' =>
                                '{{ COMPILER_TARGET_DIRECTORY }}/Generated20a684f0e0c3561fcf627ccef9cd1d93Test.php',
                            'step_count' => 1,
                        ],
                    ],
                ],
            ],
            'Test/chrome-firefox-open-index.yml: multiple-browser test' => [
                'source' => 'Test/chrome-firefox-open-index.yml',
                'expectedSuiteManifestData' => [
                    'config' => [
                        'source' => '{{ COMPILER_SOURCE_DIRECTORY }}/Test/chrome-firefox-open-index.yml',
                        'target' => '{{ COMPILER_TARGET_DIRECTORY }}',
                        'base-class' => 'webignition\BaseBasilTestCase\AbstractBaseTest',
                    ],
                    'manifests' => [
                        [
                            'config' => [
                                'browser' => 'chrome',
                                'url' => 'http://nginx/index.html'
                            ],
                            'source' => '{{ COMPILER_SOURCE_DIRECTORY }}/Test/chrome-firefox-open-index.yml',
                            'target' =>
                                '{{ COMPILER_TARGET_DIRECTORY }}/GeneratedD4f678300db2a05515c2d7f37bf371e6Test.php',
                            'step_count' => 1,
                        ],
                        [
                            'config' => [
                                'browser' => 'firefox',
                                'url' => 'http://nginx/index.html'
                            ],
                            'source' => '{{ COMPILER_SOURCE_DIRECTORY }}/Test/chrome-firefox-open-index.yml',
                            'target' =>
                                '{{ COMPILER_TARGET_DIRECTORY }}/Generated1acff01f6f61ee1997b72f934fbd3111Test.php',
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
     * @param string $source
     * @param array<mixed> $expectedErrorOutputData
     */
    public function testCompileFailure(string $source, array $expectedErrorOutputData)
    {
        /** @var ErrorOutput $errorOutput */
        $errorOutput = $this->compiler->compile($source);

        self::assertInstanceOf(ErrorOutput::class, $errorOutput);

        $expectedErrorOutputData = $this->replaceSuiteManifestDataPlaceholders($expectedErrorOutputData);

        $expectedErrorOutput = ErrorOutput::fromArray($expectedErrorOutputData);

        self::assertEquals($expectedErrorOutput, $errorOutput);
    }

    public function compileFailureDataProvider(): array
    {
        return [
            'unparseable assertion' => [
                'source' => 'InvalidTest/invalid-unparseable-assertion.yml',
                'expectedErrorOutputData' => [
                    'config' => [
                        'source' => '{{ COMPILER_SOURCE_DIRECTORY }}/InvalidTest/invalid-unparseable-assertion.yml',
                        'target' => '{{ COMPILER_TARGET_DIRECTORY }}',
                        'base-class' => 'webignition\BaseBasilTestCase\AbstractBaseTest',
                    ],
                    'error' => [
                        'code' => 206,
                        'message' => 'Unparseable test',
                        'context' => [
                            'type' => 'test',
                            'test_path' =>
                                '{{ COMPILER_SOURCE_DIRECTORY }}/InvalidTest/invalid-unparseable-assertion.yml',
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
