<?php

declare(strict_types=1);

namespace Firehed\GitTools;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitLandedCommand extends Command
{
    use CommandTrait;

    public function configure(): void
    {
        $this->setName('git:landed');
        $this->setDescription('Treats the current branch as landed, by switching back to the default branch, updating it, and removing the original branch.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = new Repository($_SERVER['PWD']);

        $currentBranch = $repo->getCurrentBranch();
        $defaultBranch = $repo->getDefaultBranchName();

        if ($currentBranch === $defaultBranch) {
            throw new RuntimeException('On default branch. Aborting.');
        }

        $repo->changeToBranch($defaultBranch);
        $repo->forceDeleteBranch($currentBranch);
        $repo->pull();

        return Command::SUCCESS;
    }
}
