<?php

namespace ingelby\toolbox\traits;

use ingelby\toolbox\exceptions\ModelException;
use yii\base\Exception;
use yii\base\Model;

trait SoftDelete
{
    protected string $defaultStatusKey = 'statusId';

    /**
     * @return int
     */
    abstract protected function getDeletedStatusId(): int;

    /**
     * @throws Exception
     * @throws ModelException
     */
    public function softDelete(): bool
    {
        /** @var Model $this */
        if (!$this instanceof Model) {
            throw new Exception('Can not softDelete, object must be instance of: ' . Model::class);
        }

        $this->{$this->defaultStatusKey} = $this->getDeletedStatusId();

        if (false === $this->save()) {
            throw new ModelException('Unable to softDelete ' . Model::class, $this->getErrors());
        }

        return true;
    }
}
