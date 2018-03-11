<?php

namespace Archivr\Cli;

use Symfony\Component\Console\Style\SymfonyStyle;

class Style extends SymfonyStyle
{
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
