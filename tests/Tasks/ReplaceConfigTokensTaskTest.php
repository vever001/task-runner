<?php

namespace EC\OpenEuropa\TaskRunner\Tests\Tasks;

use EC\OpenEuropa\TaskRunner\Tasks\ReplaceConfigTokens\ReplaceConfigTokens;
use EC\OpenEuropa\TaskRunner\Tests\AbstractTaskTest;
use Robo\Task\Simulator;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ReplaceConfigTokensTaskTest.
 *
 * @package EC\OpenEuropa\TaskRunner\Tests\Tasks
 */
class ReplaceConfigTokensTaskTest extends AbstractTaskTest
{
    use \EC\OpenEuropa\TaskRunner\Tasks\ReplaceConfigTokens\loadTasks;

    /**
     * Test task.
     *
     * @param array $data
     * @param array $expected
     *
     * @dataProvider testTaskDataProvider
     */
    public function testTask(array $data, array $expected)
    {
        $source = $this->getSandboxFilepath('source.yml');
        $destination = $this->getSandboxFilepath('destination.yml');
        file_put_contents($source, Yaml::dump($data));
        $this->taskReplaceConfigTokens($source, $destination)->run();
        $destinationData = Yaml::parse(file_get_contents($destination));
        $this->assertEquals($expected, $destinationData);
    }

    /**
     * @param string $text
     * @param array  $expected
     *
     * @dataProvider extractTokensDataProvider
     */
    public function testExtractTokens($text, array $expected)
    {
        $task = new ReplaceConfigTokens(null, null);
        $actual = $this->invokeMethod($task, 'extractTokens', [$text]);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function extractTokensDataProvider()
    {
        return $this->getFixtureContent('tasks/replace-config-tokens/extract.yml');
    }

    /**
     * @return array
     */
    public function testTaskDataProvider()
    {
        return $this->getFixtureContent('tasks/replace-config-tokens/task.yml');
    }
}