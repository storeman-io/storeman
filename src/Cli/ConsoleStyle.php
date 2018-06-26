<?php

namespace Storeman\Cli;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleStyle extends SymfonyStyle
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        parent::__construct($input, $output);

        $this->input = $input;
        $this->output = $output;

        $this->getFormatter()->setStyle('bold', new OutputFormatterStyle(null, null, ['bold']));
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * Asks the same question multiple times returning the answers as an array.
     *
     * @param string $question
     * @param callable $validator
     * @return array
     */
    public function askMultiple(string $question, callable $validator = null): array
    {
        $answers = [];

        while ($answer = $this->ask($question, null, $validator)) {

            $answers[] = $answer;
        }

        return $answers;
    }
}
