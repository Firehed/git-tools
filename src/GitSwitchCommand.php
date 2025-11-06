<?php

declare(strict_types=1);

namespace Firehed\GitTools;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\{
    AsCommand,
    Argument,
};

#[AsCommand(name: 'git:switch', description: 'Interactively switches to a branch')]
class GitSwitchCommand extends Command
{
    use CommandTrait;

    public function __invoke(
        #[Argument('The numeric index of the branch to switch to')] ?int $index,
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $currentBranch = $this->repo->getCurrentBranch();
        $defaultBranch = $this->repo->getDefaultBranchName();
        $branches = $this->repo->getSortedBranchNames();

        if ($index === null) {
            $index = $this->askForBranchIndex('Switch to which branch?', $branches, $currentBranch, $input, $output);
        }

        if ($branches[$index] === $currentBranch) {
            throw new RuntimeException('You are already on that branch.');
        }

        return $this->switchToGitBranch($branches[$index]);
    }

    /**
     * @return int the command exit code (indicating success or failure)
     */
    private function switchToGitBranch(
        string $branchName,
    ): int {
        $result = $this->repo->changeToBranch($branchName);
        return $result ? Command::SUCCESS : Command::FAILURE;
    }
}
