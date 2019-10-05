<?php

namespace ingelby\toolbox\helpers\supervisor;


use ingelby\toolbox\helpers\supervisor\exceptions\SupervisorCommandException;
use ingelby\toolbox\helpers\supervisor\exceptions\SupervisorProcessNotRunningException;

class SupervisordHelper
{
    /**
     * @return array
     * @throws SupervisorCommandException
     * @throws SupervisorProcessNotRunningException
     */
    public static function status()
    {
        return static::runCommand('supervisorctl status');
    }

    /**
     * @param string $command
     * @return array
     * @throws SupervisorCommandException
     * @throws SupervisorProcessNotRunningException
     */
    protected static function runCommand($command)
    {
        \Yii::info('Running supervisor command: ' . $command);
        $commandLineResponse = [];
        $returnVariable = null;
        exec($command, $commandLineResponse, $returnVariable);

        if ([] !== $commandLineResponse && false !== strpos($commandLineResponse[0], 'unix:///tmp/supervisor.sock')) {
            throw new SupervisorProcessNotRunningException(
                'Supervisord process not running: ' . $commandLineResponse[0]
            );
        }
        if (0 !== $returnVariable) {
            throw new SupervisorCommandException(
                'Command: "' . $command . '" exited with error: ' . $commandLineResponse . ' exit code: ' .
                $returnVariable
            );
        }

        return $commandLineResponse;
    }

    /**
     * @return array
     * @throws SupervisorCommandException
     * @throws SupervisorProcessNotRunningException
     */
    public static function restart($processName = null)
    {
        if (null === $processName) {
            return static::runCommand('supervisorctl restart all');
        }

        return static::runCommand('supervisorctl restart ' . $processName);

    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * @return array
     */
    public static function restartService()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return static::runCommand('service supervisord restart');
    }
}
