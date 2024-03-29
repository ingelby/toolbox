<?php

namespace ingelby\toolbox\traits;

use Yii;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;

trait ModelCacheTag
{
    use CacheNames;

    protected int $modelCacheTTL = 60 * 60 * 15;

    public function invalidateModelCacheTag(): void
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
        return static::buildModelCacheTag($this->{$this->getModelPrimaryKey()});
    }

    /**
     * @param string $primaryKey
     * @return string
     */
    public static function buildModelCacheTag(string $primaryKey): string
    {
        return static::getCacheTag() . '_' . $primaryKey;
    }

    /**
     * @return string
     */
    abstract protected function getModelPrimaryKey(): string;
}
