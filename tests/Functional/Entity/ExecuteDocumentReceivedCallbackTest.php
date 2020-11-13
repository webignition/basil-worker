<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\CallbackEntityInterface;
use App\Entity\ExecuteDocumentReceivedCallback;
use Symfony\Component\Yaml\Yaml;
use webignition\YamlDocument\Document;

class ExecuteDocumentReceivedCallbackTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $payload = [
            'key1' => 'value1',
            'key2' => [
                'key2key1' => 'key2 value1',
                'key2key2' => 'key2 value2',
            ],
        ];

        $document = new Document(Yaml::dump($payload));
        $callback = ExecuteDocumentReceivedCallback::create($document);

        self::assertNull($callback->getId());
        self::assertSame(CallbackEntityInterface::STATE_AWAITING, $callback->getState());
        self::assertSame(0, $callback->getRetryCount());
        self::assertSame(CallbackEntityInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED, $callback->getType());
        self::assertSame($payload, $callback->getPayload());

        $this->entityManager->persist($callback);
        $this->entityManager->flush();
        self::assertIsInt($callback->getId());
    }
}
