<?php

namespace ingelby\toolbox\helpers;

/**
 * Class CommandLine
 * @package ingelby\toolbox\helpers
 */
class CommandLine {

    const COLOUR_RED = '31';
    const COLOUR_GREEN = '32';
    const COLOUR_YELLOW = '33';

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param string|array $item
     * @param null $colour
     * @param bool $debug
     * @param bool $logOutput
     */
    public static function printOutput($item, $colour = null, $debug = true, $logOutput = true)
    {
        if (is_array($item)) {
            $item = json_encode($item);
        }

        if ($debug) {
            echo "\033[0;$colour;40m$item\033[0m\n";
        }

        if ($logOutput) {
            \Yii::info($item);
        }

    }
}