<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CallbackWebServer
{
    private int $port;
    private string $logPath;
    private OutputInterface $output;
    private Process $process;

    public function __construct(int $port, string $logPath, OutputInterface $output)
    {
        $this->port = $port;
        $this->logPath = $logPath;
        $this->output = $output;
        $this->process = Process::fromShellCommandline($this->createCommand());
    }

    public function start(): void
    {
        $this->process->start();
        $this->checkStatus(Process::STATUS_STARTED);
    }

    public function stop(): void
    {
        $this->process->stop();
        shell_exec('sudo pkill -f "' . $this->createNcCommand() . '"');
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        $this->checkStatus(Process::STATUS_TERMINATED);
    }

    public function getUrl(): string
    {
        return 'http://localhost:' . $this->port . '/callback';
    }

    private function createCommand(): string
    {
        return sprintf(
            'while true; do printf "HTTP/1.1 200 OK\r\n\r\n" | %s; done',
            $this->createNcLoggingCommand()
        );
    }

    private function createNcCommand(): string
    {
        return sprintf(
            'nc -l -N %d',
            $this->port
        );
    }

    private function createNcLoggingCommand(): string
    {
        return sprintf(
            '%s >> %s',
            $this->createNcCommand(),
            $this->logPath
        );
    }

    /**
     * @param Process::STATE_* $expectedStatus
     */
    private function checkStatus(string $expectedStatus): void
    {
        $decoratedStatus = strtolower($expectedStatus);
        $status = $this->process->getStatus();

        if ($expectedStatus === $status) {
            $this->output->writeln('Callback web server ' . $decoratedStatus . '.');
        } else {
            $this->output->writeln('Callback web server process not ' . $decoratedStatus . '. Status: ' . $status);
        }
    }
}
