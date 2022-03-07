<?php

declare(strict_types=1);

namespace Firehed\GitTools;

use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitShowChangedFilesCommand extends Command
{
    use CommandTrait;

    // private const ARG_BRANCH_INDEX = 'index';

    protected static $defaultName = 'git:show-changed-files';

    public function configure(): void
    {
        $this->setDescription('Displays files that differ from the given branch');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = new Repository($_SERVER['PWD']);

        // $currentBranch = $repo->getCurrentBranch();
        $defaultBranch = $repo->getDefaultBranchName();

        $comparisonBranch = $defaultBranch;
        // $cmd = sprintf('git diff --name-only %s', escapeshellarg($comparisonBranch));

        $results = $repo->getChangedFiles($comparisonBranch);
        foreach ($results as $result) {
            $output->writeln($result);
        }
        return Command::SUCCESS;
    }
}
