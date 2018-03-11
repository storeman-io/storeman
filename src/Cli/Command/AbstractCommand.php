<?php

namespace Archivr\Cli\Command;

use Archivr\Cli\Style;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Style
     */
    protected $outputStyle;

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->outputStyle = new Style($input, $output);

        $this->registerFormatters();
    }

    /**
     * Registers some custom formatters to the given output.
     */
    protected function registerFormatters(): void
    {
        $this->output->getFormatter()->setStyle('bold', new OutputFormatterStyle(null, null, ['bold']));
    }
}
