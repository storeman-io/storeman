<?php

namespace Storeman\Cli;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLogger extends \Symfony\Component\Console\Logger\ConsoleLogger
{
    public function __construct(OutputInterface $output)
    {
        $formatLevelMap = [
            LogLevel::NOTICE => 'fg=default;bg=default',
            LogLevel::INFO => 'fg=white',
            LogLevel::DEBUG => 'fg=white',
        ];

        parent::__construct($output, [], $formatLevelMap);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        $message = "[{now}] {$message}";
        $context += ['now' => new \DateTime()];

        parent::log($level, $message, $context);
    }
}
