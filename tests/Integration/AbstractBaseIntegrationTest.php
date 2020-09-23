<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractBaseIntegrationTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    protected $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }
}
