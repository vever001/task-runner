<?php

namespace OpenEuropa\TaskRunner;

use Composer\Autoload\ClassLoader;
use Gitonomy\Git\Repository;
use OpenEuropa\TaskRunner\Commands\ChangelogCommands;
use OpenEuropa\TaskRunner\Commands\DrupalCommands;
use OpenEuropa\TaskRunner\Commands\DynamicCommands;
use OpenEuropa\TaskRunner\Commands\ReleaseCommands;
use OpenEuropa\TaskRunner\Contract\ComposerAwareInterface;
use OpenEuropa\TaskRunner\Contract\RepositoryAwareInterface;
use OpenEuropa\TaskRunner\Contract\TimeAwareInterface;
use OpenEuropa\TaskRunner\Services\Composer;
use OpenEuropa\TaskRunner\Contract\FilesystemAwareInterface;
use League\Container\ContainerAwareTrait;
use OpenEuropa\TaskRunner\Services\Time;
use Robo\Common\ConfigAwareTrait;
use Robo\Config\Config;
use Robo\Robo;
use Robo\Runner as RoboRunner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Application.
 *
 * @package OpenEuropa\TaskRunner
 */
class TaskRunner
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;

    const APPLICATION_NAME = 'OpenEuropa Task Runner';

    const REPOSITORY = 'openeuropa/task-runner';

    /**
     * @var RoboRunner
     */
    private $runner;

    /**
     * @var ConsoleOutput|OutputInterface
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var string
     */
    private $workingDir;

    /**
     * @var array
     */
    private $defaultCommandClasses = [
        ChangelogCommands::class,
        DrupalCommands::class,
        DynamicCommands::class,
        ReleaseCommands::class,
    ];

    /**
     * TaskRunner constructor.
     *
     * @param \Robo\Config\Config                               $config
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Composer\Autoload\ClassLoader                    $classLoader
     */
    public function __construct(Config $config, InputInterface $input, OutputInterface $output, ClassLoader $classLoader)
    {
        $this->setConfig($config);
        $this->input = $input;
        $this->output = $output;

        $this->workingDir = $this->getWorkingDir($this->input);
        chdir($this->workingDir);
        $config->set('runner.working_dir', realpath($this->workingDir));

        $this->application = $this->createApplication();
        $this->application->setAutoExit(false);
        $this->container = $this->createContainer($this->input, $this->output, $this->application, $this->config, $classLoader);

        // Create and initialize runner.
        $this->runner = new RoboRunner();
        $this->runner->setRelativePluginNamespace('TaskRunner');
        $this->runner->setContainer($this->container);
    }

    /**
     * Runs the instantiated Task Runner application.
     *
     * @return int
     *   The exiting status code of the application.
     */
    public function run()
    {
        // Register command classes.
        $this->runner->registerCommandClasses($this->application, $this->defaultCommandClasses);

        // Register commands defined in runner.yml file.
        $this->registerDynamicCommands($this->application);

        // Run command.
        return $this->runner->run($this->input, $this->output, $this->application);
    }

    /**
     * @param string $class
     *
     * @return \OpenEuropa\TaskRunner\Commands\AbstractCommands
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getCommands($class)
    {
        // Register command classes.
        $this->runner->registerCommandClasses($this->application, $this->defaultCommandClasses);

        return $this->getContainer()->get("{$class}Commands");
    }

    /**
     * Create and configure container.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \OpenEuropa\TaskRunner\Application $application
     * @param \Robo\Config\Config $config
     *
     * @return \League\Container\Container|\League\Container\ContainerInterface
     */
    private function createContainer(InputInterface $input, OutputInterface $output, Application $application, Config $config, ClassLoader $classLoader)
    {
        $container = Robo::createDefaultContainer($input, $output, $application, $config, $classLoader);
        $container->get('commandFactory')->setIncludeAllPublicMethods(false);
        $container->share('task_runner.composer', Composer::class)->withArgument($this->workingDir);
        $container->share('task_runner.time', Time::class);
        $container->share('repository', Repository::class)->withArgument($this->workingDir);
        $container->share('filesystem', Filesystem::class);

        // Add service inflectors.
        $container->inflector(ComposerAwareInterface::class)
            ->invokeMethod('setComposer', ['task_runner.composer']);
        $container->inflector(TimeAwareInterface::class)
            ->invokeMethod('setTime', ['task_runner.time']);
        $container->inflector(FilesystemAwareInterface::class)
            ->invokeMethod('setFilesystem', ['filesystem']);
        $container->inflector(RepositoryAwareInterface::class)
            ->invokeMethod('setRepository', ['repository']);

        return $container;
    }

    /**
     * Create application.
     *
     * @return \OpenEuropa\TaskRunner\Application
     */
    private function createApplication()
    {
        $application = new Application(self::APPLICATION_NAME, 'UNKNOWN');
        $application
            ->getDefinition()
            ->addOption(new InputOption('--working-dir', null, InputOption::VALUE_REQUIRED, 'Working directory, defaults to current working directory.', $this->workingDir));
        $application
            ->getDefinition()
            ->addOption(new InputOption('--site', null, InputOption::VALUE_REQUIRED, 'The multisite to execute this command against.', []));
        return $application;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return mixed
     */
    private function getWorkingDir(InputInterface $input)
    {
        return $input->getParameterOption('--working-dir', getcwd());
    }

    /**
     * @param \OpenEuropa\TaskRunner\Application $application
     */
    private function registerDynamicCommands(Application $application)
    {
        foreach ($this->getConfig()->get('commands', []) as $name => $tasks) {
            /** @var \Consolidation\AnnotatedCommand\AnnotatedCommandFactory $commandFactory */
            $commandFileName = DynamicCommands::class."Commands";
            $commandClass = $this->container->get($commandFileName);
            $commandFactory = $this->container->get('commandFactory');
            $commandInfo = $commandFactory->createCommandInfo($commandClass, 'runTasks');
            $command = $commandFactory->createCommand($commandInfo, $commandClass)->setName($name);
            $application->add($command);
        }
    }
}
