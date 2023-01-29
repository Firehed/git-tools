<?php

declare(strict_types=1);

namespace Firehed\GitTools;

class Repository
{
    private const REF_PREFIX = 'refs/heads/';
    private const REF_PREFIX_LENGTH = 11; // = strlen(REF_PREFIX)

    public function __construct(private string $pwd)
    {
    }

    /**
     * TODO: memoize result?
     * @return string[]
     */
    public function getBranchNames(): array
    {
        // Is refs/heads functionally equivalent to `git branch`?
        // FIXME: REF_PREFIX
        $cmd = 'git for-each-ref refs/heads --format="%(refname)"';

        $result = $this->executeCommandInPwd($cmd);

        if ($result['exitCode'] > 0) {
            throw new \Exception('Git branch lookup failed');
        }

        $branches = explode("\n", trim($result['stdout']));

        // Return without leading refs/heads/ text
        return array_map(self::trimRefsHeads(...), $branches);
    }

    private static function trimRefsHeads(string $fullName): string
    {
        return substr($fullName, self::REF_PREFIX_LENGTH);
    }

    /**
     * TODO: memoize result?
     */
    public function getCurrentBranch(): string
    {
        $cmd = 'git symbolic-ref HEAD';
        $result = $this->executeCommandInPwd($cmd);

        if ($result['exitCode'] > 0) {
            // Could be outside of a repository
            // Could be in detached head state
            // Other situations?
            throw new \RuntimeException('Not in a repo or not on a branch');
        }

        $branchName = trim($result['stdout']);
        assert(str_starts_with(haystack: $branchName, needle: self::REF_PREFIX));

        return self::trimRefsHeads($branchName);
    }

    /**
     * @return string[]
     */
    public function getChangedFiles(string $comparisonBranch): array
    {
        $cmd = sprintf('git diff --name-only %s', escapeshellarg($comparisonBranch));
        $result = $this->executeCommandInPwd($cmd);
        assert($result['exitCode'] === 0);
        return explode("\n", trim($result['stdout']));
    }

    public function changeToBranch(string $name): bool
    {
        $cmd = "git checkout $name";
        $result = $this->executeCommandInPwd($cmd);
        fwrite(STDOUT, $result['stdout']);
        fwrite(STDERR, $result['stderr']);
        return $result['exitCode'] === 0;
    }

    public function forceDeleteBranch(string $name): bool
    {
        $cmd = 'git branch -D ' . escapeshellarg($name);
        $result = $this->executeCommandInPwd($cmd);
        fwrite(STDOUT, $result['stdout']);
        fwrite(STDERR, $result['stderr']);
        return $result['exitCode'] === 0;
    }

    public function getDefaultBranchName(): string
    {
        $branches = $this->getBranchNames();
        // Prefer these branch names, in order
        // TODO: make this customizable?
        // TODO: see if there's any way to get this from git itself
        $candidates = ['main', 'master', 'default', 'dev'];
        foreach ($candidates as $branch) {
            if (in_array($branch, $branches, true)) {
                return $branch;
            }
        }
        throw new \RuntimeException(sprintf(
            'Could not determine default branch name (looked for %s)',
            implode(', ', $candidates),
        ));
    }

    /**
     * Returns the branch names with the default branch at position zero and
     * the rest sorted alphabetically.
     *
     * @return string[]
     */
    public function getSortedBranchNames(): array
    {
        $default = $this->getDefaultBranchName();
        $branches = $this->getBranchNames();
        usort($branches, function ($lhs, $rhs) use ($default): int {
            if ($lhs === $default) {
                return -1;
            }
            if ($rhs === $default) {
                return 1;
            }
            // Default(ish) string sorting
            return mb_strtolower($lhs) <=> mb_strtolower($rhs);
        });
        return $branches;
    }

    public function pull(): bool
    {
        $result = $this->executeCommandInPwd('git pull');
        return $result['exitCode'] === 0;
    }

    /**
     * @return array{
     *   stdout: string,
     *   stderr: string,
     *   exitCode: int,
     * }
     */
    private function executeCommandInPwd(string $command)
    {
        $spec = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ];
        $proc = proc_open(
            command: $command,
            descriptor_spec: $spec,
            pipes: $pipes,
            cwd: $this->pwd,
        );
        if ($proc === false) {
            throw new \Exception('Command could not be run');
        }

        $stdout = stream_get_contents($pipes[1]);
        if ($stdout === false) {
           $stdout = '';
        }
        $stderr = stream_get_contents($pipes[2]);
        if ($stderr === false) {
           $stderr = '';
        }
        $exitCode = proc_close($proc);
        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exitCode' => $exitCode,
        ];
    }
}
