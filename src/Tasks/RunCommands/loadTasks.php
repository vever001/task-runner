<?php

namespace OpenEuropa\TaskRunner\Tasks\RunCommands;

/**
 * Trait loadTasks
 *
 * @package OpenEuropa\TaskRunner\Tasks\RunCommands
 */
trait loadTasks
{
    /**
     *
     * @return \OpenEuropa\TaskRunner\Tasks\RunCommands\RunCommands
     */
    public function taskRunCommands($commands)
    {
        return $this->task(RunCommands::class, $commands);
    }
}
