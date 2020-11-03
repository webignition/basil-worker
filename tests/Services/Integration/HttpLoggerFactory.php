<?php

declare(strict_types=1);

namespace App\Tests\Services\Integration;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class HttpLoggerFactory
{
    public function create(string $path): LoggerInterface
    {
        $logger = new Logger('');
        $logHandler = new StreamHandler($path);
        $logHandler
            ->setFormatter(new LineFormatter('%message%' . "\n"));

        $logger->pushHandler($logHandler);

        return $logger;
    }
}
