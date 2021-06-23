<?php

declare(strict_types=1);

namespace App;

use PHPUnit\Framework\TestCase;

class CallbackReceiverLogTest extends TestCase
{
    private const JOB_LABEL = 'job-label-content';

    /**
     * @var array<array<mixed>>
     */
    private static array $logSections = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$logSections = self::extractLogSections((string) stream_get_contents(STDIN));
    }

    public function testLogSize(): void
    {
        self::assertCount(10, self::$logSections);
    }

    /**
     * @dataProvider logBodyDataProvider
     *
     * @param array<mixed> $expectedLogSectionBodies
     */
    public function testLogBody(array $expectedLogSectionBodies): void
    {
        $logSectionBodyDataCollection = [];
        foreach (self::$logSections as $logSection) {
            $logSectionBodyDataCollection[] = $this->decodeLogSectionBody($logSection);
        }

        self::assertCount(count(self::$logSections), $expectedLogSectionBodies);

        foreach ($expectedLogSectionBodies as $expectedLogSection) {
            self::assertContains($expectedLogSection, $logSectionBodyDataCollection);
        }
    }

    /**
     * @return array<mixed>
     */
    public function logBodyDataProvider(): array
    {
        return [
            'default' => [
                'expectedLogSectionBodies' => [
                    [
                        'label' => self::JOB_LABEL,
                        'type' => 'job/started',
                        'payload' => [],
                    ],
                    [
                        'label' => self::JOB_LABEL,
                        'type' => 'compilation/started',
                        'payload' => [
                            'source' => 'test.yml',
                        ],
                    ],
                    [
                        'label' => self::JOB_LABEL,
                        'type' => 'compilation/passed',
                        'payload' => [
                            'source' => 'test.yml',
                        ],
                    ],
                    [
                        'label' => self::JOB_LABEL,
                        'type' => 'compilation/completed',
                        'payload' => [],
                    ],
                    [
                        'label' => self::JOB_LABEL,
                        'type' => 'execution/started',
                        'payload' => [],
                    ],
                    [
                        'label' => self::JOB_LABEL,
                        'type' => 'test/started',
                        'payload' => [
                            'type' => 'test',
                            'path' => 'test.yml',
                            'config' => [
                                'browser' => 'chrome',
                                'url' => 'http://http-fixtures',
                            ],
                        ],
                    ],
                    [
                        'label' => self::JOB_LABEL,
                        'type' => 'step/passed',
                        'payload' => [
                            'type' => 'step',
                            'name' => 'verify page is open',
                            'status' => 'passed',
                            'statements' => [
                                [
                                    'type' => 'assertion',
                                    'source' => '$page.url is "http://http-fixtures/"',
                                    'status' => 'passed',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label' => self::JOB_LABEL,
                        'type' => 'test/passed',
                        'payload' => [
                            'type' => 'test',
                            'path' => 'test.yml',
                            'config' => [
                                'browser' => 'chrome',
                                'url' => 'http://http-fixtures',
                            ],
                        ],
                    ],
                    [
                        'label' => self::JOB_LABEL,
                        'type' => 'execution/completed',
                        'payload' => [],
                    ],
                    [
                        'label' => self::JOB_LABEL,
                        'type' => 'job/completed',
                        'payload' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<mixed> $logSection
     *
     * @return array<mixed>
     */
    private function decodeLogSectionBody(array $logSection): array
    {
        $bodyContent = $logSection['body'];
        $bodyData = json_decode($bodyContent, true);

        if (!is_array($bodyData)) {
            $bodyData = [];
        }

        return $bodyData;
    }

    /**
     * @return array<array<mixed>>
     */
    private static function extractLogSections(string $raw): array
    {
        $result = [];
        $sections = explode('-----------------', $raw);
        $sections = array_filter($sections);

        foreach ($sections as $section) {
            $sectionJson = self::getJsonFromLogSection($section);
            $sectionData = json_decode($sectionJson, true);

            if (!is_array($sectionData)) {
                $sectionData = [];
            }

            $result[] = $sectionData;
        }

        return $result;
    }

    private static function getJsonFromLogSection(string $section): string
    {
        $lines = explode("\n", trim($section));
        array_pop($lines);

        return implode("\n", $lines);
    }
}
