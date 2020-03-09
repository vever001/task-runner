<?php

/**
 * @file
 * Execute Task Runner commands via Robo.
 */

use OpenEuropa\TaskRunner\Config\ConfigInitializer;
use OpenEuropa\TaskRunner\TaskRunner;
use Robo\Common\TimeKeeper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

// Start Timer.
$timer = new TimeKeeper();
$timer->start();

// Initialize input and output.
$argv = $_SERVER['argv'];

// Allow @ aliases, e.g: ./vendor/bin/run @mysite command.
if (count($argv) > 1 && stripos($argv[1], '@') === 0) {
    $argv[1] = sprintf('--site=%s', substr($argv[1], 1));
}

$input = new ArgvInput($argv);
$output = new ConsoleOutput();

// Initialize configuration.
$config_initializer = new ConfigInitializer($repo_root, $input);
$config = $config_initializer->initialize();

// Execute command.
$runner = new TaskRunner($config, $input, $output, $classLoader);
$status_code = (int) $runner->run();

// Stop timer.
$timer->stop();
if ($output->isVerbose()) {
  $output->writeln("<comment>" . $timer->formatDuration($timer->elapsed()) . "</comment> total time elapsed.");
}

exit($status_code);
