<?php

namespace OpenEuropa\TaskRunner;

use Robo\Application as RoboApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Application.
 *
 * @package OpenEuropa\TaskRunner
 */
class Application extends RoboApplication {

    /**
     * This command is identical to its parent, but public rather than protected.
     */
    public function runCommand(Command $command, InputInterface $input, OutputInterface $output) {
        return $this->doRunCommand($command, $input, $output);
    }

}
