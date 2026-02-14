<?php

declare(strict_types=1);

namespace Firehed\GitTools;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitNukeCommand extends Command
{
    use CommandTrait;

    private const ARG_BRANCH_INDEX = 'index';

    public function configure(): void
    {
        $this->setName('git:nuke');
        $this->setDescription('Interactively force-deletes a branch');
        $this->addArgument(
            name: self::ARG_BRANCH_INDEX,
            mode: InputArgument::OPTIONAL,
            description: 'The numeric index of of the branch to remove',
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $currentBranch = $this->repo->getCurrentBranch();
        $defaultBranch = $this->repo->getDefaultBranchName();
        $branches = $this->repo->getSortedBranchNames(SortOrder::Alphabetical);

        $index = $input->getArgument(self::ARG_BRANCH_INDEX);

        if ($index === null) {
            $index = $this->askForBranchIndex('Delete which branch?', $branches, $currentBranch, $input, $output);
        } else {
            assert(is_string($index));
            if (!ctype_digit($index)) {
                throw new RuntimeException('Invalid branch.');
            }
            $index = (int) $index;
        }

        if ($index === 0) {
            throw new RuntimeException('Refusing to remove the default branch.');
        }
        if ($branches[$index] === $currentBranch) {
            throw new RuntimeException('Cannot remove the currently selected branch.');
        }

        return $this->removeGitBranch($branches[$index]);
    }

    /**
     * @return int the command exit code (indicating success or failure)
     */
    private function removeGitBranch(
        string $branchName,
    ): int {
        $result = $this->repo->forceDeleteBranch($branchName);
        return $result ? Command::SUCCESS : Command::FAILURE;
    }
}
