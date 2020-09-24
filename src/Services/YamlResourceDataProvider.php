<?php

declare(strict_types=1);

namespace App\Services;

class YamlResourceDataProvider extends YamlResourceLoader implements DataProviderInterface
{
    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        $data = parent::getData();

        return is_array($data) ? $data : [];
    }
}
