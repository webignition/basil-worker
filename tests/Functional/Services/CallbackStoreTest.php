<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;
use App\Entity\Callback\CompileFailureCallback;
use App\Entity\Callback\ExecuteDocumentReceivedCallback;
use App\Services\CallbackStore;
use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class CallbackStoreTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackStore $callbackStore;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider storeDataProvider
     */
    public function testStore(CallbackInterface $callback)
    {
        $callbackRepository = $this->entityManager->getRepository(CallbackEntity::class);
        self::assertCount(0, $callbackRepository->findAll());

        self::assertNull($callback->getId());

        $this->callbackStore->store($callback);
        self::assertNotNull($callback->getId());

        $retrievedCallback = $callbackRepository->find($callback->getId());
        self::assertEquals($callback->getEntity(), $retrievedCallback);
    }

    public function storeDataProvider(): array
    {
        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $errorOutput
            ->shouldReceive('getData')
            ->andReturn([
                'errorOutputKey1' => 'errorOutputValue1',
                'errorOutputKey2' => 'errorOutputValue2',
            ]);

        $document = new Document(Yaml::dump([
            'documentKey1' => 'documentValue1',
            'documentKey2' => 'documentValue2',
        ]));

        return [
            CallbackEntity::class => [
                'callback' => CallbackEntity::create(
                    CallbackInterface::TYPE_COMPILE_FAILURE,
                    [
                        'callbackEntityKey1' => 'callbackEntityValue1',
                        'callbackEntityKey2' => 'callbackEntityValue2',
                    ]
                ),
            ],
            CompileFailureCallback::class => [
                'callback' => new CompileFailureCallback($errorOutput),
            ],
            ExecuteDocumentReceivedCallback::class => [
                'callback' => new ExecuteDocumentReceivedCallback($document),
            ],
        ];
    }
}
