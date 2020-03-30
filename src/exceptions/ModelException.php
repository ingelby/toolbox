<?php

namespace ingelby\toolbox\exceptions;

use Throwable;

class ModelException extends \Exception
{

    /**
     * @var array
     */
    protected $modelErrors;

    public function __construct($message = "", array $modelErrors = [], $code = 0, Throwable $previous = null)
    {
        $this->modelErrors = $modelErrors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getModelErrors(): array
    {
        return $this->modelErrors;
    }
}
