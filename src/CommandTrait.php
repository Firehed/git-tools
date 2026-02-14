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
    public function __construct(protected Repository $repo)
    {
        parent::__construct();
    }

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
        $branchDates = $this->repo->getBranchCommitDates();

        $padding = strlen((string)(count($branches) - 1));
        $format = "[% {$padding}d] % 1s %s (%s)";

        foreach ($branches as $index => $branch) {
            $date = $branchDates[$branch];
            $output->writeln(sprintf(
                $format,
                $index,
                $branch === $currentBranch ? '*' : '',
                $branch,
                $this->renderDate($date),
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

    private function renderDate(\DateTimeInterface $date, \DateTimeImmutable $now = new \DateTimeImmutable()): string
    {
        $diff = $now->diff($date);
        $days = $diff->d;
        if ($diff->m > 0) {
            $days += ($diff->m * 30);
        }
        if ($diff->y > 0) {
            $days += ($diff->y * 365);
        }
        return match ($days) {
            0 => 'today',
            1 => 'yesterday',
            default => "{$days}d",
        };
    }
}
