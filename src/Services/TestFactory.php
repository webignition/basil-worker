<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\SourceCompilation\PassedEvent;
use App\Services\EntityFactory\TestFactory as TestEntityFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use webignition\BasilCompilerModels\TestManifest;

class TestFactory implements EventSubscriberInterface
{
    public function __construct(private TestEntityFactory $testEntityFactory)
    {
    }

    /**
     * @return array<mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PassedEvent::class => [
                ['createFromSourceCompileSuccessEvent', 100],
            ],
        ];
    }

    /**
     * @return Test[]
     */
    public function createFromSourceCompileSuccessEvent(PassedEvent $event): array
    {
        $suiteManifest = $event->getOutput();

        return $this->createFromManifestCollection($suiteManifest->getTestManifests());
    }

    /**
     * @param TestManifest[] $manifests
     *
     * @return Test[]
     */
    public function createFromManifestCollection(array $manifests): array
    {
        $tests = [];

        foreach ($manifests as $manifest) {
            if ($manifest instanceof TestManifest) {
                $tests[] = $this->createFromManifest($manifest);
            }
        }

        return $tests;
    }

    private function createFromManifest(TestManifest $manifest): Test
    {
        $manifestConfiguration = $manifest->getConfiguration();

        return $this->testEntityFactory->create(
            TestConfiguration::create(
                $manifestConfiguration->getBrowser(),
                $manifestConfiguration->getUrl()
            ),
            $manifest->getSource(),
            $manifest->getTarget(),
            $manifest->getStepCount()
        );
    }
}
