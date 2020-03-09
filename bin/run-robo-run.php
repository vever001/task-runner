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
$input = new ArgvInput($_SERVER['argv']);
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
