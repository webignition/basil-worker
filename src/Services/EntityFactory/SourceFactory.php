<?php

declare(strict_types=1);

namespace App\Services\EntityFactory;

use App\Entity\Source;

class SourceFactory extends AbstractEntityFactory
{
    /**
     * @param Source::TYPE_* $type
     */
    public function create(string $type, string $path): Source
    {
        $source = Source::create($type, $path);

        $this->persist($source);

        return $source;
    }
}
