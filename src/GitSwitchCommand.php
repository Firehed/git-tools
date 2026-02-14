<?php

declare(strict_types=1);

namespace Firehed\GitTools;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\{
    Argument,
    AsCommand,
    Option,
};

#[AsCommand(name: 'git:switch', description: 'Interactively switches to a branch')]
class GitSwitchCommand extends Command
{
    use CommandTrait;

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option(description: 'Sort branches by age instead of name', shortcut: 'N')] bool $newestFirst = false,
        #[Argument('The numeric index of the branch to switch to')] ?int $index = null,
    ): int {
        $sortOrder = $newestFirst ? SortOrder::NewestFirst : SortOrder::Alphabetical;

        $currentBranch = $this->repo->getCurrentBranch();
        $defaultBranch = $this->repo->getDefaultBranchName();
        $branches = $this->repo->getSortedBranchNames($sortOrder);

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
