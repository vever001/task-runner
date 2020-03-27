<?php

namespace OpenEuropa\TaskRunner\Tasks\RunCommands;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use OpenEuropa\TaskRunner\Traits\ConfigurationTokensTrait;
use Robo\Common\BuilderAwareTrait;
use Robo\Contract\BuilderAwareInterface;
use Robo\Exception\TaskException;
use Robo\Robo;
use Robo\Task\BaseTask;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class RunCommands
 *
 * @package OpenEuropa\TaskRunner\Tasks\RunCommands
 */
class RunCommands extends BaseTask implements BuilderAwareInterface, ContainerAwareInterface
{
    use BuilderAwareTrait;
    use ConfigurationTokensTrait;
    use ContainerAwareTrait;

    /**
     * An array of Robo commands to run.
     *
     * Array keys as command names, values as params.
     *
     * @var array
     */
    protected $commands;

    /**
     * RunCommand constructor.
     *
     * @param string|array $commands
     */
    public function __construct($commands)
    {
        $this->commands = is_array($commands) ? $commands : [$commands];
    }

    /**
     * @return \Robo\Result
     * @throws \Robo\Exception\TaskException
     */
    public function run()
    {
        return $this->invokeCommands($this->commands);
    }

    /**
     * Invokes an array of Robo commands.
     *
     * @param array $commands
     *   An array of Robo commands to invoke.
     *
     * @throws \Exception
     */
    protected function invokeCommands(array $commands)
    {
        foreach ($commands as $key => $value) {
            if (is_numeric($key)) {
                $command = $value;
                $args = [];
            }
            else {
                $command = $key;

                // Validate $value to pass as arguments.
                if (!is_array($value)) {
                    $type = gettype($value);
                    throw new TaskException($this, "Invalid parameters for command '{$command}', array expected but '{$type}' given.");
                }

                $args = is_array($value) ? $value : [];
            }
            $this->invokeCommand($command, $args);
        }
    }

    /**
     * Invokes a single Robo command.
     *
     * @param string $command_name
     *   The name of the command, e.g., 'tests:behat'.
     * @param array $args
     *   An array of arguments to pass to the command.
     *
     * @throws \Exception
     */
    protected function invokeCommand($command_name, array $args = [])
    {
        $application = Robo::application();
        $command = $application->find($command_name);

        $input = new ArrayInput($args);
        $input->setInteractive(Robo::input()->isInteractive());
        Robo::output()->writeln("<comment>> $command_name</comment>");
        $exit_code = $application->runCommand($command, $input, Robo::output());

        // The application will catch any exceptions thrown in the executed
        // command. We must check the exit code and throw our own exception. This
        // obviates the need to check the exit code of every invoked command.
        if ($exit_code) {
            throw new \Exception("Command `$command_name {$input->__toString()}` exited with code $exit_code.");
        }
    }
}
