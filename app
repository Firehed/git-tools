#!/usr/bin/env php
<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;

chdir(__DIR__);
ini_set('display_errors', '0');
ini_set('error_log', '/dev/stderr');
// set_error_handler(function (int $severity, string $message, $file, $line): bool {
//     if (error_reporting() & $severity) {
//         throw new ErrorException($message, 0, $severity, $file, $line);
//     }
//     return false;
// }, -1);

require 'vendor/autoload.php';

// Tricky nonsense: match on argv[0] and automatically dispatch the correct
// command? Is there a good way to handle various shell aliases, being
// dispatched as a git sub-command, etc?

$repo = new Firehed\GitTools\Repository($_SERVER['PWD']);

$application = new Application();
$application->add(new Firehed\GitTools\GitShowChangedFilesCommand($repo));
$application->add(new Firehed\GitTools\GitSwitchCommand($repo));
$application->add(new Firehed\GitTools\GitLandedCommand($repo));
$application->add(new Firehed\GitTools\GitNukeCommand($repo));
$application->run();
