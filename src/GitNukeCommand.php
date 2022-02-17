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

    protected static $defaultName = 'git:nuke';

    public function configure(): void
    {
        $this->setDescription('Interactively force-deletes a branch');
        $this->addArgument(
            name: self::ARG_BRANCH_INDEX,
            mode: InputArgument::OPTIONAL,
            description: 'The numeric index of of the branch to remove',
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // :(
        $repo = new Repository($_SERVER['PWD']);

        $currentBranch = $repo->getCurrentBranch();
        $defaultBranch = $repo->getDefaultBranchName();
        $branches = $repo->getSortedBranchNames();

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

        return $this->removeGitBranch($repo, $branches[$index]);
    }

    /**
     * @return int the command exit code (indicating success or failure)
     */
    private function removeGitBranch(
        Repository $repository,
        string $branchName,
    ): int {
        $result = $repository->forceDeleteBranch($branchName);
        return $result ? Command::SUCCESS : Command::FAILURE;
    }
}
