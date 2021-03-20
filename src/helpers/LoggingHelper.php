<?php

namespace ingelby\toolbox\helpers;

use ingelby\toolbox\exceptions\ModelException;

class LoggingHelper
{
    const CATEGORY_CACHE = 'cache';
    const CATEGORY_CONSOLE = 'console';

    /**
     * @param \Throwable $error
     * @param string     $category
     * @deprecated
     * @see LoggingHelper::logException()
     */
    public static function logError(\Throwable $error, $category = 'application')
    {
        \Yii::error(
            [
                'message' => $error->getMessage(),
                'line'    => $error->getLine(),
                'file'    => $error->getFile(),
                'trace'   => $error->getTraceAsString(),
            ],
            $category
        );
    }

    /**
     * @param \Throwable $e
     * @param string     $category
     */
    public static function logException(\Throwable $e, $category = 'application')
    {

        $error = [
            'message'  => $e->getMessage(),
            'trace'    => $e->getTraceAsString(),
            'line'     => $e->getLine(),
            'file'     => $e->getFile(),
            'previous' => '',
        ];

        $depth = 1;
        while (null !== $previous = $e->getPrevious()) {
            $key = 'previous_' . $depth;
            $error[$key] = $previous->getMessage();
            $error[$key . '_message'] = $previous->getMessage();
            $error[$key . '_trace'] = $previous->getTraceAsString();
            $error[$key . '_line'] = $previous->getLine();
            $error[$key . '_file'] = $previous->getFile();
            $depth++;
        }
        
        if ($e instanceof ModelException) {
            $error['modelException'] = json_encode($e->getModelErrors());
        }
        \Yii::error($error, $category);
    }
}
