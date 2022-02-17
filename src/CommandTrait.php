<?php

declare(strict_types=1);

namespace Firehed\GitTools;

use RuntimeException;
use Symfony\Component\Console\{
    Exception\MissingInputException,
    Helper\QuestionHelper,
    Input\InputInterface,
    Output\OutputInterface,
    Question\Question,
};

use function array_key_exists;
use function ctype_digit;
use function is_string;

trait CommandTrait
{
    /**
     * @param string[] $branches
     */
    private function askForBranchIndex(
        string $questionText,
        array $branches,
        string $currentBranch,
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $padding = strlen((string)(count($branches) - 1));
        $format = "[% {$padding}d] % 1s %s";

        foreach ($branches as $index => $branch) {
            $output->writeln(sprintf(
                $format,
                $index,
                $branch === $currentBranch ? '*' : '',
                $branch,
            ));
        }

        $question = new Question($questionText . ' ');
        $question->setValidator(function ($answer) use ($branches) {
            if ($answer === null) {
                throw new MissingInputException('Aborted');
            }
            assert(is_string($answer));
            if (!ctype_digit($answer)) {
                throw new RuntimeException('Invalid branch.');
            }
            $index = (int) $answer;
            if (!array_key_exists($index, $branches)) {
                throw new RuntimeException('Invalid branch.');
            }
            return (int)$answer;
        });

        $helper = new QuestionHelper();
        /** @var int */
        $answer = $helper->ask($input, $output, $question);

        return $answer;
    }
}
