<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;

class YamlResourceLoader
{
    /**
     * @var string
     */
    private string $resourcePath;

    /**
     * @var mixed
     */
    private $data = null;

    public function __construct(string $resourcePath)
    {
        $this->resourcePath = $resourcePath;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        if (empty($this->data)) {
            $this->data = Yaml::parseFile($this->resourcePath);
        }

        return $this->data;
    }
}
