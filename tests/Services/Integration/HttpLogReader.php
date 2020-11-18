<?php

declare(strict_types=1);

namespace App\Tests\Services\Integration;

use webignition\HttpHistoryContainer\Collection\HttpTransactionCollection;
use webignition\HttpHistoryContainer\Transaction\LoggableTransaction;

class HttpLogReader
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getTransactions(): HttpTransactionCollection
    {
        $content = (string) file_get_contents($this->path);
        $lines = array_filter(explode("\n", $content));

        $transactions = new HttpTransactionCollection();

        foreach ($lines as $line) {
            $loggedTransaction = LoggableTransaction::fromJson($line);
            $transactions->add($loggedTransaction);
        }

        return $transactions;
    }

    public function reset(): void
    {
        unlink($this->path);
    }
}
