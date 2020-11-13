<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\CallbackEntityInterface;
use App\Entity\CompileFailureCallback;
use App\Entity\ExecuteDocumentReceivedCallback;
use App\Repository\CallbackRepository;
use App\Services\CallbackStore;
use App\Tests\AbstractBaseFunctionalTest;
use Symfony\Component\Yaml\Yaml;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class CallbackRepositoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackRepository $repository;
    private CallbackStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testFindCompileFailureCallback()
    {
        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $errorOutput
            ->shouldReceive('getData')
            ->andReturn([]);

        $callback = CompileFailureCallback::create($errorOutput);

        $this->doFindEntity($callback);
    }

    public function testFindExecuteDocumentReceivedCallback()
    {
        $document = new Document(Yaml::dump([]));
        $callback = ExecuteDocumentReceivedCallback::create($document);

        $this->doFindEntity($callback);
    }

    private function doFindEntity(CallbackEntityInterface $callback): void
    {
        self::assertNull($this->repository->find((int) $callback->getId()));

        $this->store->store($callback);

        $retrievedCallback = $this->repository->find($callback->getId());
        self::assertEquals($callback, $retrievedCallback);
    }
}
