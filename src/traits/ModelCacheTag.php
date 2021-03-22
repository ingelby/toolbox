<?php

namespace ingelby\toolbox\traits;

use Yii;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;

trait ModelCacheTag
{
    use CacheNames;

    protected int $modelCacheTTL = 60 * 60 * 15;

    public function invalidateSellerCacheTag(): void
    {
        TagDependency::invalidate($this->getCache(), [$this->getModelCacheTag()]);
    }

    /**
     * @return CacheInterface
     */
    protected function getCache(): CacheInterface
    {
        return Yii::$app->cache;
    }

    /**
     * @return string
     */
    public function getModelCacheTag(): string
    {
        return static::getCacheTag() . '_' . $this->{$this->getModelPrimaryKey()};
    }

    /**
     * @return string
     */
    abstract protected function getModelPrimaryKey(): string;
}
